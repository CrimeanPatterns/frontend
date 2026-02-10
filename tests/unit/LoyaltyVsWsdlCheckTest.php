<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 08.04.16
 * Time: 17:22.
 */

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\ConverterOptions;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountResponse;
use Doctrine\DBAL\Connection;

class LoyaltyVsWsdlCheckTest extends BaseContainerTest
{
    public const API_TIMEOUT = 30;

    /** @var Connection */
    private $connection;
    /** @var \TSchemaManager */
    private $manager;
    private $userIdWsdl;
    private $userIdJson;
    /** @var \Memcached */
    private $memcached;

    public function _before()
    {
        parent::_before();
        $this->manager = new \TSchemaManager();
        $this->userIdWsdl = $this->aw->createAwUser(null, null, [], true, true);
        $this->userIdJson = $this->aw->createAwUser(null, null, [], true, true);
        $this->connection = $this->container->get('database_connection');
        $this->memcached = $this->container->get(\Memcached::class);
        $this->container->get(LocalizeService::class)->setRegionalSettings();
    }

    public function _after()
    {
        $this->manager = null;
        $this->userIdWsdl = null;
        $this->userIdJson = null;
        $this->connection = null;
        $this->memcached = null;
    }

    /**
     * @dataProvider dataCheckAccount
     */
    public function testCheckAccount($provider, $login, $pass = '')
    {
        $repository = $this->container->get('doctrine')->getRepository(Account::class);
        $em = $this->container->get('doctrine.orm.entity_manager');

        $accountIdWsdl = $this->aw->createAwAccount($this->userIdWsdl, $provider, $login, $pass);
        /** @var Account $accountWsdl */
        $accountWsdl = $repository->find($accountIdWsdl);

        if (!is_object($accountWsdl)) {
            throw new \Exception('Can not find account to WSDL');
        }

        $accountIdJson = $this->aw->createAwAccount($this->userIdJson, $provider, $login, $pass);
        /** @var Account $accountJson */
        $accountJson = $repository->find($accountIdJson);

        if (!is_object($accountJson)) {
            throw new \Exception('Can not find account to JSON');
        }

        codecept_debug("Provider: {$provider}, Login: {$login}");

        $this->aw->checkAccount($accountIdWsdl, true, false);
        //        $this->aw->checkAccount($accountIdJson, true, false);
        $this->checkAccountJson($accountIdJson);

        $check = false;
        $timeStart = time();

        $reportJson = null;
        $reportWsdl = null;

        while (!$check && $timeStart + self::API_TIMEOUT > time()) {
            sleep(2);
            $em->refresh($accountWsdl);
            $em->refresh($accountJson);

            if ($accountWsdl->getUpdatedate()->getTimestamp() === $accountWsdl->getCreationdate()->getTimestamp()
               || $accountJson->getUpdatedate()->getTimestamp() === $accountJson->getCreationdate()->getTimestamp()
            ) {
                continue;
            }
            $reportJson = $this->memcached->get("aw_check_" . $accountIdJson);
            $reportWsdl = $this->memcached->get("aw_check_" . $accountIdWsdl);
            $check = true;
        }

        if (!isset($reportWsdl)) {
            throw new \Exception('No answer from Wsdl service');
        }

        if (!isset($reportJson)) {
            throw new \Exception('No answer from Json service');
        }

        //        $diff = $this->arrayRecursiveDiff($snapshotWsdl, $snapshotJson);
        //        codecept_debug("Diff result:");
        //        codecept_debug(json_encode($diff, JSON_PRETTY_PRINT));
        //        $this->assertEmpty($diff);
        if (!isset($reportWsdl->properties['CombineSubAccounts'])) {
            $reportWsdl->properties['CombineSubAccounts'] = false;
        }

        $this->prepareReportProperties($reportJson->properties);
        $this->prepareReportProperties($reportWsdl->properties);

        $this->assertTrue($reportJson instanceof \AccountCheckReport);
        $this->assertEquals($reportWsdl->errorCode, $reportJson->errorCode);
        $this->assertTrue($reportWsdl instanceof \AccountCheckReport);
        $this->assertEquals((int) $reportWsdl->balance, (int) $reportJson->balance, 'Balances');
        $this->assertEquals($reportWsdl->properties, $reportJson->properties, 'Properties');
        $this->assertEquals((int) $reportWsdl->errorCode, (int) $reportJson->errorCode, 'Error code');
        $this->assertEquals($reportWsdl->errorMessage, $reportJson->errorMessage, 'Error message');
    }

