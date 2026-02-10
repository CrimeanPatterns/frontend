<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Service\WatchdogKillListProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProviderErrorsController extends AbstractController
{
    private WatchdogKillListProvider $watchdogKillListProvider;
    private bool $debug;

    public function __construct(WatchdogKillListProvider $watchdogKillListProvider, bool $debug)
    {
        $this->watchdogKillListProvider = $watchdogKillListProvider;
        $this->debug = $debug;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_PROVIDERSTATUS')")
     * @Route("/manager/provider-errors/{providerId}", name="aw_manager_provider_errors")
     */
    public function index(
        Request $request,
        int $providerId
    ): Response {
        // Process request parameters
        $showWithErrors = (int) $request->query->get('KE', 0);
        $limit = (int) $request->query->get('L', 15);
        $hideKilled = (int) $request->query->get('HK', 0);

        if ($limit == 0 || $limit > 300) {
            $limit = 15;
        }

        // Get provider data
        $providerData = $this->getProviderData($providerId);

        // Get killed accounts
        $killedAccounts = $this->watchdogKillListProvider->search($limit, $providerId);

        // Get account errors
        $accountErrors = $this->getAccountErrors($providerId, $showWithErrors, $limit, $hideKilled, $killedAccounts);

        // Provider statistics
        $providerStatistics = $this->getProviderStatistics($providerId);

        // Additional data for the view
        $copyProviderCode = "<button style='font-size:60%; padding:8px 7px 4px 0; vertical-align:top; background:none; border:none; color:gray; cursor:pointer;' onclick='copyText(\"#providerCode\")'>&nbsp;❒ Copy</button>";

        // Determine external check indicator
        $extCheckIndicator = $this->getExtCheckIndicator($providerData);

        // Format display name
        $displayName = $providerData['DisplayName'];

        if (mb_strlen(html_entity_decode($displayName, ENT_QUOTES, "UTF-8")) > 40) {
            $displayName = mb_substr($displayName, 0, 40) . '...';
        }

        // Prepare assignee data
        $escapedDisplayName = $this->fixQuotes($providerData['DisplayName']);
        $assignee = $this->prepareAssigneeData($providerId, $providerData, $escapedDisplayName);

        global $arProviderKind, $SAVE_PASSWORD;

        return $this->render('@AwardWalletMain/Manager/ProviderStatus/providerErrors.html.twig', [
            'schema' => 'providerStatus',
            'providerId' => $providerId,
            'providerData' => $providerData,
            'killedAccounts' => $killedAccounts,
            'rows' => $accountErrors['rows'],
            'unknownErrors' => $accountErrors['unknownErrors'],
            'showWithErrors' => $showWithErrors,
            'hideKilled' => $hideKilled,
            'limit' => $limit,
            'showKilledLink' => $accountErrors['showKilledLink'],
            'showKilledText' => $accountErrors['showKilledText'],
            'showErrorsText' => $accountErrors['showErrorsText'],
            'providerStatistics' => $providerStatistics,
            'copyProviderCode' => $copyProviderCode,
            'extCheckIndicator' => $extCheckIndicator,
            'displayName' => $displayName,
            'assignee' => $assignee,
            'arProviderKind' => $this->getProviderKinds(),
            'arProviderState' => $this->getProviderStates(),
            'SAVE_PASSWORD' => $this->getSavePasswordTypes(),
            'arCheckedBy' => $this->getCheckedByTypes(),
            'ACCOUNT_DISABLED' => ACCOUNT_DISABLED,
            'ACCOUNT_ENGINE_ERROR' => ACCOUNT_ENGINE_ERROR,
            'ACCOUNT_CHECKED' => ACCOUNT_CHECKED,
            'CHECKED_BY_EMAIL' => Account::CHECKED_BY_EMAIL,
            'SAVE_PASSWORD_DATABASE' => SAVE_PASSWORD_DATABASE,
            'PROVIDER_CHECKING_EXTENSION_ONLY' => PROVIDER_CHECKING_EXTENSION_ONLY,
            'CHECK_IN_MIXED' => CHECK_IN_MIXED,
            'title' => 'Errors for provider ' . $providerData['Code'],
            'contentTitle' => '',
            'providerKindMap' => $arProviderKind,
            'savePasswordMap' => $SAVE_PASSWORD,
            'checkedByMap' => Account::CHECKED_BY_NAMES,
        ]);
    }

    private function getProviderData(int $providerId): array
    {
        $query = "
            SELECT 
                p.ProviderID, p.DisplayName, p.State, p.Code, p.Kind, p.InternalNote, p.WSDL, p.Assignee,
                p.AutoLogin, p.DeepLinking, p.CanCheckBalance, p.Corporate, p.CanCheckExpiration, p.CanCheckItinerary,
                p.CanCheckConfirmation, p.Tier, p.Severity, p.ResponseTime, p.Warning, u.Login as AssigneeLogin, p.StatePrev,
                p.CheckInBrowser, p.CheckInMobileBrowser, p.PasswordRequired
            FROM 
                Provider p 
                LEFT OUTER JOIN Usr u ON p.Assignee = u.UserID
            WHERE p.ProviderID = :providerId
        ";

        $statement = $this->getDoctrine()->getConnection()->prepare($query);
        $statement->execute(['providerId' => $providerId]);
        $result = $statement->fetch();

        if (!$result) {
            throw $this->createNotFoundException('Provider not found');
        }

        return $result;
    }

    private function getAccountErrors(int $providerId, int $showWithErrors, int $limit, int $hideKilled, array $killedAccounts): array
    {
        $em = $this->getDoctrine()->getManager();
        $connection = $em->getConnection();

        // First query for unknown errors
        $sql = "
            SELECT UpdateDate, AccountID, UserID, Login, Login2, ErrorCode, ErrorMessage, DebugInfo, Balance, SavePassword, CheckedBy,
                Login3, SuccessCheckDate, ExpirationDate, ExpirationAutoSet, SubAccounts, DisableExtension, DisableClientPasswordAccess, PassChangeDate, CreationDate
            FROM Account 
            WHERE ProviderID = :providerId
            AND State >= :accountDisabled
            AND ErrorCode = :accountEngineError
            AND CheckedBy <> :checkedByEmail
            AND UpdateDate > DATE_SUB(NOW(), INTERVAL 1 DAY)
            ORDER BY UpdateDate DESC
            LIMIT :limit
        ";

        $statement = $connection->prepare($sql);
        $statement->bindValue('providerId', $providerId);
        $statement->bindValue('accountDisabled', ACCOUNT_DISABLED);
        $statement->bindValue('accountEngineError', ACCOUNT_ENGINE_ERROR);
        $statement->bindValue('checkedByEmail', Account::CHECKED_BY_EMAIL);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll();
        $unknownErrors = count($rows);

        // Second query for known errors if needed
        if ((count($rows) < $limit) || $showWithErrors) {
            if ($showWithErrors) {
                $unknownErrorsLimit = $limit;
            } else {
                $unknownErrorsLimit = $limit - count($rows);
            }

            $sql = "
                SELECT UpdateDate, AccountID, UserID, Login, Login2, ErrorCode, ErrorMessage, DebugInfo, Balance, SavePassword, CheckedBy,
                    Login3, SuccessCheckDate, ExpirationDate, ExpirationAutoSet, SubAccounts, DisableExtension, DisableClientPasswordAccess, PassChangeDate, CreationDate
                FROM Account 
                WHERE ProviderID = :providerId
                AND State >= :accountDisabled
                AND ErrorCode <> :accountChecked AND ErrorCode <> :accountEngineError
                AND CheckedBy <> :checkedByEmail
                AND UpdateDate > DATE_SUB(NOW(), INTERVAL 1 DAY)
                ORDER BY UpdateDate DESC
                LIMIT :limit
            ";

            $statement = $connection->prepare($sql);
            $statement->bindValue('providerId', $providerId);
            $statement->bindValue('accountDisabled', ACCOUNT_DISABLED);
            $statement->bindValue('accountChecked', ACCOUNT_CHECKED);
            $statement->bindValue('accountEngineError', ACCOUNT_ENGINE_ERROR);
            $statement->bindValue('checkedByEmail', Account::CHECKED_BY_EMAIL);
            $statement->bindValue('limit', $unknownErrorsLimit, \PDO::PARAM_INT);
            $statement->execute();

            $unknownRows = $statement->fetchAll();

            if ($showWithErrors) {
                $rows = $unknownRows;
            } else {
                $rows = array_merge($rows, $unknownRows);
            }
        }

        // Handle killed accounts
        if ($hideKilled === 1) {
            $showKilledText = 'show';
        } else {
            $showKilledText = 'hide';
        }

        $hide = false;
        $allErrors = [];
        $unknownErrs = 0;
        $showKilledLink = "";

        if (is_array($killedAccounts) && count($killedAccounts) > 0) {
            $allErrors = $this->processKilledAccounts($killedAccounts, $rows, $limit);

            foreach ($allErrors as $row) {
                if ($hideKilled === 0 && ($row['ErrorMessage'] === 'Killed' || (int) $row['ErrorCode'] === ACCOUNT_ENGINE_ERROR)) {
                    $hide = true;
                    $unknownErrs++;
                }
            }

            if ($hideKilled === 1) {
                $hide = true;
            }
        }

        if ($hide) {
            $showKilledLink = "<a href=\"/manager/provider-errors/{$providerId}?HK=" . ($hideKilled === 0 ? 1 : 0) . "&L={$limit}\">$showKilledText killed</a> / ";
        }

        if ($hideKilled === 0 && count($allErrors) > 0 && $showWithErrors === 0) {
            $rows = $allErrors;
            $unknownErrors = $unknownErrs;
        }

        if ($showWithErrors === 1) {
            $unknownErrors = 0;
        }

        // Determine show errors text
        $showErrorsText = 'known errors';

        if ($showWithErrors) {
            $showErrorsText = 'all errors';
        }

        foreach ($rows as &$row) {
            // hide some debug info
            // workaround extDebuginfo -> src/wsdl/WsdlService.php line 1173
            if (stristr($row['DebugInfo'], 'ParseItineraries')) {
                $row['DebugInfo'] = preg_replace('/\,?\s*ParseItineraries:\s*\d+\,\s*ParseHistory:\s*\d+/', "", $row['DebugInfo']);
            }

            if (strpos($row['DebugInfo'], "\n") !== false) {
                $row['DebugInfo'] = "<ul><li>" . str_replace(["\n", "<li></li>", "/www/loyalty/current/src/AppBundle/Engine/", "/www/loyalty/current/vendor/awardwallet/", "/www/loyalty/current/vendor/facebook/"], ["</li>\n<li>", "", "", "", ""], $row['DebugInfo']) . "</li></ul>";

                $replace = [
                    ',"profile.managed_default_content_settings.images"' => ', "profile.managed_default_content_settings.images"',
                    ',"download.default_directory"' => ', "download.default_directory"',
                    ',"profile.password_manager_enabled"' => ', "profile.password_manager_enabled"',
                    ',"webrtc.nonproxied_udp_enabled"' => ', "webrtc.nonproxied_udp_enabled"',
                    ',"webrtc.multiple_routes_enabled"' => ', "webrtc.multiple_routes_enabled"',
                    ',"credentials_enable_service"' => ', "credentials_enable_service"',
                    ',"profile.default_content_settings.popups"' => ', "profile.default_content_settings.popups"',
                ];
                $row['DebugInfo'] = str_replace(array_keys($replace), array_values($replace), $row['DebugInfo']);
            }

            if ($row['ErrorMessage'] !== 'Killed' && strpos($row['DebugInfo'], "<li></li>") !== false) {
                $row['DebugInfo'] = '';
            }

            if (!empty($row['DebugInfo'])) {
                $replace = [
                    "/\"firefox_profile\":\"([^\"$]+)/ms" => '"firefox_profile":" ... ',
                    '/"profile"\s*:\s*"([^\"]+)/ms' => '"profile": ...',
                    "/\"extensions\":\s*\[\"([^]]+)/ms" => '"extensions":[" ... ',
                ];
                $row['DebugInfo'] = preg_replace(array_keys($replace), array_values($replace), $row['DebugInfo']);

                // reduce logs in DebugInfo with "ThrottledException: ..."
                preg_match_all("#ThrottledException:\s*Throttled at service\/old\/browser\/RequestThrottler\.php:59#ims", $row['DebugInfo'], $throttledExceptions);
                $throttledExceptionCount = count($throttledExceptions[0]);

                if ($throttledExceptionCount > 1) {
                    for ($throttledExceptionIndex = 0; $throttledExceptionIndex < 5; $throttledExceptionIndex++) {
                        preg_match_all("#ThrottledException:\s*Throttled at service\/old\/browser\/RequestThrottler\.php:59, try {$throttledExceptionIndex}#ims", $row['DebugInfo'], $exceptionCounts);
                        $exceptionCount = count($exceptionCounts[0]);

                        if ($exceptionCount > 1) {
                            $row['DebugInfo'] = preg_replace("#ThrottledException:\s*Throttled at service\/old\/browser\/RequestThrottler\.php:59, try {$throttledExceptionIndex}, delay: \d+ sec#ims", "ThrottledException: Throttled at service/old/browser/RequestThrottler.php:59, try {$throttledExceptionIndex} - <strong>x{$exceptionCount}</strong>", $row['DebugInfo'], 1);
                            $row['DebugInfo'] = preg_replace("#<li>\s*ThrottledException:\s*Throttled at service\/old\/browser\/RequestThrottler\.php:59, try {$throttledExceptionIndex}, delay: \d+ sec</li>#ims", "", $row['DebugInfo']);
                        }// if ($exceptionCount > 1)
                    }// for ($throttledExceptionIndex = 0; $throttledExceptionIndex < 5; $throttledExceptionIndex++)
                }// if ($throttledExceptionCount > 0)
                // reduce logs in DebugInfo with "ThrottledException: ..."
            }
        }

        return [
            'rows' => $rows,
            'unknownErrors' => $unknownErrors,
            'showKilledLink' => $showKilledLink,
            'showKilledText' => $showKilledText,
            'showErrorsText' => $showErrorsText,
        ];
    }

    private function processKilledAccounts(array $killedAccounts, array $rows, int $limit): array
    {
        $killedAccountIds = [];

        foreach ($killedAccounts as &$killedAccount) {
            $killedAccount['ErrorCode'] = null;
            $killedAccount['ErrorMessage'] = "Killed";
            $killedAccountIds[] = $killedAccount['AccountID'];
        }

        $otherErrors = [];
        $unknownAndKilledErrors = $killedAccounts;

        foreach ($rows as $row) {
            if (!in_array($row['AccountID'], $killedAccountIds) && ((int) $row['ErrorCode'] === ACCOUNT_ENGINE_ERROR || $row['ErrorMessage'] === 'Killed')) {
                $unknownAndKilledErrors[] = $row;
            } elseif ((int) $row['ErrorCode'] !== ACCOUNT_ENGINE_ERROR && $row['ErrorMessage'] !== 'Killed') {
                $otherErrors[] = $row;
            }
        }

        foreach ($unknownAndKilledErrors as $key => $row) {
            $updateDate[$key] = strtotime($row['UpdateDate']);
        }

        array_multisort($updateDate, SORT_DESC, $unknownAndKilledErrors);
        $allErrors = array_merge($unknownAndKilledErrors, $otherErrors);
        $allErrors = array_slice($allErrors, 0, $limit);

        return $allErrors;
    }

    private function getProviderStatistics(int $providerId): string
    {
        // Get accounts from watchdog kill list for this provider
        $response = [];
        $response[$providerId] = $this->watchdogKillListProvider->search(null, $providerId);
        $killedAccounts = [];
        $killedSql = "";
        $dateUtc = strtotime(gmdate('d M Y H:i:s'));
        $lastHourDate = strtotime('-1 hour', $dateUtc);
        $killedAccountsForLastHour = [];

        foreach ($response as $provId => $accounts) {
            foreach ($accounts as $account) {
                if (!isset($account['AccountID'])) {
                    continue;
                }
                $killedAccounts[] = $account['AccountID'];

                if (strtotime($account['UpdateDate']) >= $lastHourDate) {
                    if (!isset($killedAccountsForLastHour[$provId])) {
                        $killedAccountsForLastHour[$provId] = 1;
                    } else {
                        $killedAccountsForLastHour[$provId]++;
                    }
                }
            }
        }

        if (count($killedAccounts) > 0) {
            $killedSql = "AND a.AccountID NOT IN(" . implode(", ", $killedAccounts) . ")";
        }

        // Build the SQL query
        $sql = "
        select
            a.ProviderID,
            count(a.AccountID) as TotalCount,
            sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " then 1 else 0 end) AS UnkErrors,
            sum(case when a.AccountID is not null and a.UpdateDate > adddate(now(), interval -4 hour) then 1 else 0 end) AS LastChecked,
            sum(case when a.AccountID is not null and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourChecked,
            sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " and a.UpdateDate > adddate(now(), interval -4 hour) then 1 else 0 end) AS LastUnkErrors,
            sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourUnkErrors,
            sum(case when a.ErrorCode = " . ACCOUNT_CHECKED . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourSuccessfullyChecked,
            sum(case when a.ErrorCode = " . ACCOUNT_PROVIDER_ERROR . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourProviderErrors,
            sum(case when a.ErrorCode = " . ACCOUNT_INVALID_PASSWORD . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourInvalidPassword,
            sum(case when a.ErrorCode in (" . ACCOUNT_LOCKOUT . ", " . ACCOUNT_PREVENT_LOCKOUT . ") and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourLockouts,
            sum(case when a.ErrorCode = " . ACCOUNT_WARNING . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourWarnings,
            sum(case when a.ErrorCode = " . ACCOUNT_QUESTION . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourQuestions,
            sum(case when a.ErrorCode <> " . ACCOUNT_CHECKED . " then 1 else 0 end) AS Errors,
            sum(case when a.ErrorCode = " . ACCOUNT_CHECKED . " then 1 else 0 end) AS SuccessfullyChecked,
            sum(case when a.ErrorCode = " . ACCOUNT_PROVIDER_ERROR . " then 1 else 0 end) AS ProviderErrors,
            sum(case when a.ErrorCode = " . ACCOUNT_INVALID_PASSWORD . " then 1 else 0 end) AS InvalidPassword,
            sum(case when a.ErrorCode in (" . ACCOUNT_LOCKOUT . ", " . ACCOUNT_PREVENT_LOCKOUT . ") then 1 else 0 end) AS Lockouts,
            sum(case when a.ErrorCode = " . ACCOUNT_WARNING . " then 1 else 0 end) AS Warnings,
            sum(case when a.ErrorCode = " . ACCOUNT_QUESTION . " then 1 else 0 end) AS Questions,
            sum(case when a.ErrorCode <> " . ACCOUNT_CHECKED . " and a.UpdateDate > adddate(now(), interval -1 hour) then 1 else 0 end) AS LastHourErrors,
            round(sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " then 1 else 0 end)/count(a.AccountID)*100, 2) AS ErrorRate,
            round(sum(case when a.ErrorCode = " . ACCOUNT_CHECKED . " then 1 else 0 end)/count(a.AccountID)*100, 2) AS SuccessRate
        from
            Account a
        where
            a.ProviderID = {$providerId}
            and a.CheckedBy <> " . Account::CHECKED_BY_EMAIL . "
            and a.UpdateDate > date_sub(now(), interval 1 day)
            and a.State not in (" . implode(', ', [ACCOUNT_PENDING, ACCOUNT_IGNORED]) . ")
            {$killedSql}
        group by
            a.ProviderID
        ";

        // Check if we're in debug mode
        if ($this->debug) {
            $sql = str_replace(['1 HOUR', '4 HOUR', '1 DAY'], ['5000 HOUR', '5000 HOUR', '180 DAY'], $sql);
        }

        // Execute the query with parameters
        $stmt = $this->getDoctrine()->getConnection()->prepare($sql);
        $stmt->bindValue('providerId', $providerId);
        $result = $stmt->executeQuery();

        $rows = $result->fetchAllAssociative();

        // Process the query results
        if (count($rows) > 0) {
            $row = $rows[0];

            // Add killed accounts to UnkErrors count
            if (isset($response[$row['ProviderID']]) && count($response[$row['ProviderID']]) > 0) {
                $row['UnkErrors'] += count($response[$row['ProviderID']]);
            }
        } else {
            // Create empty row with default values
            $row = [
                'ProviderID' => $providerId,
                // last hour
                'LastHourErrors' => 0,
                'LastHourProviderErrors' => 0,
                'LastHourInvalidPassword' => 0,
                'LastHourLockouts' => 0,
                'LastHourQuestions' => 0,
                'LastHourWarnings' => 0,
                'LastHourUnkErrors' => 0,
                'LastHourSuccessfullyChecked' => 0,
                'LastHourChecked' => 0,
                // total
                'TotalCount' => 0,
                'SuccessfullyChecked' => 0,
                'SuccessRate' => 0,
                'UnkErrors' => 0,
                'ProviderErrors' => 0,
                'InvalidPassword' => 0,
                'Lockouts' => 0,
                'Questions' => 0,
                'Warnings' => 0,
            ];
        }

        // Calculate killed accounts
        $killedAccs = 0;

        if (isset($response[$row['ProviderID']])) {
            $killedAccs = count($response[$row['ProviderID']]);
        }

        // Calculate last hour counts
        $lastHourCount = intval($row["LastHourUnkErrors"]);
        $total = intval($row["LastHourErrors"]);
        $killedForLastHour = 0;

        if (isset($killedAccountsForLastHour[$row['ProviderID']])) {
            $lastHourCount += $killedAccountsForLastHour[$row['ProviderID']];
            $total += $killedAccountsForLastHour[$row['ProviderID']];
            $killedForLastHour = $killedAccountsForLastHour[$row['ProviderID']];
        }

        // Prepare data for the template
        $statistics = [
            'providerId' => $row['ProviderID'],
            'successRate' => $row["SuccessRate"],
            'lastHour' => [
                'total' => $total,
                'providerErrors' => intval($row["LastHourProviderErrors"]),
                'invalidCredentials' => intval($row["LastHourInvalidPassword"]),
                'accountLockouts' => intval($row["LastHourLockouts"]),
                'questions' => intval($row["LastHourQuestions"]),
                'warnings' => intval($row["LastHourWarnings"]),
                'unknownErrors' => intval($row["LastHourUnkErrors"]),
                'killed' => $killedForLastHour,
                'successfullyChecked' => intval($row["LastHourSuccessfullyChecked"]),
                'totalChecked' => intval($row["LastHourChecked"]),
            ],
            'total' => [
                'checked' => intval($row["TotalCount"]),
                'successfullyChecked' => intval($row["SuccessfullyChecked"]),
                'unknownErrors' => intval($row["UnkErrors"]),
                'providerErrors' => intval($row["ProviderErrors"]),
                'invalidCredentials' => intval($row["InvalidPassword"]),
                'accountLockouts' => intval($row["Lockouts"]),
                'questions' => intval($row["Questions"]),
                'warnings' => intval($row["Warnings"]),
                'killed' => $killedAccs,
            ],
        ];

        // Generate the HTML for statistics
        return $this->renderView('@AwardWalletMain/Manager/ProviderStatus/statistics.html.twig', [
            'statistics' => $statistics,
        ]);
    }

    private function getEmptyStatisticsRow(int $providerId): array
    {
        return [
            'ProviderID' => $providerId,
            // last hour
            'LastHourErrors' => 0,
            'LastHourProviderErrors' => 0,
            'LastHourInvalidPassword' => 0,
            'LastHourLockouts' => 0,
            'LastHourQuestions' => 0,
            'LastHourWarnings' => 0,
            'LastHourUnkErrors' => 0,
            'LastHourSuccessfullyChecked' => 0,
            'LastHourChecked' => 0,
            // total
            'TotalCount' => 0,
            'SuccessfullyChecked' => 0,
            'SuccessRate' => 0,
            'UnkErrors' => 0,
            'ProviderErrors' => 0,
            'InvalidPassword' => 0,
            'Lockouts' => 0,
            'Questions' => 0,
            'Warnings' => 0,
        ];
    }

    private function getExtCheckIndicator(array $providerData): string
    {
        if ($providerData['CheckInBrowser'] == CHECK_IN_MIXED || $providerData['CheckInMobileBrowser'] == 1) {
            $extCheckText = '';

            if ($providerData['CheckInBrowser'] == CHECK_IN_MIXED && $providerData['CheckInMobileBrowser'] == 1) {
                $extCheckText = 'desktop + mobile';
            } elseif ($providerData['CheckInBrowser'] == CHECK_IN_MIXED) {
                $extCheckText = 'desktop';
            } elseif ($providerData['CheckInMobileBrowser'] == 1) {
                $extCheckText = 'mobile';
            }

            return "<i id='icon-ext' title='{$extCheckText}'></i>";
        }

        return '';
    }

    private function prepareAssigneeData(int $providerId, array $providerData, string $escapedDisplayName): string
    {
        $statePrev = empty($providerData["StatePrev"]) ? '' : $this->getProviderStates()[$providerData["StatePrev"]];

        $assignee = " <a class='clear' stateprev='{$statePrev}' style='display: none' href='#' onclick=\"setAssignee({$providerId}, '', this.parentNode, '{$escapedDisplayName}'); return false;\"><img src='/images/manager/fixed.png' title='Mark as fixed' style='float:left;padding-right:5px' height='20px'></a>";
        $assignee .= " <a class='set' style='display: none' href='#' onclick=\"setAssignee({$providerId}, {$this->getUser()->getId()}, this.parentNode, '{$escapedDisplayName}'); return false;\">Mark as broken</a>";
        $assignee .= "<span class='name' style='display: none' userId='{$providerData['Assignee']}'>Assignee: <span>{$providerData['AssigneeLogin']}</span></span>";

        return $assignee;
    }

    private function fixQuotes(string $text): string
    {
        return str_replace(["'", '"'], ["\'", '\"'], $text);
    }

    private function getProviderKinds(): array
    {
        // This should be loaded from a service or parameter
        return [
            // Provider kinds mapping
            1 => 'FirstKind',
            2 => 'SecondKind',
            // etc.
        ];
    }

    private function getProviderStates(): array
    {
        // This should be loaded from a service or parameter
        return [
            // Provider states mapping
            1 => 'Enabled',
            2 => 'Disabled',
            3 => 'Fixing',
            // etc.
        ];
    }

    private function getSavePasswordTypes(): array
    {
        // This should be loaded from a service or parameter
        return [
            // Save password types mapping
            1 => 'Local',
            2 => 'Database',
            // etc.
        ];
    }

    private function getCheckedByTypes(): array
    {
        // This should be loaded from a service or parameter
        return [
            // Checked by types mapping
            1 => 'User',
            2 => 'Extension',
            3 => 'Email',
            // etc.
        ];
    }
}
