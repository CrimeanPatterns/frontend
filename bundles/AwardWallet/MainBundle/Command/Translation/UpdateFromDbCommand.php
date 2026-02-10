<?php

namespace AwardWallet\MainBundle\Command\Translation;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Manager\DbTranslationManager;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Model\MessageCollection;
use JMS\TranslationBundle\Translation\Dumper\XliffDumper;
use JMS\TranslationBundle\Translation\Loader\XliffLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateFromDbCommand extends Command
{
    protected static $defaultName = 'translation:update-from-db';

    private $transDir;
    private $projectDir;
    private DbTranslationManager $dbTranslationManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        DbTranslationManager $dbTranslationManager,
        EntityManagerInterface $entityManager,
        $projectDir
    ) {
        parent::__construct();
        $this->dbTranslationManager = $dbTranslationManager;
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    public function configure()
    {
        $this->setDescription("update Desc with english values in source field, synchronize source and target field values in english locale");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->transDir = $this->projectDir . '/translations';
        $locales = ['en'];
        $output->writeln("locales: " . implode(", ", $locales));

        $loader = new XliffLoader();

        $dumper = new XliffDumper();
        $dumper->setAddDate(false);

        $catalogue = $this->dbTranslationManager->extract();

        foreach ($catalogue->getDomains() as $domain => $messages) {
            /** @var MessageCollection $messages */
            foreach ($locales as $locale) {
                /** @var MessageCatalogue $existing */
                $file = $this->transDir . '/' . $domain . '.' . $locale . '.xliff';

                if (!file_exists($file)) {
                    continue;
                }
                $existing = $loader->load($file, $locale, $domain);
                $existingDomain = $existing->getDomain($domain);
                $fixedSource = 0;
                $fixedTranslation = 0;

                foreach ($messages->all() as $message) {
                    /** @var Message $message */
                    $key = $message->getId();

                    if ($existingDomain->has($key)) {
                        /** @var Message $existingMessage */
                        $existingMessage = $existingDomain->get($key);

                        if ($existingMessage->getDesc() != $message->getDesc()) {
                            $existingMessage->setDesc($message->getDesc());
                            $output->writeln("updated desc (source) for message $domain.$locale.$key");
                            $fixedSource++;
                        }

                        if ($locale == 'en' && $existingMessage->getSourceString() != $existingMessage->getLocaleString()) {
                            $existingMessage->setLocaleString($existingMessage->getDesc());
                            $output->writeln("updated target for message $domain.$locale.$key");
                            $fixedTranslation++;
                        }
                    }
                }

                if ($fixedSource > 0 || $fixedTranslation > 0) {
                    $out = "saving $domain.$locale";

                    if ($fixedSource > 0) {
                        $out .= ", $fixedSource changed sources";
                    }

                    if ($fixedTranslation > 0) {
                        $out .= ", $fixedTranslation changed targets";
                    }
                    $output->writeln($out);
                    file_put_contents($this->transDir . "/" . $domain . '.' . $locale . '.xliff', $dumper->dump($existing, $domain));
                }
            }
        }

        $this->faqEnglishOnly();

        return 0;
    }

    private function faqEnglishOnly()
    {
        $englishOnlyId = $this->entityManager->getConnection()->executeQuery('SELECT FaqID FROM Faq WHERE EnglishOnly = 1')->fetchAll();
        empty($englishOnlyId) ?: $englishOnlyId = array_column($englishOnlyId, 'FaqID');
        clearstatcache($this->transDir);

        $enDoc = new \DOMDocument();
        $enDoc->load($this->transDir . '/faq.en.xliff');
        $transNodes = $enDoc->getElementsByTagName('trans-unit');

        $xliff = glob($this->transDir . '/faq.*.xliff');

        for ($i = -1, $iCount = count($xliff); ++$i < $iCount;) {
            if ('faq.en' === pathinfo($xliff[$i], PATHINFO_FILENAME)) {
                continue;
            }
            $doc = new \DOMDocument();
            $doc->load($xliff[$i]);
            $xpath = new \DOMXPath($doc);
            $body = $xpath->query('(//*[@resname])[last()]')->item(0)->parentNode;

            $modified = 0;

            if (!empty($englishOnlyId)) {
                for ($j = -1, $jCount = count($englishOnlyId); ++$j < $jCount;) {
                    foreach ($xpath->query('//*[@resname="answer.' . $englishOnlyId[$j] . '" or @resname="question.' . $englishOnlyId[$j] . '"]') as $node) {
                        $node->parentNode->removeChild($node);
                        ++$modified;
                    }
                }
            }

            foreach ($transNodes as $node) {
                if (!$node->hasAttribute('resname')) {
                    continue;
                }
                $resname = $node->getAttribute('resname');

                if (in_array(filter_var($resname, FILTER_SANITIZE_NUMBER_INT), $englishOnlyId)) {
                    continue;
                }
                $is = $xpath->query('//*[@resname="' . $resname . '"]');

                if (!$is->length) {
                    $body->appendChild($doc->importNode($node, true));
                    ++$modified;
                }
            }

            if ($modified) {
                $doc->save($xliff[$i]);
            }
        }

        return;
    }
}
