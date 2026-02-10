<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\ItineraryCheckError;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ItineraryRepositoryInterface;
use AwardWallet\MainBundle\Entity\Repositories\ParkingRepository;
use AwardWallet\MainBundle\Entity\Repositories\RentalRepository;
use AwardWallet\MainBundle\Entity\Repositories\ReservationRepository;
use AwardWallet\MainBundle\Entity\Repositories\RestaurantRepository;
use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\RetrieveAlertProvider;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckItinerariesCommand extends Command
{
    private const SKIP_CHECKED_IN_LAST_DAYS = 3;
    private const SKIP_CHECKED_IN_LAST_DAYS_EXTENSION = 10;
    protected static $defaultName = 'aw:check-itineraries';

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private RetrieveAlertProvider $retrieveAlert;
    private EntityManagerInterface $replicaEntityManager;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        RetrieveAlertProvider $retrieveAlert,
        EntityManagerInterface $replicaEntityManager
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->em = $em;
        $this->retrieveAlert = $retrieveAlert;
        $this->replicaEntityManager = $replicaEntityManager;
    }

    public function configure()
    {
        $this->setName(self::$defaultName);
        $this->setDescription("Check itineraries health on custom criteria (only errors without check accounts)");
        $this->setDefinition([
            new InputOption(
                'provider',
                'p',
                InputOption::VALUE_REQUIRED,
                "sets the providerCode for debugging: \nFE: '... -p aa'"
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $provider = $input->getOption('provider');

        if (isset($provider)) {
            if (!preg_match("/^\w+$/", $provider)) {
                $this->logger->error('wrong format provider');

                return 1;
            }
            $this->logger->info('Run for provider ' . $provider);
        } else {
            $this->logger->info('Run for all providers with Can Check Itineraries');
        }

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select(['p.providerid as ProviderID', 'p.code as Code, p.state as State'])
            ->from(Provider::class, 'p')
            ->where($qb->expr()
                ->in('p.state', [
                    PROVIDER_ENABLED,
                    PROVIDER_CHECKING_OFF,
                    PROVIDER_CHECKING_WITH_MAILBOX,
                    PROVIDER_CHECKING_EXTENSION_ONLY,
                ]))
            ->andWhere('p.cancheckitinerary = :checkIt')
            ->setParameter(
                'checkIt',
                1,
                ParameterType::INTEGER
            );

        if (isset($provider)) {
            $qb->andWhere('p.code = :code')
                ->setParameter('code', $provider, ParameterType::STRING);
        }
        $this->logger->info("Run query: select providers list");

        $q = $qb->getQuery();
        $providers = $q->getResult();
        $providersReservationTypes = $this->getProvidersReservationTypes();

        foreach ($providers as $row) {
            $this->logger->info("[{$row['Code']}]");
            $id = $row['ProviderID'];
            $types = array_filter($providersReservationTypes, function ($s) use ($id) {
                return $s['ProviderID'] == $id;
            });

            if (empty($types)) {
                $this->logger->info("---- send to db error: OUTDATED (no reservation)");
                $this->saveErrorToDatabase($row['ProviderID'], ItineraryCheckError::OUTDATED,
                    "no reservation for last year");
                $this->logger->info("no reservation for last year");

                continue;
            }
            $this->processProviderTypes($types, $row['State']);
        }

        if (count($providers) === 1) {
            $providerCode = $providers[0]['Code'];
        } else {
            $providerCode = null;
        }
        $this->retrieveErrors($providerCode);

        $this->deleteOldErrors();

        return 0;
    }

    private function processProviderTypes(array $types, int $state)
    {
        foreach ($types as $type) {
            $this->logger->info("----[{$type['TableName']}]");
            /** @var ItineraryRepositoryInterface $itineraryRep */
            $itineraryRep = $this->em->getRepository(Itinerary::getItineraryClass($type['TableName']));

            if ($this->hasFutureReservations($type['ProviderID'], $itineraryRep)) {
                $this->logger->info("---- has future reservations - ok");

                continue;
            }

            if ($state == PROVIDER_CHECKING_EXTENSION_ONLY) {
                // ??? in checkIts.php OUTDATED set when has future
                $this->logger->info("---- send to db error: OUTDATED");
                $this->saveErrorToDatabase($type['ProviderID'], ItineraryCheckError::OUTDATED,
                    "table: {$type['TableName']}");
            } else {
                if ($this->hasFreshAccounts($type['ProviderID'])) {
                    $this->logger->info("---- send to db error: NO_FUTURE_ITINERARIES");
                    $this->saveErrorToDatabase($type['ProviderID'], ItineraryCheckError::NO_FUTURE_ITINERARIES,
                        "table: {$type['TableName']}");
                } else {
                    $this->logger->info("---- has NO FRESH ACCs (updated at least in last 2 months) --- SKIP NO_FUTURE_ITINERARIES");
                }
            }
        }
    }

    private function hasFutureReservations(int $providerId, ItineraryRepositoryInterface $table)
    {
        $provider = $this->replicaEntityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find($providerId);

        if ($table instanceof TripRepository
            || $table instanceof ReservationRepository
            || $table instanceof RentalRepository
            || $table instanceof RestaurantRepository
            || $table instanceof ParkingRepository
        ) {
            $qb = $table->createQueryBuilder('t');
            $qb->select("t")->distinct();

            if ($table instanceof TripRepository) {
                $qb->join('t.segments', 'tripSegments');
            }
            $criteria = $table->getFutureCriteria();
            $criteria->andWhere(Criteria::expr()->eq('t.provider', $provider));
            $criteria->andWhere(Criteria::expr()->eq('t.parsed', 1));
            $criteria->andWhere(Criteria::expr()->eq('t.hidden', 0));

            if (in_array($provider->getState(), [PROVIDER_CHECKING_OFF, PROVIDER_CHECKING_EXTENSION_ONLY])) {
                $days = self::SKIP_CHECKED_IN_LAST_DAYS_EXTENSION;
            } else {
                $days = self::SKIP_CHECKED_IN_LAST_DAYS;
            }
            $criteria->andWhere(Criteria::expr()->gte('t.updateDate',
                new \DateTime('-' . $days . ' days')));
            $criteria->setMaxResults(1);
            $qb->addCriteria($criteria);

            return count($qb->getQuery()->getResult()) > 0;
        }

        return false;
    }

    private function hasFreshAccounts(int $providerId): bool
    {
        $provider = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find($providerId);
        $accRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $expr = Criteria::expr();
        $criteria = Criteria::create();
        $criteria->where($expr->gte('updatedate', new \DateTime('- 2 months')));
        $criteria->andWhere($expr->eq('providerid', $provider));

        return $accRep->matching($criteria)->count() > 0;
    }

    private function saveErrorToDatabase(
        int $providerId,
        int $errorType,
        ?string $errorMsg = null,
        ?string $requestId = null,
        ?string $partner = null,
        ?\DateTime $detectionDate = null,
        ?string $confirmationNumber = null
    ) {
        $providerRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);

        $providerId = $providerRepository->find($providerId);

        if (!isset($providerId)) {
            return;
        }

        if (!isset($detectionDate)) {
            $detectionDate = new \DateTime();
        }
        $error = $this->em->getRepository(\AwardWallet\MainBundle\Entity\ItineraryCheckError::class);

        if (!empty($confirmationNumber) && !empty($partner)
            && $error->checkDuplicatesPerDay($providerId->getProviderid(), null, $detectionDate,
                $errorType, $errorMsg, $confirmationNumber, $partner)
        ) {
            // not save. already in DB
            return;
        }

        $errorRow = new ItineraryCheckError();
        $errorRow
            ->setDetectiondate($detectionDate)
            ->setProviderid($providerId)
            ->setErrorType($errorType)
            ->setStatus(ItineraryCheckError::STATUS_NEW);

        if (isset($requestId)) {
            $errorRow->setRequestid($requestId);
        }

        if (isset($errorMsg)) {
            $errorRow->setErrormessage($errorMsg);
        }

        if (isset($partner)) {
            $errorRow->setPartner($partner);
        }

        if (isset($confirmationNumber)) {
            $errorRow->setConfirmationnumber($confirmationNumber);
        }

        $this->em->persist($errorRow);
        $this->em->flush();
        $this->em->clear();
    }

    private function getProvidersReservationTypes()
    {
        $this->logger->info("Run query: getProvidersReservationTypes");
        $list = [];

        foreach (Itinerary::$table as $kind => $table) {
            $sql = "
            SELECT DISTINCT ProviderID, '{$table}' as TableName
            FROM {$table}
            WHERE Parsed = 1 AND Hidden = 0
            AND UpdateDate < ADDDATE(NOW(), INTERVAL -1 DAY) AND UpdateDate > ADDDATE(NOW(), INTERVAL -6 MONTH)
            AND NOT AccountID IS NULL
            ";
            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->executeQuery();
            $list = array_merge($list, $stmt->fetchAll(Query::HYDRATE_ARRAY));
        }

        return $list;
    }

    private function retrieveErrors(?string $providerCode)
    {
        $data = $this->retrieveAlert->search(null, $providerCode);

        foreach ($data as $row) {
            $date = new \DateTime();
            $date->setTimestamp($row['DetectionDate']);
            $this->saveErrorToDatabase(
                $row['ProviderID'],
                ItineraryCheckError::RETRIEVE_ERROR,
                null,
                $row['RequestId'],
                $row['Partner'],
                $date,
                $row['ConfirmationNumber']
            );
        }
    }

    private function deleteOldErrors()
    {
        $conn = $this->em->getConnection();
        $conn->executeQuery(
            "DELETE FROM ItineraryCheckError WHERE DetectionDate < ?",
            [date('Y-m-d', strtotime("-1 year"))],
            [\PDO::PARAM_STR]
        );
    }
}
