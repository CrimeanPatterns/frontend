<?php

namespace AwardWallet\MainBundle\Command\Update;

use AwardWallet\MainBundle\Entity\ItineraryCheckError;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ParserNoticeProvider;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateItineraryCheckErrorCommand extends Command
{
    public const MessageFinal = 'ItineraryCheckError: updated successfully! Total: %d, Added: %d, Updated: %d, Skipped: %d';
    public const MessageDataInCache = 'ItineraryCheckError: data was in cache, don\'t necessary update';
    public const MessageNoData = 'ItineraryCheckError: no data, nothing update';

    /** @var EntityManager */
    private $em;
    /** @var LoggerInterface */
    private $logger;
    /** @var ParserNoticeProvider */
    private $parserNotice;

    public function __construct(LoggerInterface $logger, EntityManager $em, ParserNoticeProvider $parserNotice)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->parserNotice = $parserNotice;
    }

    public function configure()
    {
        $this->setName('aw:update-itinerary-check-error')
            ->setDescription("Update ItineraryCheckError - get from Kibana Parse Notice. (default period - yesterday)")
            ->setHelp("Update ItineraryCheckError - get from Kibana Parse Notice.\n- If one of the dates is not set, then the day of the specified date is taken.\n- If both dates are empty, then the current day is taken.\n- If a date range is specified, then a period is processed that includes full days in the range")
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run')
            ->addOption('startDate', 'a', InputOption::VALUE_OPTIONAL, 'start day: format Y-m-d',
                date("Y-m-d", strtotime("-1 day")))
            ->addOption('endDate', 'b', InputOption::VALUE_OPTIONAL, 'end day: format Y-m-d');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $uxStart = null;
        $uxEnd = null;

        $start = $input->getOption('startDate');

        if (!empty($start)) {
            if (preg_match("/^\d{4}\-\d{2}\-\d{2}$/", $start) !== 1
                || empty($uxStart = strtotime($start))
                //                || $uxStart < strtotime("-1 year", time())
            ) {
                $this->logger->info("ItineraryCheckError[command]: wrong start date: format or earlier than 1 year ago");

                return 0;
            }
        }

        $end = $input->getOption('endDate');

        if (!empty($end)) {
            if (preg_match("/^\d{4}\-\d{2}\-\d{2}$/", $end) !== 1
                || empty($uxEnd = strtotime($end))
                //                || $uxEnd < strtotime("-1 year", time())
            ) {
                $this->logger->info("ItineraryCheckError[command]: wrong start date: format or earlier than 1 year ago");

                return 0;
            }
        }

        $this->logger->debug("start: " . var_export($uxStart, true) . (isset($uxStart) ? '//' . date("Y-m-d H:i", $uxStart) : ''));
        $this->logger->debug("end  : " . var_export($uxEnd, true) . (isset($uxEnd) ? '-' . date("Y-m-d H:i", $uxEnd) : ''));

        $dryRun = !empty($input->getOption('dry-run'));

        if ($dryRun) {
            $this->logger->debug("dry run");
        }

        $error = $this->em->getRepository(\AwardWallet\MainBundle\Entity\ItineraryCheckError::class);
        $providerRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $accountRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);

        [$fromCache, $newParserErrors] = $this->parserNotice->search($uxStart, $uxEnd);
        $flushCounter = 0;
        $added = 0;
        $updated = 0;
        $skipped = 0;

        if ($fromCache) {
            $log = self::MessageDataInCache;
            $this->logger->info($log);

            return 0;
        }

        if (count($newParserErrors) === 0) {
            $log = self::MessageNoData;
            $this->logger->info($log);

            return 0;
        }

        foreach ($newParserErrors as $row) {
            $accountId = $row['AccountId'] ? $accountRepository->find($row['AccountId']) : null;

            if (!empty($row['AccountId']) && empty($accountId)) {
                $this->logger->info('[ItineraryCheckError]: messed account ' . $row['AccountId']);
            }
            $providerId = $row['ProviderID'] ? $providerRepository->find($row['ProviderID']) : null;

            if (!isset($providerId)) {
                continue;
            }

            $dt = new \DateTime();
            $dt->setTimestamp($row['DetectionDate']);

            if (!empty($accountId) && $error->checkDuplicatesPerDay($providerId->getProviderid(),
                $accountId->getAccountid(), $dt,
                ItineraryCheckError::PARSER_NOTICE, $row['ErrorMessage'])
            ) {
                $flushCounter++;
                $skipped++;

                continue;
            }
            /** @var ItineraryCheckError $errorRow */
            $errorRows = $error->findBy([
                'detectiondate' => $dt,
                'providerid' => $providerId,
                'errortype' => ItineraryCheckError::PARSER_NOTICE,
                'accountid' => $accountId,
            ], ['detectiondate' => 'DESC'], 1);

            if (empty($errorRows)) {
                $errorRow = new ItineraryCheckError();
                $added++;
            } else {
                $errorRow = array_shift($errorRows);
                $updated++;
            }

            if ($dryRun) {
                $flushCounter++;

                continue;
            }

            $errorRow->setDetectiondate($dt)
                ->setProviderid($providerId)
                ->setAccountid($accountId)
                ->setErrorType(ItineraryCheckError::PARSER_NOTICE)
                ->setErrorMessage($row['ErrorMessage']);

            if (isset($row['RequestId'])) {
                $errorRow->setRequestid($row['RequestId']);
            }

            if (isset($row['Partner'])) {
                $errorRow->setPartner($row['Partner']);
            }

            if (empty($errorRow->getStatus())) {
                $errorRow->setStatus(ItineraryCheckError::STATUS_NEW);
            }

            $this->em->persist($errorRow);
            $this->em->flush();
            $this->em->clear();
            $flushCounter++;
            $this->logger->info("ItineraryCheckError: {$flushCounter} records processed...");
        }
        $total = $added + $updated + $skipped;
        $log = sprintf(self::MessageFinal, $total, $added, $updated, $skipped);
        $this->logger->info($log);

        return 0;
    }
}
