<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderpropertyRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Loyalty\Converters\PropertiesItinerariesConverter;
use AwardWallet\MainBundle\Loyalty\Resources\Answer;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\History;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryField;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryRow;
use AwardWallet\MainBundle\Loyalty\Resources\Property;
use AwardWallet\MainBundle\Loyalty\Resources\SubAccount;
use AwardWallet\MainBundle\Loyalty\Resources\SubAccountHistory;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class AccountCheckReportConverter
{
    private SerializerInterface $serializer;

    private ProviderRepository $providerRepository;

    private AccountRepository $accountRepository;

    private ProviderpropertyRepository $providerPropertyRepository;

    private PropertiesItinerariesConverter $propertiesItinerariesConverter;

    private LoggerInterface $logger;

    public function __construct(
        SerializerInterface $serializer,
        ProviderRepository $providerRepository,
        AccountRepository $accountRepository,
        ProviderpropertyRepository $providerPropertyRepository,
        PropertiesItinerariesConverter $propertiesItinerariesConverter,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->providerRepository = $providerRepository;
        $this->accountRepository = $accountRepository;
        $this->providerPropertyRepository = $providerPropertyRepository;
        $this->propertiesItinerariesConverter = $propertiesItinerariesConverter;
        $this->logger = $logger;
    }

    public function convert(\AccountCheckReport $report, int $source, bool $checkItineraries): CheckAccountResponse
    {
        $provider = $this->getProviderFromReport($report);
        $response = new CheckAccountResponse();
        $response->setState($report->errorCode)
                 ->setMessage($report->errorMessage)
                 ->setBalance($report->balance);

        $providerProperties = $this->providerPropertyRepository->getProviderProperties($provider);

        if (!empty($report->properties)) {
            $response->setProperties($this->convertProperties($report->properties, $providerProperties, []));
        }
        $response->setSubaccounts(
            $this->convertSubaccounts($report, $providerProperties)
        );
        $userData = (new UserData())->setSource($source)->setCheckIts($checkItineraries);
        $response->setUserdata($userData);
        $response->setHistory($this->handleHistory($report));

        $this->logger->pushProcessor(function (array $record) use ($provider) {
            $record['extra']['providerCode'] = $provider->getCode();

            return $record;
        });

        try {
            if (null !== $report->checker) {
                $itineraries = $this->propertiesItinerariesConverter->convertArrayToSchema($this->normalizeItineraries($report->checker->Itineraries), $provider);
            } else {
                $itineraries = $this->propertiesItinerariesConverter->extractItinerariesFromProperties($provider, $report->properties);
            }
        } finally {
            $this->logger->popProcessor();
        }

        if ($itineraries === \TAccountChecker::getNoItinerariesArray()) {
            $response->setNoitineraries(true);
            $response->setItineraries([]);
        } else {
            $response->setItineraries($itineraries);
        }

        $response->setInvalidanswers($this->convertInvalidAnswers($report->invalidAnswers));

        return $response;
    }

    public function getProviderFromReport(\AccountCheckReport $report): ?Provider
    {
        if ($report->account instanceof Account) {
            return $report->account->getProviderid();
        } elseif ($report->account instanceof \Account) {
            try {
                $accountFields = $report->account->getAccountInfo(false);
                $providerCode = $accountFields['ProviderCode'];
                /** @var Provider $provider */
                $provider = $this->providerRepository->findOneBy(['code' => $providerCode]);

                return $provider;
            } catch (\AccountException $e) {
                $this->logger->error($e->getMessage());

                return null;
            }
        } elseif (null !== $report->checker) {
            return $this->getProviderFromChecker($report->checker);
        }

        return null;
    }

    private function getProviderFromChecker(\TAccountChecker $checker): ?Provider
    {
        if (null === $checker || empty($checker->AccountFields)) {
            return null;
        }
        $providerCode = $checker->AccountFields['ProviderCode'];
        /** @var Provider $provider */
        $provider = $this->providerRepository->findOneBy(['code' => $providerCode]);

        return $provider;
    }

    private function handleHistory(\AccountCheckReport $report): ?History
    {
        if ((empty($report->properties['HistoryRows']) && empty($report->properties['SubAccounts'])) || empty($report->properties['HistoryColumns'])) {
            return null;
        }

        $history = (new History())->setRange(History::HISTORY_INCREMENTAL2);
        $columns = $report->properties['HistoryColumns'];

        if (!empty($report->properties['HistoryRows'])) {
            $history->setRows($this->buildHistoryRowsFromReport($report->properties['HistoryRows'], $columns));
        }

        $subAccHistory = [];

        if (!empty($report->properties['SubAccounts'])) {
            foreach ($report->properties['SubAccounts'] as &$subAccount) {
                if (empty($subAccount['Code']) || empty($subAccount['HistoryRows'])) {
                    continue;
                }
                $item = (new SubAccountHistory())
                    ->setCode($subAccount['Code'])
                    ->setRows($this->buildHistoryRowsFromReport($subAccount['HistoryRows'], $columns));
                $subAccHistory[] = $item;
                unset($subAccount['HistoryRows']);
            }
        }

        $history->setSubAccounts(!empty($subAccHistory) ? $subAccHistory : null);

        return $history;
    }

    /**
     * @return SubAccount[]|null
     */
    private function convertSubaccounts(\AccountCheckReport $report, array $providerProps): ?array
    {
        if (empty($report->properties['SubAccounts']) || !is_array($report->properties['SubAccounts'])) {
            return null;
        }

        $subAccounts = [];

        foreach ($report->properties['SubAccounts'] as $subAccountRow) {
            if (empty($subAccountRow['Code']) || empty($subAccountRow['DisplayName'])) {
                continue;
            }

            $subAccount = (new SubAccount())
                ->setCode($subAccountRow['Code'])
                ->setDisplayname($subAccountRow['DisplayName'])
                ->setBalance($subAccountRow['Balance'] ?? null);

            if (isset($subAccountRow['ExpirationDate'])) {
                switch ($subAccountRow['ExpirationDate']) {
                    case false:
                        $subAccount->setNeverexpires(true);

                        break;

                    default:
                        $expDate = (new \DateTime())->setTimestamp($subAccountRow['ExpirationDate']);
                        $subAccount->setExpirationDate($expDate);

                        break;
                }
                unset($subAccountRow['ExpirationDate']);
            }

            $subAccountProperties = $this->convertProperties($subAccountRow, $providerProps, ['Code', 'DisplayName', 'Balance']);
            $subAccount->setProperties($subAccountProperties);
            $subAccounts[] = $subAccount;
        }

        return $subAccounts;
    }

    /**
     * @param Providerproperty[] $providerProps
     */
    private function convertProperties(array $properties, array $providerProps, array $ignoreProps = [])
    {
        $ignoreProps = array_merge($ignoreProps, ['ExpirationDateCombined']);
        $result = [];

        foreach ($properties as $propCode => $propValue) {
            if (is_array($propValue) || in_array($propCode, $ignoreProps) || !isset($providerProps[$propCode])) {
                continue;
            }

            $property = $providerProps[$propCode];
            $propItem = new Property();
            $propItem->setCode($property->getCode())
                ->setKind($property->getKind())
                ->setName($property->getName())
                ->setValue(iconv("UTF-8", "UTF-8//IGNORE", $propValue));
            $result[] = $propItem;
        }

        return $result;
    }

    private function normalizeItineraries($itineraries): array
    {
        $isItItem = false;

        foreach (['Kind', "RecordLocator", "Number", "ConfirmationNumber", "ConfNo"] as $key) {
            $isItItem = array_key_exists($key, $itineraries) ? true : $isItItem;
        }

        if ($isItItem === true) {
            return [$itineraries];
        } else {
            return $itineraries;
        }
    }

    /**
     * @return HistoryRow[]
     */
    private function buildHistoryRowsFromReport(array $arrayRows, array $columns)
    {
        $rows = [];

        foreach ($arrayRows as $record) {
            $fields = [];

            foreach ($record as $name => $value) {
                if (isset($columns[$name])) {
                    if ($columns[$name] == 'PostingDate') {
                        $value = intval($value);
                    }
                    $fields[] = new HistoryField($name, $value);
                }
            }
            $rows[] = (new HistoryRow())->setFields($fields);
        }

        return $rows;
    }

    private function convertInvalidAnswers(array $invalidAnswers): ?array
    {
        if ($invalidAnswers === null) {
            return null;
        }

        $result = [];

        foreach ($invalidAnswers as $question => $answer) {
            $result[] = new Answer($question, $answer);
        }

        return $result;
    }
}
