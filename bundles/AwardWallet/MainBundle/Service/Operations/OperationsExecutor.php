<?php

namespace AwardWallet\MainBundle\Service\Operations;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\UserErrorException;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use AwardWallet\MainBundle\Service\SocksMessaging\SendLogsToChannelHandler;
use AwardWallet\MainBundle\Service\WatchdogKillListProvider;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\TemplateWrapper;

class OperationsExecutor implements ExecutorInterface
{
    private LoggerInterface $logger;

    private SocksClient $messaging;

    private Connection $connection;

    private TemplateWrapper $twigTemplate;

    private WatchdogKillListProvider $watchdogKillListProvider;

    public function __construct(
        LoggerInterface $logger,
        SocksClient $messaging,
        Connection $connection,
        Environment $twig,
        WatchdogKillListProvider $watchdogKillListProvider
    ) {
        $this->logger = $logger;
        $this->messaging = $messaging;
        $this->twigTemplate = $twig->load('@AwardWalletMain/Manager/Support/Operations/operations.twig');
        $this->connection = $connection;
        $this->watchdogKillListProvider = $watchdogKillListProvider;
    }

    public static function listOfOperations(): array
    {
        return [
            "checkProvider" => "Check Provider with unknown errors",
            "checkProviderI" => "Check Provider with invalid logons",
            "checkProviderR" => "Check Provider with provider errors",
            "checkProviderW" => "Check Provider with warnings",
            "checkProviderE" => "Check Provider with missing expiration dates",
            "checkProviderSQ" => "Check Provider with security questions",
            "checkProviderT" => "Check Provider with possible presence itineraries",
            "checkProviderTY" => "Check Provider with itineraries",
            "checkProviderTN" => "Check Provider with no itineraries",
            "checkProviderS" => "Check Provider without errors",
            "checkProviderF" => "Full Check Provider",
            "checkProviderN" => "Check Provider with balance N/A",
            "checkProviderZB" => "Check Provider with Zero Balance",
            "resetExpDate" => "Reset Expiration Date",
            "resetDisabledI" => "Reset 'Disabled' state for accounts with invalid credentials",
            "resetDisabledP" => "Reset 'Disabled' state for accounts with provider errors",
            "resetDisabledL" => "Reset 'Disabled' state for accounts with lockouts",
            "resetDisabledU" => "Reset 'Disabled' state for accounts with unknown errors",
            "resetDisabledA" => "Reset 'Disabled' state for all accounts (except disabled by user)",
            "resetCache" => "Reset history cache",
        ];
    }

    public static function getOperationText(string $operation, string $provider): string
    {
        $text = self::listOfOperations()[$operation] ?? null;

        if (!isset($text)) {
            return 'Unknown operation';
        }

        if (strpos($operation, 'checkProvider') === 0) {
            return str_replace('Provider', 'Provider \'' . $provider . '\' ', $text);
        }

        $text .= " for provider '%s'";

        return sprintf($text, $provider);
    }

