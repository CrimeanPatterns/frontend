<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ItineraryRepositoryInterface;
use AwardWallet\MainBundle\Entity\Repositories\RentalRepository;
use AwardWallet\MainBundle\Entity\Repositories\ReservationRepository;
use AwardWallet\MainBundle\Entity\Repositories\RestaurantRepository;
use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Loyalty\Converter;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckRetrieveConfNoCommand extends Command
{
    private const SKIP_CHECKED_IN_LAST_DAYS = 1;
    private const MAX_PAST_DAYS = 365;
    protected static $defaultName = 'aw:check-retrieve-confno';

    /** @var LoggerInterface */
    private $logger;
    /** @var ApiCommunicator */
    private $apiCommunicator;
    /** @var EntityManager */
    private $em;
    /** @var Converter */
    private $converter;
    /** @var GlobalVariables */
    private $globals;
    private $err;
    private EntityManagerInterface $replicaEntityManager;

    public function __construct(
        LoggerInterface $logger,
        EntityManager $em,
        ApiCommunicator $apiCommunicator,
        Converter $converter,
        GlobalVariables $globals,
        EntityManagerInterface $replicaEntityManager
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->apiCommunicator = $apiCommunicator;
        $this->converter = $converter;
        $this->globals = $globals;
        $this->err = 0;
        $this->replicaEntityManager = $replicaEntityManager;
    }

    public function configure()
    {
        $this->setName(self::$defaultName);
        $this->setDescription("Check retrieve health on custom criteria");
        $this->setDefinition([
            new InputOption(
                'provider',
                'p',
                InputOption::VALUE_REQUIRED,
                "sets the provider for debugging (providerCode or providerId): \nFE: '... -p aa' or '... -p 1'"
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $provider = $input->getOption('provider');

        if ($provider) {
            $this->logger->info('Run for provider ' . $provider);
        } else {
            $this->logger->info('Run for all providers with Can Check Confirmation (not extension only)');
        }

        if (preg_match("/^\d+$/", $provider)) {
            $providerID = (int) $provider;
        } elseif (preg_match("/^\w+$/", $provider)) {
            $providerCode = $provider;
        } elseif (!empty($provider)) {
            $this->logger->error('wrong format provider');

            return 1;
        }

        $qb = $this->replicaEntityManager->createQueryBuilder();
        $qb
            ->select(['p.providerid as ProviderID', 'p.code as Code'])
            ->from(Provider::class, 'p')
            ->where('p.state >= :enabled')
            ->setParameter('enabled', PROVIDER_ENABLED, ParameterType::INTEGER)
            ->andWhere($qb->expr()
                ->notIn('p.state', [PROVIDER_CHECKING_EXTENSION_ONLY, PROVIDER_FIXING]))
            ->andWhere('p.cancheckconfirmation in (:list)')
            ->setParameter(
                'list',
                [CAN_CHECK_CONFIRMATION_YES_SERVER, CAN_CHECK_CONFIRMATION_YES_EXTENSION_AND_SERVER],
                Connection::PARAM_INT_ARRAY
            );

        if (isset($providerCode)) {
            $qb->andWhere('p.code = :code')
                ->setParameter('code', $providerCode, ParameterType::STRING);
        }

        if (isset($providerID)) {
            $qb->andWhere('p.providerid = :providerid')
                ->setParameter('providerid', $providerID, ParameterType::INTEGER);
        }
        $this->logger->info("Run query: select providers list");

        $q = $qb->getQuery();
        $rows = $q->getResult();

        $providersReservationTypes = $this->getProvidersReservationTypes();

        foreach ($rows as $row) {
            $this->logger->info("[{$row['Code']}]");
            $id = $row['ProviderID'];
            // check confFileds in parser for provider before all
            $provider = $this->replicaEntityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find($id);
            $checker = $this->globals->getAccountChecker($provider, false);
            $fields = $checker->GetConfirmationFields();

            if (!$this->isEmptyConfFields($fields, $provider->getCode())) {
                continue;
            }
            $types = array_filter($providersReservationTypes, function ($s) use ($id) {
                return $s['ProviderID'] == $id;
            });

            if (empty($types)) {
                $this->logger->info("no reservations found");

                continue;
            }
            $this->processProviderTypes($types);
        }

        return $this->err;
    }

    private function isEmptyConfFields($fields, $code): bool
    {
        if (!isset($fields) || !is_array($fields)) {
            $this->logger->error("provider does not have ConfFields definitions, but CanCheckConfirmation is set",
                ["Provider" => $code]);
            $this->err++;

            return false;
        }

        return true;
    }

    private function processProviderTypes(array $types)
    {
        foreach ($types as $type) {
            $this->logger->info("----[{$type['TableName']}]" . (isset($type['CatType']) ? '-cat/type-' . $type['CatType'] : ''));
            /** @var ItineraryRepositoryInterface $itineraryRep */
            $itineraryRep = $this->replicaEntityManager->getRepository(Itinerary::getItineraryClass($type['TableName']));
            $reservations = $this->getReservations($itineraryRep, $type['CatType'], self::SKIP_CHECKED_IN_LAST_DAYS,
                $type['ProviderID'], true);

            if (count($reservations) > 0) {
                $this->logger->info("in last day was ok");

                continue;
            }
            $reservations = $this->getReservations($itineraryRep, $type['CatType'], self::MAX_PAST_DAYS,
                $type['ProviderID'], true);

            if (count($reservations) > 0 && $this->prepareConfFieldsAndSend($reservations, $type['TableName'], true)) {
                continue;
            }
            $reservations = $this->getReservations($itineraryRep, $type['CatType'], self::MAX_PAST_DAYS,
                $type['ProviderID']);

            if (count($reservations) > 0 && $this->prepareConfFieldsAndSend($reservations, $type['TableName'], false)) {
                continue;
            }
            $this->logger->info("no future reservations");
        }
    }

    private function getReservations(
        ItineraryRepositoryInterface $table,
        ?int $type = null,
        int $pastDays,
        int $providerId,
        bool $withConfFields = false
    ) {
        $provider = $this->replicaEntityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find($providerId);

        if ($table instanceof TripRepository || $table instanceof ReservationRepository || $table instanceof RentalRepository || $table instanceof RestaurantRepository) {
            $qb = $table->createQueryBuilder('t');
            $qb->select("t")->distinct();

            if ($table instanceof TripRepository) {
                $qb->join('t.segments', 'tripSegments');
            }
            $criteria = $table->getFutureCriteria();
            $criteria->andWhere(Criteria::expr()->eq('t.provider', $provider));
            $criteria->andWhere(Criteria::expr()->eq('t.parsed', 1));

            if (isset($type)) {
                if ($table instanceof TripRepository) {
                    $criteria->andWhere(Criteria::expr()->eq('t.category', $type));
                } elseif ($table instanceof RestaurantRepository) {
                    $criteria->andWhere(Criteria::expr()->eq('t.eventtype', $type));
                }
            }
            $criteria->andWhere(Criteria::expr()->eq('t.hidden', 0));
            $criteria->andWhere(Criteria::expr()->isNull('t.userAgent')); // not ideal: UserAgentId = null - exclude duplicates of reservations

            if ($withConfFields) {
                $criteria->andWhere(Criteria::expr()->neq('t.confFields', null));
                $criteria->andWhere(Criteria::expr()->isNull('t.account'));
            } else {
                $criteria->andWhere(Criteria::expr()->isNull('t.confFields'));
                $criteria->andWhere(Criteria::expr()->neq('t.account', null)); // otherwise email parsing
                $qb->join('t.user', 'u');
                $fakeNames = [
                    'Business', 'AwardWallet', 'Bot', 'Account',
                    'Without', 'AW Plus', 'Test', 'test',
                    'Tester', 'tester', 'Имя', 'auto',
                    'login', 'user', 'User',
                ];
                $criteria->andWhere(Criteria::expr()->notIn('u.firstname', $fakeNames));
                $criteria->andWhere(Criteria::expr()->notIn('u.lastname', $fakeNames));
                // bad idea. not work with providers like expedia
                //                $qb->join('t.provider', 'p');
                //                $qb->join('t.account','a');
                //                $criteria->andWhere(Criteria::expr()->eq('p.providerid', 'a.providerid'));
            }
            $criteria->andWhere(Criteria::expr()->isNull('t.travelAgency'));
            $criteria->andWhere(Criteria::expr()->gte('t.updateDate', new \DateTime('-' . $pastDays . ' days')));
            $qb->addCriteria($criteria);

            if (!$withConfFields) {
                // regexp not work in QB
                //                $qb->andWhere('u.firstname REGEXP \'^[[:alpha:]][[:alpha:]]+[[:space:]][[:alpha:]]+$\' OR u.firstname REGEXP \'^[[:alpha:]][[:alpha:]]+[[.-.]][[:alpha:]]+$\' OR u.firstname REGEXP \'^[[:alpha:]][[:alpha:]]+$\'');
                //                $qb->andWhere('u.lastname REGEXP \'^[[:alpha:]][[:alpha:]]+[[:space:]][[:alpha:]]+$\' OR u.lastname REGEXP \'^[[:alpha:]][[:alpha:]]+[[.-.]][[:alpha:]]+$\' OR u.lastname REGEXP \'^[[:alpha:]][[:alpha:]]+$\'');
                // so...
                $qb->andWhere($qb->expr()->gt($qb->expr()->length('u.firstname'), 1));
                $qb->andWhere($qb->expr()->gt($qb->expr()->length('u.lastname'), 1));
                $badSymbols = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '(', ')', ';', '.', ',', '=', '\_'];

                foreach ($badSymbols as $badSymbol) {
                    $qb->andWhere($qb->expr()->notLike('u.firstname', '\'%' . $badSymbol . '%\''));
                    $qb->andWhere($qb->expr()->notLike('u.lastname', '\'%' . $badSymbol . '%\''));
                }
                $qb->andWhere($qb->expr()->notLike('u.email', '\'%@fakemail.com\''));
            }
            $qb->orderBy('t.updateDate', 'DESC');
            $qb->setMaxResults(3);

            return $qb->getQuery()->getResult();
        }

        return [];
    }

    private function prepareConfFieldsAndSend(array $reservations, string $table, bool $withConfFields)
    {
        foreach ($reservations as $reservation) {
            $code = $reservation->getProvider()->getCode();

            if (empty($code)) {
                $this->logger->warning("skip reservation. something wrong with provider info",
                    ["table" => $table, "id" => $reservation->getId()]);
                $this->err++;

                continue;
            }
            $this->logger->info("working with reservation", ["table" => $table, "id" => $reservation->getId()]);
            $confFields = null;

            if ($withConfFields) {
                $confFields = $reservation->getConfFields();

                if (!$this->checkCompletenessConfFields($reservation->getProvider(), $confFields)) {
                    $confFieldsCreated = $this->createConfirmationFields($reservation);

                    if (!empty($confFieldsCreated) && !empty($confFields)) {
                        $this->logger->info('try to complement confFields',
                            ["Provider" => $code, "confFields" => json_encode($confFields)]);
                        $confFields = $this->cleverMergeConfFields($confFieldsCreated, $confFields);

                        if (!$this->checkCompletenessConfFields($reservation->getProvider(), $confFields)) {
                            $confFields = null;
                        }
                        $this->logger->info('result of complement confFields',
                            ["Provider" => $code, "confFields" => json_encode($confFields)]);
                    }
                }
            } else {
                $confFields = $this->createConfirmationFields($reservation);
            }

            if (!is_array($confFields)) {
                $this->sendMessageWrongCheckFields($code, $table, $reservation->getId(), $withConfFields);

                return false;
            }
            $this->logger->info("sendToLoyalty", [
                "Provider" => $code,
                "confFields" => json_encode($confFields),
                "createdConfFields" => !$withConfFields,
                "table" => $table,
            ]);

            $this->sendToLoyalty(
                $code,
                $confFields,
                $reservation->getUserid(),
                $reservation->getUseragentid()
            );
        }

        return true;
    }

    private function cleverMergeConfFields(array $confFieldsCreate, array $confFields): ?array
    {
        $newFields = array_diff_key($confFieldsCreate, $confFields); // TODO: email - Email

        if (array_diff_key($confFieldsCreate, $newFields) == $confFields) {
            return array_merge($confFieldsCreate, $confFields);
        }

        if (count($newFields) == count($confFieldsCreate) - count($confFields)) {
            return array_merge($confFields, $newFields);
        }

        return null;
    }

    private function sendMessageWrongCheckFields(string $code, string $table, int $id, bool $fromTable)
    {
        $message = sprintf('something wrong with %sConfFields, %sID: %s', $fromTable ? '' : 'created ', $table, $id);
        $context = ["Provider" => $code];

        if ($fromTable) {
            $this->logger->warning($message, $context);
            $this->err++;
        } else {
            $this->logger->info($message, $context);
        }
    }

    private function sendToLoyalty(string $providerCode, array $confFields, $userId, $userAgentId)
    {
        $request = $this->converter->prepareCheckConfirmationRequest(
            $providerCode,
            $confFields,
            $userId,
            $userAgentId
        );

        try {
            $this->apiCommunicator->CheckConfirmation($request);
        } catch (ApiCommunicatorException $e) {
            $this->logger->error($e->getMessage());
            $this->err++;
        }
    }

    private function createConfirmationFields(object $reservation)
    {
        if (!empty($reservation->getUserAgentid())) {
            $user = $reservation->getUserAgent();
        } else {
            $user = $reservation->getUser();
        }
        $checker = $this->globals->getAccountChecker($reservation->getProvider(), false);
        $fields = $checker->GetConfirmationFields();

        if (!$this->isEmptyConfFields($fields, $reservation->getProvider()->getCode())) {
            return null;
        }

        foreach ($fields as $name => $field) {
            switch (strtolower($name)) {
                case 'givenname':
                case 'firstname':
                    $fields[$name]['Value'] = $user->getFirstname();

                    break;

                case 'lastname':
                case 'surname':
                case 'familyname':
                    $fields[$name]['Value'] = $user->getLastname();

                    break;

                case 'email':
                case 'emailaddress':
                    $fields[$name]['Value'] = $user->getEmail();

                    break;

                case 'confno':
                    $fields[$name]['Value'] = $reservation->getConfirmationNumber();

                    break;
            }
        }

        if ($reservation instanceof Trip) {
            foreach ($fields as $name => $field) {
                switch (strtolower($name)) {
                    case 'airlinecode':
                        $fields[$name]['Value'] = $reservation->getSegments()[0]->getAirline()->getCode();

                        break;

                    case 'flightnumber':
                        $fields[$name]['Value'] = $reservation->getSegments()[0]->getFlightNumber();

                        break;

                    case 'departuredate':
                    case 'depart':
                        $fields[$name]['Value'] = $reservation->getSegments()[0]->getDepartureDate()->format("m/d/Y");

                        break;
                }
            }
        }

        if ($reservation instanceof Reservation) {
            foreach ($fields as $name => $field) {
                switch (strtolower($name)) {
                    case 'checkindate':
                    case 'datein':
                        $fields[$name]['Value'] = $reservation->getCheckindate()->format("m/d/Y");

                        break;
                }
            }
        }

        $confFields = [];
        $problemFields = [];

        foreach ($fields as $name => $field) {
            if (!isset($field['Required'])) {
                $this->logger->warning('can\'t create confFields. problem with field in script. Field \'' . $name . '\' has no property \'Required\'',
                    ["Provider" => $reservation->getProvider()->getCode()]);
                $this->err++;

                return null;
            }

            if ($field['Required'] && empty($field['Value'])) {
                $problemFields[] = $name;
            } else {
                $confFields[$name] = $field['Value'];
            }
        }

        if (!empty($problemFields)) {
            $this->logger->info('can\'t create confFields. problem with field(s): ' . implode(',', $problemFields),
                ["Provider" => $reservation->getProvider()->getCode()]);

            return null;
        }
        $problemFields = [];

        foreach ($fields as $name => $field) {
            if (isset($field['Size'], $field['Value']) && $field['Size'] && !empty($field['Value']) && mb_strlen($field['Value']) > $field['Size']) {
                $problemFields[] = $name;
            }
        }

        if (!empty($problemFields)) {
            $this->logger->info('can\'t create confFields. problem with length of field(s): ' . implode(',', $problemFields),
                ["Provider" => $reservation->getProvider()->getCode()]);

            return null;
        }

        return $confFields;
    }

    private function checkCompletenessConfFields(Provider $provider, ?array $confFields): bool
    {
        if ($confFields == null) {
            return false;
        }
        $checker = $this->globals->getAccountChecker($provider, false);
        $fields = $checker->GetConfirmationFields();

        if (!$this->isEmptyConfFields($fields, $provider->getCode())) {
            return false;
        }

        foreach ($fields as $name => $field) {
            if (isset($field['Required']) && $field['Required'] && (!isset($confFields[$name]) || empty($confFields[$name]))) {
                $this->logger->info("structure ConfFields changed", ["Provider" => $provider->getCode()]);

                return false;
            }
        }

        return true;
    }

    private function getProvidersReservationTypes()
    {
        $this->logger->info("Run query: getProvidersReservationTypes");
        $list = [];

        foreach (Itinerary::$table as $kind => $table) {
            switch ($table) {
                case 'Trip':
                    $type = 'Category';

                    break;

                case 'Restaurant':
                    $type = 'EventType';

                    break;

                default:
                    $type = 'NULL';
            }
            $sql = "
            SELECT ProviderID, '{$table}' as TableName, {$type} as CatType
            FROM {$table}
            WHERE Parsed = 1 AND Hidden = 0
            GROUP BY 1, 2, 3
            ";
            $stmt = $this->replicaEntityManager->getConnection()->prepare($sql);
            $stmt->execute();
            $list = array_merge($list, $stmt->fetchAll(Query::HYDRATE_ARRAY));
        }

        return $list;
    }
}