    public function dataCheckAccount()
    {
        return [
            //            ['testprovider', 'NoItineraries'],
            //            ['testprovider', 'future.trip'],
            //            ['testprovider', 'future.rental'],
            //            ['testprovider', 'future.reservation'],
            //            ['testprovider', 'future.restaurant'],
            //            ['testprovider', 'itmaster.20ta20c20r20l20e'],
            ['testprovider', 'itmaster.11ta11c11r11l11e'],
            //
            //            ['testprovider', 'subaccount_expired_combined'],
            //            ['testprovider', 'itmaster.no.trle'],
            //            ['testprovider', 'balance.comma'],
            //            ['testprovider', 'expiration.on'],
            //            ['testprovider', 'history'],
            //
            //
            //            ['testprovider', '2.subaccounts'],
            //            ['testprovider', 'elite.complex'],
        ];
    }

    protected function prepareReportProperties(array &$properties)
    {
        /* Itineraries */
        foreach (['Rentals', 'Itineraries', 'Reservations', 'Restaurants'] as $type) {
            if (isset($properties[$type])) {
                $properties[$type] = $this->prepareNumArrayValues($properties[$type]);
            }
        }
    }

    protected function prepareNumArrayValues(array $data)
    {
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $data[$key] = round($val, 2);
            }

            if (is_array($val)) {
                $data[$key] = $this->prepareNumArrayValues($val);
            }
        }

        return $data;
    }

    /**
     * @param null $parseHistory
     * @throws ApiCommunicatorException
     */
    protected function checkAccountJson($accountId, $parseHistory = null)
    {
        /** @var AccountRepository $repository */
        $repository = $this->container->get('doctrine')->getRepository(Account::class);
        /** @var ApiCommunicator $communicator */
        $communicator = $this->container->get(ApiCommunicator::class);
        /** @var Converter $converter */
        $converter = $this->container->get(Converter::class);

        /** @var Account $account */
        $account = $repository->find($accountId);

        if (!is_object($account)) {
            return;
        }

        $options = new ConverterOptions();
        $options->setParseHistory($parseHistory);
        /** @var CheckAccountRequest $request */
        $request = $converter->prepareCheckAccountRequest($account, $options);

        /** @var PostCheckAccountResponse $response */
        $response = $communicator->CheckAccount($request);
    }

    private function loadAccountSnapshot($accountId)
    {
        $rows = [
            ['Table' => 'Account', 'ID' => $accountId],
        ];

        $row = $this->connection->executeQuery("select * from Account where AccountID = :accountId", ['accountId' => $accountId])->fetch(\PDO::FETCH_ASSOC);
        $rows = array_merge($rows, $this->manager->ChildRows("Account", $row));
        $rows = array_reverse($rows);
        $this->manager->loadRows($rows);
        $result = [];

        foreach ($rows as $row) {
            $result[] = ['Table' => $row['Table'], 'Row' => $this->filterRow($row['Values'])];
        }
        usort($result, function ($a, $b) { return strcmp($a['Table'], $b['Table']); });

        //        $rows = array_map(function(array $row){ return $this->filterRow($row['Values']); }, $rows);
        return $result;
    }

    private function arrayRecursiveDiff($aArray1, $aArray2)
    {
        $aReturn = [];

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);

                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = "$mValue -> {$aArray2[$mKey]}";
                    }
                }
            } else {
                $aReturn[$mKey] = "+" . $mValue;
            }
        }

        return $aReturn;
    }

    private function filterRow(array $values)
    {
        $result = [];

        foreach ($values as $key => $value) {
            if (preg_match('#\w+(ID|Date)$#ms', $key)) {
                continue;
            }

            if (in_array($key, ["BrowserState", "DebugInfo", "LastDurationWithPlans", "TripsHash", "CheckedBy"])) {
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