    /**
     * @param OperationsTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $this->logger->pushHandler(new SendLogsToChannelHandler(Logger::INFO, $task->getResponseChannel(),
            $this->messaging));

        try {
            $operation = $task->getOperation();
            $providerCode = $task->getProviderCode();
            $limit = $task->getLimit();
            $checkStart = $task->getCheckStart();
            $checkEnd = $task->getCheckEnd();
            $withBackgroundCheckOff = $task->getWithBackgroundCheckOff();

            switch ($operation) {
                case 'resetCache':
                    $result = $this->resetHistoryCache($providerCode);

                    break;

                case 'resetDisabledI':
                    $result = $this->resetDisabledState($providerCode, Account::DISABLE_REASON_PREVENT_LOCKOUT);

                    break;

                case 'resetDisabledP':
                    $result = $this->resetDisabledState($providerCode, Account::DISABLE_REASON_PROVIDER_ERROR);

                    break;

                case 'resetDisabledL':
                    $result = $this->resetDisabledState($providerCode, Account::DISABLE_REASON_LOCKOUT);

                    break;

                case 'resetDisabledU':
                    $result = $this->resetDisabledState($providerCode, Account::DISABLE_REASON_ENGINE_ERROR);

                    break;

                case 'resetDisabledA':
                    $result = $this->resetDisabledState($providerCode, Account::DISABLE_REASON_USER);

                    break;

                case 'resetExpDate':
                    $result = $this->resetExpDate($providerCode);

                    break;

                default:
                    $result = null;
            }

            if ($result) {
                $uidDebug = bin2hex(random_bytes(10));
                $this->messaging->publish($task->getResponseChannel(), ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => htmlspecialchars($result), 'uidDebug' => $uidDebug]);
            } else {
                $this->checkProvider($task->getResponseChannel(),
                    $operation, $providerCode,
                    $limit, $withBackgroundCheckOff,
                    $checkStart, $checkEnd);
            }
        } finally {
            $this->logger->info("done");
            $this->logger->popHandler();
        }

        return new Response();
    }

    private function resetHistoryCache($provider)
    {
        $this->connection->executeQuery(
            /** @lang MySQL */ "UPDATE Provider SET CacheVersion = CacheVersion + 1 WHERE Code = ?",
            [$provider], [\PDO::PARAM_STR]
        );
        $cacheVersion = $this->connection->executeQuery(/** @lang MySQL */ "select CacheVersion from Provider where Code = ?",
            [$provider], [\PDO::PARAM_STR])->fetchOne();

        $this->logger->info("updated provider cache version, provider: {$provider}, cache version now: {$cacheVersion}");
        \TProviderSchema::triggerDatabaseUpdate();

        return $this->twigTemplate->renderBlock('resetHistoryCache', []);
    }

    private function resetExpDate($provider)
    {
        $reset = null;
        $providerID = $this->connection->executeQuery(/** @lang MySQL */ "SELECT ProviderID FROM Provider WHERE Code = ?",
            [$provider], [\PDO::PARAM_STR])->fetchOne();

        if ($providerID !== false) {
            $st = $this->connection->executeQuery(/** @lang MySQL */ "UPDATE Account 
                    SET ExpirationDate = NULL, ExpirationAutoSet = ?
                    WHERE ProviderID = ?
                      AND (ExpirationAutoSet = ? OR ExpirationDate IS NOT NULL 
                                                        AND ExpirationAutoSet = ?)",
                [EXPIRATION_UNKNOWN, $providerID, EXPIRATION_AUTO, EXPIRATION_UNKNOWN],
                [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
            );
            $reset = $st->rowCount();
            \TProviderSchema::triggerDatabaseUpdate();
        }

        return $this->twigTemplate->renderBlock('resetExpDate', ['reset' => $reset]);
    }

    private function resetDisabledState($provider, $disableReason)
    {
        if ($disableReason == Account::DISABLE_REASON_USER) {
            $condition = "not(DisableReason is null OR DisableReason = " . $disableReason . ")";
        } else {
            $condition = "DisableReason = " . $disableReason;
        }
        $q = $this->connection->executeQuery(/** @lang MySQL */ "SELECT ProviderID FROM Provider WHERE Code = ?",
            [$provider], [\PDO::PARAM_STR]);

        $reset = null;

        if ($row = $q->fetchAssociative()) {
            $providerID = $row["ProviderID"];
            $st = $this->connection->executeQuery(/** @lang MySQL */
                "UPDATE Account SET ErrorCount = 0, Disabled = 0
                WHERE ProviderID = ?
                      AND ({$condition})
                      AND Disabled = 1", [$providerID], [\PDO::PARAM_INT]);
            $reset = $st->rowCount();
        }

        return $this->twigTemplate->renderBlock('resetDisabledState', ['reset' => $reset]);
    }

    private function checkProvider($channel, $operation, $provider, $limit, $checkingOff, $d1, $d2): void
    {
        $limit = (int) $limit;

        $filter = "";
        $canCheckItinerary = (int)
        $this->connection->executeQuery(/** @lang MySQL */ "select CanCheckItinerary from Provider where Code = ?",
            [$provider],
            [\PDO::PARAM_STR]
        )->fetchOne();
        $statuses = [
            ACCOUNT_INVALID_PASSWORD,
            ACCOUNT_LOCKOUT,
            ACCOUNT_PREVENT_LOCKOUT,
            ACCOUNT_CHECKED,
            ACCOUNT_WARNING,
            ACCOUNT_PROVIDER_ERROR,
            ACCOUNT_ENGINE_ERROR,
            ACCOUNT_QUESTION,
            ACCOUNT_PROVIDER_DISABLED,
            ACCOUNT_UNCHECKED,
        ];
        $withKilledAccounts = false;

        switch ($operation) {
            case 'checkProviderI':
                $statuses = [ACCOUNT_INVALID_PASSWORD];

                break;

            case 'checkProviderR':
                $statuses = [ACCOUNT_PROVIDER_ERROR];

                break;

            case 'checkProviderW':
                $statuses = [ACCOUNT_WARNING];

                break;

            case 'checkProvider':
                $statuses = [ACCOUNT_ENGINE_ERROR];
                $withKilledAccounts = true;

                break;

            case 'checkProviderE':
                $filter .= " and a.ExpirationDate is not null and a.ExpirationAutoSet < " . EXPIRATION_AUTO;

                break;

            case 'checkProviderT':
                if (!$canCheckItinerary) {
                    $uidDebug = bin2hex(random_bytes(10));
                    $this->messaging->publish($channel, ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => htmlspecialchars("ERROR: provider can't check itinerary"), 'uidDebug' => $uidDebug]);

                    return;
                }
                $filter .= " and a.Itineraries <> -1";

                break;

            case 'checkProviderTY':
                if (!$canCheckItinerary) {
                    $uidDebug = bin2hex(random_bytes(10));
                    $this->messaging->publish($channel, ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => htmlspecialchars("ERROR: provider can't check itinerary"), 'uidDebug' => $uidDebug]);

                    return;
                }
                $filter .= " and a.Itineraries > 0";

                break;

            case 'checkProviderTN':
                if (!$canCheckItinerary) {
                    $uidDebug = bin2hex(random_bytes(10));
                    $this->messaging->publish($channel, ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => htmlspecialchars("ERROR: provider can't check itinerary"), 'uidDebug' => $uidDebug]);

                    return;
                }
                $filter .= " and a.Itineraries = 0";

                break;

            case 'checkProviderS':
                $statuses = [ACCOUNT_CHECKED];

                break;

            case 'checkProviderSQ':
                $statuses = [ACCOUNT_QUESTION];

                break;

            case 'checkProviderN':
                $statuses = [ACCOUNT_CHECKED];
                $filter .= " and a.Balance is null";

                break;

            case 'checkProviderZB':
                $statuses = [ACCOUNT_CHECKED];
                $errDate = false;

                $d2 = strtotime('+1 day', $d2);

                if ($d1 >= $d2) {
                    $errDate = true;
                }
                $d1 = date('Y-m-d', $d1);
                $d2 = date('Y-m-d', $d2);

                if ($errDate) {
                    $uidDebug = bin2hex(random_bytes(10));
                    $this->messaging->publish($channel, ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => htmlspecialchars("ERROR: wrong period for check: [{$d1}:{$d2})"), 'uidDebug' => $uidDebug]);

                    return;
                }
                $filter .= " and (a.Balance = 0 or a.Balance < -1) and SuccessCheckDate >= '{$d1}' and SuccessCheckDate < '{$d2}'";

                break;
        }

        try {
            if ($operation === 'checkProviderF') {
                $limit = null;
            }

            $sql = $this->getSQL($provider, $filter, $statuses, $limit, $checkingOff);

            if ($withKilledAccounts || $operation === 'checkProviderF') {
                $providerId = (int)
                $this->connection->executeQuery(/** @lang MySQL */ "select ProviderID from Provider where Code = ?",
                    [$provider],
                    [\PDO::PARAM_STR]
                )->fetchOne();

                $l = $limit;
                $killedAccounts = $this->watchdogKillListProvider->search($l, $providerId);

                if (is_array($killedAccounts) && count($killedAccounts) > 0) {
                    $accounts = array_map(function ($el) {
                        return (int) $el['AccountID'];
                    }, $killedAccounts);
                    $accounts = implode(", ", $accounts);
                    $filter .= " and a.AccountID in({$accounts})";
                    $statuses = [
                        ACCOUNT_UNCHECKED,
                        ACCOUNT_CHECKED,
                        ACCOUNT_INVALID_PASSWORD,
                        ACCOUNT_LOCKOUT,
                        ACCOUNT_PROVIDER_ERROR,
                        ACCOUNT_PROVIDER_DISABLED,
                        ACCOUNT_ENGINE_ERROR,
                        ACCOUNT_PREVENT_LOCKOUT,
                        ACCOUNT_QUESTION,
                        ACCOUNT_WARNING,
                    ];
                    $sqlForKilled = $this->getSQL($provider, $filter, $statuses, $limit, $checkingOff);
                }
            }
        } catch (UserErrorException $e) {
            $uidDebug = bin2hex(random_bytes(10));
            $this->messaging->publish($channel, ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => "ERROR: " . $e->getMessage(), 'uidDebug' => $uidDebug]);

            return;
        }
        $this->logger->warning("operation query started", ["sql" => $sql, "page" => "operations"]);
        session_write_close();
        $q = $this->connection->executeQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $killedAcs = [];

        if (!empty($sqlForKilled)) {
            $killedAcs = $this->connection->executeQuery($sqlForKilled)->fetchAll(\PDO::FETCH_ASSOC);
            $uidDebug = bin2hex(random_bytes(10));
            $this->messaging->publish($channel,
                ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => 'sql for killed: ' . htmlspecialchars($sqlForKilled) . "<br/>", 'uidDebug' => $uidDebug]);
        }

        $diff = array_diff(array_map(function ($dat) {
            return $dat['AccountID'];
        }, $killedAcs), array_map(function ($data) {
            return $data['AccountID'];
        }, $q));

        if (count($q) < $limit) {
            $c = $limit - count($q);
            $counter = 0;

            foreach ($diff as $i => $killed) {
                if ($counter >= $c) {
                    break;
                }
                $q[] = $killedAcs[$i];
                $counter++;
            }
        }
        $uidDebug = bin2hex(random_bytes(10));
        $this->messaging->publish($channel, ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => htmlspecialchars($sql) . "<br/>", 'uidDebug' => $uidDebug]);

        $this->logger->warning("operation query finished", ["page" => "operations", 'uidDebug' => $uidDebug]);
        $checker = new \GroupWsdlCheckAccount($q);
        $checker->parseHistory = true;
        $checker->priority = 6;

        $logger = function ($obj, $event, $message) use ($channel) {
            $uidDebug = bin2hex(random_bytes(10));
            $this->messaging->publish($channel, ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => htmlspecialchars($message) . "<br/>", 'uidDebug' => $uidDebug]);
            $this->logger->warning($message, ["page" => "operations", 'uidDebug' => $uidDebug]);
        };
        $wsdlLogger = function ($obj, $event, $message) use ($channel) {
            $uidDebug = bin2hex(random_bytes(10));
            $this->messaging->publish($channel,
                ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => htmlspecialchars('[WSDL] ' . $message) . "<br/>", 'uidDebug' => $uidDebug]);
            $this->logger->warning($message, ["page" => "operations", 'uidDebug' => $uidDebug]);
        };
        $loyaltyLogger = function ($obj, $event, $message) use ($channel) {
            $uidDebug = bin2hex(random_bytes(10));
            $this->messaging->publish($channel,
                ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => htmlspecialchars('[Loyalty] ' . $message) . "<br/>", 'uidDebug' => $uidDebug]);
            $this->logger->warning($message, ["page" => "operations", 'uidDebug' => $uidDebug]);
        };
        $checker->addObserver($logger, \GroupWsdlCheckAccount::EVENT_END);

        $checker->addObserver($wsdlLogger, \GroupWsdlCheckAccount::EVENT_LOG);
        $checker->addObserver($wsdlLogger, \GroupWsdlCheckAccount::EVENT_ONE_COMPLETE);

        $checker->addObserver($loyaltyLogger, \GroupWsdlCheckAccount::LOYALTY_EVENT_ONE_COMPLETE);
        $checker->addObserver($loyaltyLogger, \GroupWsdlCheckAccount::LOYALTY_EVENT_LOG);
        $checker->addObserver($loyaltyLogger, \GroupWsdlCheckAccount::LOYALTY_EVENT_ERROR);

        // Check
        $checker->WSDLCheckAccounts(UpdaterEngineInterface::SOURCE_OPERATIONS);
        $uidDebug = bin2hex(random_bytes(10));
        $this->logger->warning("operations done", ["page" => "operations", 'uidDebug' => $uidDebug]);
        $this->messaging->publish($channel, ["dateTime" => $this->getCurrentDateTimeWithMS(), "type" => "logs", "message" => "done<br/>", 'uidDebug' => $uidDebug]);
        session_start();
    }

    private function getCurrentDateTimeWithMS(): string
    {
        return date("Y-m-d H:i:s.") . str_pad(gettimeofday()["usec"], 6, '0', STR_PAD_LEFT);
    }

    private function getSQL($provider, $filter, $statuses, $limit = null, $checkingOff = false)
    {
        global $arProviderState;
        //    $awPlusOnlyFilter = " and (p.State <> ".PROVIDER_CHECKING_AWPLUS_ONLY." or u.AccountLevel <> ".ACCOUNT_LEVEL_FREE.")";
        $awPlusOnlyFilter = "";

        if ($checkingOff) {
            if ($limit && $limit > 100) {
                $limit = 100;
            }

            // TODO: skywards gag
            if ($provider === 'skywards' && $limit && $limit > 7) {
                $limit = 7;
            }
        }

        $q = $this->connection->executeQuery(/** @lang MySQL */ "SELECT * FROM Provider WHERE Code = ?",
            [$provider])->fetch();

        if ($q['State'] < PROVIDER_ENABLED || $q['State'] == PROVIDER_CHECKING_EXTENSION_ONLY) {
            throw new UserErrorException("Invalid provider state: " . $arProviderState[$q["State"]]);
        }

        if (!$checkingOff && in_array($q['State'], [PROVIDER_CHECKING_OFF, PROVIDER_FIXING])) {
            throw new UserErrorException("Use 'With background check off'. Provider has state '" . $arProviderState[$q["State"]] . "'");
        }

        if ($q["CanCheck"] != 1) {
            throw new UserErrorException("CanCheck is false for this provider");
        }

        if ($provider === "aa") {
            $filter .= " and a.ErrorCode in (" . ACCOUNT_CHECKED . ", " . ACCOUNT_ENGINE_ERROR . ") and a.SuccessCheckDate > a.PassChangeDate and a.UpdateDate > '2014-04-16'";
        } elseif ($q["PasswordRequired"]) {
            $filter .= " and ((a.SavePassword = " . SAVE_PASSWORD_DATABASE . " and a.Pass <> '') or a.AuthInfo is not null)";
        }

        $sql = "select
		a.AccountID,
		a.ProviderID,
		5 as Priority,
		a.ErrorCode,
		a.SuccessCheckDate,
		a.PassChangeDate,
		a.UpdateDate,
		a.ModifyDate,
		a.PassChangeDate,
		'{$q['Code']}' as ProviderCode,
		a.SavePassword,
		'{$q['Code']}' as Code,
		a.Login,
  			a.Login2,
  			a.Login3,
  			a.Pass,
  			a.UserID,
  			a.BrowserState,
  			a.HistoryVersion,
  			(u.AutoGatherPlans = 1 AND {$q['CanCheckItinerary']} = 1) as AutoGatherPlans
	from
		Account a
		join Usr u on a.UserID = u.UserID
	where
	    a.ProviderID = {$q['ProviderID']}
		and a.ErrorCode in( " . implode(", ", $statuses) . " )
		and a.Disabled = 0
		$awPlusOnlyFilter
		$filter";

        if (isset($limit)) {
            $sql .= " LIMIT " . $limit;
        }

        return $sql;
    }
}
