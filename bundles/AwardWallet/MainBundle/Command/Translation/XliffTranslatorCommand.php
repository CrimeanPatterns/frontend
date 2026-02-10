<?php

namespace AwardWallet\MainBundle\Command\Translation;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\AIModel\AIModelService;
use AwardWallet\MainBundle\Service\AIModel\BatchConfig;
use AwardWallet\MainBundle\Service\AIModel\Exception\BatchProcessingException;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Dumper\XliffDumper;
use JMS\TranslationBundle\Translation\Loader\XliffLoader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class XliffTranslatorCommand extends Command
{
    private const STATE_AI_TRANSLATED = 'ai-translated';
    private const STATE_AI_TRANSLATION_FAILED = 'ai-translation-failed';
    private const UNTRANSLATABLE_MARKER = '___UNTRANSLATABLE___';

    protected static $defaultName = 'aw:translation:xliff-localize';

    private string $translationsDir;

    private array $availableLanguages;

    private AIModelService $aiModelService;

    private LoggerInterface $logger;

    private XliffLoader $xliffLoader;

    private XliffDumper $xliffDumper;

    private ?string $llmModel = null;

    public function __construct(
        string $projectDir,
        array $availableLanguages,
        AIModelService $aiModelService,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->translationsDir = $projectDir . '/translations';
        $this->availableLanguages = array_filter($availableLanguages, fn ($lang) => $lang !== 'en');
        $this->aiModelService = $aiModelService;
        $this->logger = $logger;
        $this->xliffLoader = new XliffLoader();
        $this->xliffDumper = new XliffDumper();
    }

    protected function configure()
    {
        $this
            ->setDescription('Automatically translate XLIFF files using LLM')
            ->addOption('language', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Language code to translate from (e.g., "ru")')
            ->addOption('domains', 'd', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Translation domains to process (e.g., "messages", "menu")')
            ->addOption('llm', null, InputOption::VALUE_REQUIRED, 'LLM model to use for translation (default: "deepseek")', 'deepseek');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('starting xliff translation command');
        $startTime = microtime(true);

        $availableLanguages = $this->availableLanguages;
        $availableDomains = $this->getDomains();
        $this->logger->info(sprintf('available languages: %s', implode(', ', $availableLanguages)));
        $this->logger->info(sprintf('available domains: %s', implode(', ', $availableDomains)));

        $languages = $availableLanguages;
        $domains = $availableDomains;

        if (!empty($selectedLanguages = $input->getOption('language'))) {
            $languages = array_intersect($availableLanguages, $selectedLanguages);
        }

        if (!empty($selectedDomains = $input->getOption('domains'))) {
            $domains = array_intersect($availableDomains, $selectedDomains);
        }

        $this->llmModel = $input->getOption('llm');

        if (empty($languages)) {
            $this->logger->error('no languages selected for translation');

            return 1;
        }

        if (empty($domains)) {
            $this->logger->error('no domains selected for translation');

            return 1;
        }

        $this->logger->info(
            sprintf(
                'selected languages: %s',
                count($languages) === count($availableLanguages) ? 'all' : implode(', ', $languages)
            )
        );
        $this->logger->info(
            sprintf(
                'selected domains: %s',
                count($domains) === count($availableDomains) ? 'all' : implode(', ', $domains)
            )
        );

        try {
            $stats = [];

            foreach ($languages as $language) {
                foreach ($domains as $domain) {
                    $stats[$language][$domain] = $this->translateXliffFile($language, $domain);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error during translation: %s', $e->getMessage()));

            return 1;
        }

        ksort($stats);

        foreach ($stats as $language => $domainsStats) {
            ksort($domainsStats);

            $output->writeln("\n<info>Statistics for language: $language</info>");
            $table = new Table($output);
            $table->setHeaders(['Domain', 'New', 'Changed', 'Removed', 'Unchanged', 'Translated', 'Failed', 'Cost', 'Requests']);

            foreach ($domainsStats as $domain => $domainStats) {
                $table->addRow([
                    $domain,
                    $domainStats['new'],
                    $domainStats['changed'],
                    $domainStats['removed'],
                    $domainStats['unchanged'],
                    \count($domainStats['translated']),
                    \count($domainStats['failed']),
                    sprintf('$%.4f', $domainStats['cost']),
                    $domainStats['requests'],
                ]);
            }

            $table->render();
        }

        $endTime = microtime(true);

        if ($endTime - $startTime < 60) {
            $this->logger->info(sprintf('translation completed in %.2f seconds', $endTime - $startTime));
        } else {
            $this->logger->info(sprintf('translation completed in %.2f minutes', ($endTime - $startTime) / 60));
        }

        return 0;
    }

    private function translateXliffFile(string $language, string $domain): array
    {
        $enCatalogue = $this->getMessageCatalogue('en', $domain);
        $enMessages = $enCatalogue->getDomain($domain);
        $targetCatalogue = $this->getMessageCatalogue($language, $domain);
        $targetMessages = $targetCatalogue ? $targetCatalogue->getDomain($domain) : null;
        $stats = [
            'new' => 0,
            'changed' => 0,
            'removed' => 0,
            'unchanged' => 0,
            'translated' => [],
            'failed' => [],
            'cost' => 0,
            'requests' => 0,
        ];
        $toTranslate = [];

        if (!$enMessages && !$targetMessages) {
            $this->logger->info(sprintf('no translation files found for domain "%s" in languages "en" and "%s"', $domain, $language));

            return $stats;
        }

        if (!$enMessages) {
            $stats['removed'] = count($targetMessages->all());
            $this->logger->info(sprintf('no English translation file found for domain "%s", removing all messages in "%s" language', $domain, $language));
            $this->removeXliffFile($language, $domain);

            return $stats;
        }

        if (!$targetMessages) {
            $targetCatalogue = new MessageCatalogue();
            $targetCatalogue->setLocale($language);
            // force creation of the domain if it does not exist
            $targetCatalogue->add(new Message('test', $domain));
            $targetMessages = $targetCatalogue->getDomain($domain);
            $targetMessages->replace([]);
        }

        /** @var Message $message */
        foreach ($enMessages->all() as $id => $message) {
            if ($targetMessages->has($id)) {
                /** @var Message\XliffMessage $targetMessage */
                $targetMessage = $targetMessages->get($id);

                if (
                    $targetMessage->getSourceString() === $message->getSourceString()
                    && $targetMessage->getState() != self::STATE_AI_TRANSLATION_FAILED
                    && $targetMessage->getLocaleString() !== $targetMessage->getSourceString()
                ) {
                    $stats['unchanged']++;
                } else {
                    $stats['changed']++;
                    $targetMessage->setDesc($message->getSourceString());
                    $targetMessage->setLocaleString($message->getLocaleString());
                    $toTranslate[$id] = $message->getSourceString();
                    $this->logger->info(sprintf('updated translation for id "%s" in domain "%s" (%s)', $id, $domain, $language));
                }

                $targetMessage->setNew(false);
                $targetMessage->setSources($message->getSources());
                $targetMessage->setMeaning($message->getMeaning());
            } else {
                $stats['new']++;
                $targetMessage = Message\XliffMessage::create($id, $domain);
                $targetMessage->setDesc($message->getSourceString());
                $targetMessage->setSources($message->getSources());
                $targetMessage->setMeaning($message->getMeaning());
                $targetMessage->setLocaleString($message->getLocaleString());
                $toTranslate[$id] = $message->getSourceString();
                $targetMessages->add($targetMessage);
                $this->logger->info(sprintf('added new translation for id "%s" in domain "%s" (%s)', $id, $domain, $language));
            }
        }

        foreach ($targetMessages->all() as $id => $targetMessage) {
            if (!$enMessages->has($id)) {
                $stats['removed']++;
                $targetMessages->filter(function (Message $message) use ($id) {
                    return $message->getId() !== $id;
                });
                $this->logger->info(sprintf('removed translation for id "%s" in domain "%s" (%s)', $id, $domain, $language));
            }
        }

        // translate remaining messages using AI model
        if (!empty($toTranslate)) {
            $this->logger->info(sprintf(
                'translating %d messages for domain "%s" in language "%s"',
                count($toTranslate),
                $domain,
                $language
            ));
            $stats['failed'] = array_keys($toTranslate);

            try {
                $batchResult = $this->aiModelService->sendBatchJsonRequest(
                    $this->prepareSystemMessage($language),
                    $toTranslate,
                    $this->llmModel,
                    BatchConfig::create()
                        ->withBatchSize(25)
                        ->withMaxRetries(2)
                        ->withMaxTokensPerBatch(50000)
                        ->withRetryDelayBase(2)
                );
            } catch (\Exception $e) {
                if ($e instanceof BatchProcessingException) {
                    $this->logger->error(sprintf('batch processing error: %s', $e->getMessage()));
                    $batchResult = $e->getResult();
                } else {
                    $this->logger->error(sprintf('error during translation: %s', $e->getMessage()));
                    $batchResult = null;
                }
            }

            if (is_null($batchResult)) {
                $this->logger->info('batch result is null, skipping translation');

                return $stats;
            } else {
                $stats['cost'] += $batchResult->getCost();
                $stats['requests'] += $batchResult->getRequestCount();
                $translations = $batchResult->getData();

                foreach ($translations as $id => $translation) {
                    if (!$targetMessages->has($id)) {
                        continue;
                    }

                    /** @var Message\XliffMessage $targetMessage */
                    $targetMessage = $targetMessages->get($id);
                    $sourceText = $targetMessage->getSourceString();

                    if (!StringHandler::isEmpty($translation)) {
                        // Check if LLM marked text as untranslatable
                        if ($translation === self::UNTRANSLATABLE_MARKER) {
                            // LLM could not translate - mark as failed
                            $targetMessage->setState(self::STATE_AI_TRANSLATION_FAILED);
                            $this->logger->warning(sprintf(
                                'LLM marked as untranslatable id "%s" in domain "%s" (%s): "%s"',
                                $id,
                                $domain,
                                $language,
                                $sourceText
                            ));
                        } elseif (
                            $translation !== $targetMessage->getLocaleString()
                            || $translation === $sourceText
                        ) {
                            // Translation is different from current - update it
                            $targetMessage->setLocaleString($translation);
                            $targetMessage->setState(self::STATE_AI_TRANSLATED);

                            if (($key = array_search($id, $stats['failed'])) !== false) {
                                unset($stats['failed'][$key]);
                            }

                            $stats['translated'][] = $id;
                            $stats['translated'] = array_unique($stats['translated']);

                            if ($translation === $sourceText) {
                                $this->logger->info(sprintf(
                                    'LLM kept identical translation for id "%s" in domain "%s" (%s): "%s"',
                                    $id,
                                    $domain,
                                    $language,
                                    $translation
                                ));
                            } else {
                                $this->logger->info(sprintf(
                                    'translated id "%s" in domain "%s" (%s): "%s"',
                                    $id,
                                    $domain,
                                    $language,
                                    $translation
                                ));
                            }
                        } else {
                            // Translation exists but different from source - keep existing translation
                            $this->logger->info(sprintf(
                                'keeping existing translation for id "%s" in domain "%s" (%s)',
                                $id,
                                $domain,
                                $language
                            ));
                        }
                    } else {
                        // Empty translation - mark as failed
                        $targetMessage->setState(self::STATE_AI_TRANSLATION_FAILED);
                        $this->logger->warning(sprintf(
                            'empty translation returned for id "%s" in domain "%s" (%s)',
                            $id,
                            $domain,
                            $language
                        ));
                    }
                }

                if (\count($stats['failed']) > 0) {
                    $this->logger->warning(sprintf(
                        'failed to translate %d messages in domain "%s" for language "%s": "%s"',
                        \count($stats['failed']),
                        $domain,
                        $language,
                        implode('", "', $stats['failed'])
                    ));

                    foreach ($stats['failed'] as $id) {
                        if ($targetMessages->has($id)) {
                            /** @var Message\XliffMessage $targetMessage */
                            $targetMessage = $targetMessages->get($id);
                            $targetMessage->setState(self::STATE_AI_TRANSLATION_FAILED);
                        }
                    }
                } else {
                    $this->logger->info(sprintf(
                        'successfully translated all messages in domain "%s" for language "%s"',
                        $domain,
                        $language
                    ));
                }
            }
        }

        if (\count($targetMessages->all()) > 0) {
            $targetMessages->sort(function (Message $a, Message $b) {
                return strcmp($a->getId(), $b->getId());
            });
            $this->xliffDumper->setAddDate(false);

            $filePath = sprintf('%s/%s.%s.xliff', $this->translationsDir, $domain, $language);
            file_put_contents($filePath, $this->xliffDumper->dump($targetCatalogue, $domain));
            $this->logger->info(sprintf('saved translation file "%s"', $filePath));
        } else {
            $this->logger->info(sprintf('no messages left in translation file for domain "%s" in language "%s", removing file', $domain, $language));
            $this->removeXliffFile($language, $domain);
        }

        return $stats;
    }

    private function prepareSystemMessage(string $language): string
    {
        $regionalVariants = [
            'es' => 'Latin American Spanish (México, Colombia, Argentina, etc.)',
            'pt' => 'Brazilian Portuguese',
            'fr' => 'International French',
            'de' => 'Standard German',
            'it' => 'Standard Italian',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'zh_CN' => 'Simplified Chinese (China)',
            'zh_TW' => 'Traditional Chinese (Taiwan)',
            'ch' => 'Traditional Chinese (Hong Kong)',
        ];

        $languageVariant = $regionalVariants[$language] ?? $language;

        return 'You are a professional UI/UX translator for AwardWallet - a loyalty program management platform. Your goal is to create natural, user-friendly translations that feel native to the target language.

## CORE PRINCIPLES
1. **Context over literal translation** - Consider what users actually expect to see
2. **UI-first approach** - Prioritize clarity, brevity, and usability  
3. **Native feel** - Make it sound like the app was originally built in the target language
4. **Key-based context** - Use translation keys to understand UI placement and purpose

## TECHNICAL REQUIREMENTS
- Preserve ALL HTML tags, placeholders (%var%, {count}, etc.), and formatting
- Return ONLY valid JSON - no explanations or comments
- Use "' . self::UNTRANSLATABLE_MARKER . '" only for corrupted/invalid content
- Convert Symfony plurals {0}text|{1}text|[2,Inf]text to target language format

## CONTEXT DETECTION FROM KEYS
Translation keys often hint at UI placement. Use these patterns as context clues:
- Keys like `*.add`, `*.save`, `*.delete` → Likely action buttons (keep short)
- Keys like `*.title`, `*.header`, `*.heading` → Probably section headers or page titles
- Keys like `*.menu.*`, `*.nav.*` → Often navigation elements (use standard menu terms)
- Keys like `*.error.*`, `*.warning.*` → System messages (be clear and helpful)
- Keys like `*.form.*`, `*.label.*` → Form elements (use standard form language)
- Keys like `*.tooltip.*`, `*.help.*` → Help text (brief explanations)
- Keys like `*.modal.*`, `*.dialog.*` → Dialog content (conversational tone)

Remember: These are just hints - always consider the actual text content and context.

## AWARDWALLET TERMINOLOGY GLOSSARY
AwardWallet is a platform for managing loyalty programs from airlines, hotels, credit cards, and more.
AwardWallet User is a registered user of AwardWallet who can manage their loyalty programs.
Award Program is a loyalty program account that a user adds to AwardWallet.
Add Person allows you to add the name of a user who is not registered with AwardWallet but for whom loyalty program accounts can be added.
Connections are links to other AwardWallet users who are registered in the system.
Award Booking is a service for booking flights using miles or points.
Add Award Booking Request allows the user to submit a request for booking a flight using miles or points.
Booking Requests Queue is the queue of submitted award flight booking requests.
AW Plus Subscription / AwardWallet Plus Subscription is a paid subscription that provides additional features and capabilities on AwardWallet.
Itinerary can refer to a flight, hotel, parking, restaurant, etc., which a user can add to AwardWallet.
AT201 Subscription is a subscription that unlocks additional AwardWallet features.
Loyalty Program is a rewards program whose account a user adds to AwardWallet.
Timeline is a chronological view of itineraries showing the sequence of the user’s events (flights, hotels, etc.).
Add Mailbox allows the user to add an email inbox that will be scanned to extract booking and asset information.
OneCard is a card that allows the user to manage their loyalty programs in AwardWallet.

## BRAND NAMES (NEVER TRANSLATE)
AwardWallet, OneCard, AW Plus, airline names (Delta, United), hotel chains (Marriott, Hilton), credit cards (Visa, Amex)

## TARGET LANGUAGE
' . $languageVariant . ' - Use appropriate regional terminology and interface conventions.

## EXAMPLES

English → Russian:
```json
{
    "button.add_member": "Добавить пользователя",
    "nav.connections": "Мои пользователи", 
    "title.persons": "Пользователи",
    "button.show_more": "Подробнее",
    "menu.award_booking": "Бронирование за мили",
    "form.credit_card": "Номер карты",
    "error.invalid_login": "Неверный логин",
    "brand.company": "AwardWallet"
}
```

English → English (reference):
```json
{
    "button.add_member": "Add User",
    "nav.connections": "My Users",
    "title.persons": "Users", 
    "button.show_more": "Show More",
    "menu.award_booking": "Book with Points",
    "form.credit_card": "Card Number",
    "error.invalid_login": "Invalid Login",
    "brand.company": "AwardWallet"
}
```

Target: ' . $language;
    }

    private function getMessageCatalogue(string $language, string $domain): ?MessageCatalogue
    {
        $filePath = sprintf('%s/%s.%s.xliff', $this->translationsDir, $domain, $language);

        if (!file_exists($filePath)) {
            $this->logger->info(sprintf('translation file "%s" does not exist', $filePath));

            return null;
        }

        return $this->xliffLoader->load(
            sprintf('%s/%s.%s.xliff', $this->translationsDir, $domain, $language),
            $language,
            $domain
        );
    }

    private function removeXliffFile(string $language, string $domain): void
    {
        $filePath = sprintf('%s/%s.%s.xliff', $this->translationsDir, $domain, $language);

        if (file_exists($filePath)) {
            unlink($filePath);
            $this->logger->info(sprintf('removed translation file "%s"', $filePath));
        } else {
            $this->logger->warning(sprintf('translation file "%s" does not exist, cannot remove', $filePath));
        }
    }

    /**
     * @return string[]
     */
    private function getDomains(): array
    {
        $finder = new Finder();
        $finder->files()->in($this->translationsDir)->name('*.xliff');
        $domains = [];

        foreach ($finder as $file) {
            $nameParts = explode('.', $file->getFilename());

            if (count($nameParts) != 3) {
                $this->logger->warning(
                    sprintf('Skipping file "%s" as it does not follow the expected naming convention.', $file->getFilename())
                );

                continue;
            }

            [$domain] = $nameParts;

            if (!in_array($domain, $domains)) {
                $domains[] = $domain;
            }
        }

        sort($domains);

        return $domains;
    }
}
