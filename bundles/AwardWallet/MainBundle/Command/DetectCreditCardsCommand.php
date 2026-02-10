<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\CardImageParser\CardImageParserLoader;
use AwardWallet\CardImageParser\CreditCardDetection\CreditCardDetectorInterface;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\CardImageRepository;
use AwardWallet\MainBundle\Globals\ClassUtils;
use AwardWallet\MainBundle\Manager\CardImage\ParserHandler;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmt;

class DetectCreditCardsCommand extends Command
{
    protected static $defaultName = 'aw:detect-credit-cards';

    /**
     * @var CardImageRepository
     */
    protected $cardImageRep;

    /**
     * @var ParserHandler
     */
    protected $parserHandler;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var DocParser
     */
    protected $docParser;

    /**
     * @var CardImageParserLoader
     */
    protected $parserLoader;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $dryRun;

    /**
     * @var int
     */
    protected $limitCensored;

    /**
     * @var int
     */
    protected $limitProcessed;
    private CardImageParserLoader $cardImageParserLoader;
    private ParserHandler $cardImageParserHandler;
    private ParserHandler $cardImageParserHandlerDryRun;
    private EntityRepository $cardImageRepository;
    private EntityManagerInterface $entityManager;
    private EntityRepository $providerRepository;

    public function __construct(
        CardImageParserLoader $cardImageParserLoader,
        ParserHandler $cardImageParserHandler,
        ParserHandler $cardImageParserHandlerDryRun,
        EntityRepository $cardImageRepository,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        EntityRepository $providerRepository
    ) {
        parent::__construct();
        $this->cardImageParserLoader = $cardImageParserLoader;
        $this->cardImageParserHandler = $cardImageParserHandler;
        $this->cardImageParserHandlerDryRun = $cardImageParserHandlerDryRun;
        $this->cardImageRepository = $cardImageRepository;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->providerRepository = $providerRepository;
    }

    protected function configure()
    {
        $this
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry-run mode')
            ->addOption('limit-processed', null, InputOption::VALUE_REQUIRED, 'limit processed cards')
            ->addOption('limit-censored', null, InputOption::VALUE_REQUIRED, 'limit censored cards');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->parserLoader = $this->cardImageParserLoader;

        if ($this->dryRun = $input->getOption('dry-run')) {
            $this->parserHandler = $this->cardImageParserHandlerDryRun;
        } else {
            $this->parserHandler = $this->cardImageParserHandler;
        }

        $this->limitCensored = $input->getOption('limit-censored');
        $this->limitProcessed = $input->getOption('limit-processed');
        $this->cardImageRep = $this->cardImageRepository;
        $this->docParser = new DocParser();
        $this->output = $output;
        $this->em = $this->entityManager;
        $providerRep = $this->providerRepository;
        /** @var Provider[] $providers */
        $providers = $providerRep->findBy(['canDetectCreditCards' => true]);
        $this->em->clear();

        foreach ($providers as $provider) {
            $this->processProvider($provider);
        }

        return 0;
    }

    protected function processProvider(Provider $provider)
    {
        $this->output->writeln('Processing ' . $providerCode = $provider->getCode());

        if (
            !($parser = $this->loadParser($providerCode = $provider->getCode()))
            || !($parser instanceof CreditCardDetectorInterface)
        ) {
            $this->output->writeln('credit card detector not found');

            return;
        }

        $currentDetectorVersion = $this->parserHandler->getCurrentDetectorVersion($providerCode);
        $processedCount = 0;
        $censoredCount = 0;
        $this->output->writeln('Current detector version: ' . $currentDetectorVersion);

        /** @var CardImage $cardImage */
        foreach ($this->getCardImagesIterator($provider, $currentDetectorVersion) as $containerCardImagesByKind) {
            if (
                isset($this->limitProcessed)
                && ($processedCount >= $this->limitProcessed)
            ) {
                $this->output->writeln('Processed cards limit reached');

                break;
            }

            if (
                isset($this->limitCensored)
                && ($censoredCount >= $this->limitCensored)
            ) {
                $this->output->writeln('Censored cards limit reached');

                break;
            }

            if ($this->processContainer($containerCardImagesByKind, $providerCode)) {
                $censoredCount++;
            }

            $this->em->clear();
            $processedCount++;
        }

        $this->output->writeln("Cards processed: {$processedCount}, censored {$censoredCount}");
    }

    /**
     * @param CardImage[] $containerCardImagesByKind
     * @return bool is credit card detected
     */
    protected function processContainer(array $containerCardImagesByKind, string $providerCode): bool
    {
        if (!($parser = $this->parserLoader->loadParser($providerCode))) {
            return false;
        }

        $container = current($containerCardImagesByKind)->getContainer();

        $this->output->writeln('Processing container: ' . ClassUtils::getName($container) . '(' . $container->getId() . ')');

        try {
            [$isCCDetected, $_] = $this->parserHandler->handleCreditCardDetection($parser, $containerCardImagesByKind, $providerCode);

            return (bool) $isCCDetected;
        } catch (\Throwable $e) {
            $this->output->writeln('error: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @return \Iterator<CardImage[]>
     */
    protected function getCardImagesIterator(Provider $provider, string $currentDetectorVersion): iterable
    {
        $stmt = $this->em->getConnection()->executeQuery("
            select 
                concat(
                    coalesce(sa.AccountID, ciDest.AccountID), 
                    if(ciSource.SubAccountID is null, '', concat('_', ciSource.SubAccountID))
                ) as ContainerID,
                ciDest.CardImageID
            from CardImage ciSource
            left join SubAccount sa on ciSource.SubAccountID = sa.SubAccountID
            left join Account a on sa.AccountID = a.AccountID
            join CardImage ciDest on 
                ciDest.AccountID = ciSource.AccountID or
                ciDest.SubAccountID = sa.SubAccountID
            where
                (
                    (
                        ciSource.AccountID is not null and
                        ciSource.ProviderID = ?
                    ) or
                    (
                        ciSource.SubAccountID is not null and
                        a.ProviderID = ?
                    )
                ) and
                ciSource.CCDetected <> 1 and 
                (
                    ciSource.CCDetectorVersion is null or
                    ciSource.CCDetectorVersion <> ?
                )
            order by ContainerID",
            [$provider->getProviderid(), $provider->getProviderid(), $currentDetectorVersion],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR]
        );

        return stmt($stmt)
            ->fromPairs()  // to ContainerID => CardImageID
            ->groupAdjacentByKey() // by ContainerID
            ->map(function ($cardImageIds) {
                $buffer = [];

                /** @var CardImage $cardImage */
                foreach ($this->cardImageRep->findBy(['cardImageId' => $cardImageIds]) as $cardImage) {
                    $buffer[$cardImage->getKind()] = $cardImage;
                }

                return $buffer;
            })
            ->filterNotEmpty();
    }

    protected function parseAnnotations(string $docComment): array
    {
        try {
            return $this->docParser->parse($docComment);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return CreditCardDetectorInterface|null
     */
    protected function loadParser(string $providerCode)
    {
        $parser = $this->parserLoader->loadParser($providerCode);

        if (
            !$parser
            || !($parser instanceof CreditCardDetectorInterface)
        ) {
            return null;
        }

        if ($parser instanceof LoggerAwareInterface) {
            $parser->setLogger($this->logger);
        }

        return $parser;
    }
}
