<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Providerproperty as EntityProviderproperty;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ExpirationDateResolver;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ProgramStatusResolver;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Security\Voter\SessionVoter;
use AwardWallet\MainBundle\Service\BalanceFormatter;
use AwardWallet\MainBundle\Service\BalanceWatch\Timeout;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DesktopListMapper extends Mapper
{
    public const SUBACCOUNT_FICO_KEYWORD = 'FICO';

    protected const DATA_TEMPLATE = [
        'ID' => null,
        'FID' => null,
        'ProviderID' => null,
        'TableName' => null,
        'State' => null,
        'isCustom' => null,
        'AccountOwner' => null,
        'Kind' => null,
        'DisplayName' => null,
        'DisplayNameFormated' => null,
        'SavePassword' => null,
        'Balance' => null,
        'BalanceRaw' => null,
        'TotalBalance' => null,
        'TotalBalanceChange' => null,
        'USDCash' => null,
        'USDCashRaw' => null,
        'USDCashMileValue' => null,
        'TotalUSDCash' => null,
        'TotalUSDCashRaw' => null,
        'TotalUSDCashChange' => null,
        'LastChange' => null,
        'LastChangeRaw' => null,
        'ChangesConfirmed' => null,
        'ChangedText' => null,
        'LastChangeDate' => null,
        'LastChangeDateTs' => null,
        'ChangeCount' => null,
        'ChangedOverPeriodPositive' => null,
        'AllianceAlias' => null,
        'AllianceIcon' => null,
        'AllianceLevel' => null,
        'EliteStatuses' => null,
        'EliteLevelsCount' => null,
        'Elitism' => null,
        'Rank' => null,
        'ExpirationDate' => null,
        'ExpirationDateYMD' => null,
        'ExpirationDateTs' => null,
        'ExpirationDateTip' => null,
        'ExpirationKnown' => null,
        'ExpirationState' => null,
        'ExpirationStateType' => null,
        'ExpirationMode' => null,
        'ExpirationModeType' => null,
        'ExpirationDetails' => null,
        'ExpirationBlogPost' => null,
        'ErrorCode' => null,
        'AutoLogin' => null,
        'LoginURL' => null,
        'DisableClientPasswordAccess' => null,
        'Goal' => null,
        'GoalProgress' => null,
        'GoalIndicators' => null,
        'UpdateDate' => null,
        'UpdateDateTs' => null,
        'SubAccountsArray' => null,
        'Access' => null,
        'Shares' => null,
        'ProgramMessage' => null,
        'ProviderCode' => null,
        'ProviderCurrency' => null,
        'ProviderCurrencies' => null,
        'ProviderManualUpdate' => null,
        'CheckInBrowser' => null,
        // Login cell in account/list (First line, last line)
        'LoginFieldFirst' => null,
        'LoginFieldLast' => null,
        // Status (from main properties)
        'AccountStatus' => null,
        'ConnectedAccount' => null,
        'StatusIndicators' => null,
        'StateBar' => null,
        'LastDurationWithoutPlans' => null,
        'LastDurationWithPlans' => null,
        'HasCurrentTrips' => null,
        'IsActiveTab' => null,
        'IsArchived' => null,
        'SuccessCheckDateTs' => null,
        'SuccessCheckDateYMD' => null,
        'LastUpdatedDateTs' => null,
        'UserEmail' => null,
        'FamilyMemberName' => null,
        'EmailDate' => null,
        'EmailDateTs' => null,
        'CanSavePassword' => null,
        'PasswordRequired' => null,
        'CanReceiveEmail' => null,
        'HistoryName' => null,
        'CanCheck' => null,
        'IsActive' => null,
        'IsShareable' => null,
        'Disabled' => null,
        'DisableBackgroundUpdating' => null,
        'DetectedCards' => null,
        'BackgroundCheck' => null,
        'KeyWords' => null,
        'CardImages' => [
            'ProviderID' => null,
            'AccountID' => null,
            'SubAccountID' => null,
            'CardImageID' => null,
            'UserID' => null,
            'ProviderCouponID' => null,
            'Kind' => null,
            'Width' => null,
            'Height' => null,
            'FileName' => null,
            'FileSize' => null,
            'Format' => null,
            'UploadDate' => null,
            'StorageKey' => null,
        ],
        'DocumentImages' => null,
        'BackgroundCheckState' => null,
        'BalanceWatchExpirationDate' => null,
        'BalanceWatchStartDate' => null,
        'CustomFields' => null,
        'PwnedTimes' => null,
        'MileValue' => null,
        'Complex' => null,
    ];
    /**
     * @var CacheManager
     */
    private $cacheManager;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        ProviderTranslator $providerTranslator,
        LocalizeService $localizer,
        SessionVoter $sessionVoter,
        DateTimeIntervalFormatter $intervalFormatter,
        ExpirationDateResolver $expirationResolver,
        ProgramStatusResolver $statusResolver,
        CacheManager $cacheManager,
        BalanceFormatter $balanceFormatter,
        Timeout $bwTimeout,
        UserMailboxCounter $userMailboxCounter,
        PropertyFormatter $propertyFormatter,
        ClockInterface $clock,
        MileValueCards $mileValueCards,
        MileValueService $mileValueService
    ) {
        parent::__construct(
            $em,
            $translator,
            $providerTranslator,
            $localizer,
            $sessionVoter,
            $intervalFormatter,
            $expirationResolver,
            $statusResolver,
            $balanceFormatter,
            $bwTimeout,
            $userMailboxCounter,
            $propertyFormatter,
            $clock,
            $mileValueCards,
            $mileValueService
        );

        $this->cacheManager = $cacheManager;
    }

    public function alterTemplate(MapperContext $mapperContext)
    {
        parent::alterTemplate($mapperContext);

        $mapperContext->alterDataTemplateBy(static::DATA_TEMPLATE);
    }

    public function map(MapperContext $mapperContext, $accountID, $accountFields, $accountsIds)
    {
        $accountFields = parent::map($mapperContext, $accountID, $accountFields, $accountsIds);

        if (isset($accountFields['SubAccountsArray']) && is_array($accountFields['SubAccountsArray']) && isset($accountFields['Complex'])) {
            usort($accountFields['SubAccountsArray'], function (array $subAccount1, array $subAccount2) {
                return [
                    isset($subAccount2['Unhideable']) && $subAccount2['Unhideable'],
                    $subAccount1['DisplayName'],
                ] <=> [
                    isset($subAccount1['Unhideable']) && $subAccount1['Unhideable'],
                    $subAccount2['DisplayName'],
                ];
            });
        }

        return $accountFields;
    }

    protected function mapAccount(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::mapAccount($mapperContext, $accountID, $accountFields);

        $accountFields['Disabled'] = (bool) $accountFields['Disabled'];
        $accountFields['DisableBackgroundUpdating'] = (bool) $accountFields['DisableBackgroundUpdating'];

        if ($accountFields['Disabled']) {
            $accountFields['StateBar'] = 'disabled';
        } elseif ($accountFields['ErrorCode'] == ACCOUNT_WARNING) {
            $accountFields['StateBar'] = 'warning';
        }

        if (isset($accountFields['ExpirationDetails'])
            && !$accountFields['isCustom']
            && isset($accountFields['Blogs']['BlogIdsMileExpiration'])
        ) {
            $firstPost = reset($accountFields['Blogs']['BlogIdsMileExpiration']);
            $note = $this->translator->trans(/** @Desc("Here is how you can keep your %programName% %currency% from expiring") */ 'account.expire.note.blog-link', [
                '%programName%' => $accountFields['DisplayName'],
                '%currency%' => $accountFields['ProviderCurrencyName'],
            ]);
            $accountFields['ExpirationBlogPost'] = [
                'Text' => $note,
                'Link' => $firstPost['postURL'],
                'Image' => $firstPost['imageURL'] ?? null,
            ];
        }

        return $accountFields;
    }

    protected function filter(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::filter($mapperContext, $accountID, $accountFields);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);

        // Alliance icon
        if (in_array($accountFields['Kind'], [PROVIDER_KIND_AIRLINE, PROVIDER_KIND_TRAIN])
            && isset($accountFields['AllianceAlias'])
            && !empty($accountFields['AllianceAlias'])) {
            $accountFields['AllianceLevel'] = $this->getAllianceLevel($accountFields);
            $accountFields['AllianceIcon'] = $this->getAllianceIcon($accountFields);
        }

        // Intval balance (for sort)
        if (!isset($accountFields['BalanceRaw']) || empty($accountFields['BalanceRaw'])) {
            $accountFields['BalanceRaw'] = -1;
        }

        if (isset($accountFields["SubAccountsArray"]) && is_array($accountFields["SubAccountsArray"])) {
            foreach ($accountFields['SubAccountsArray'] as $k => &$subAccount) {
                if (!isset($subAccount['BalanceRaw']) || empty($subAccount['BalanceRaw'])) {
                    $subAccount['BalanceRaw'] = -1;
                }
            }
        }

        // Login column in account/list
        // If exist comment - number + comment
        // If not exist number and exist comment - login + comment
        // If not exist comment - login + number (if exist)
        if ($accountFields['TableName'] == 'Account') {
            $accountFields['MainProperties']['Login'] = htmlentities($accountFields['MainProperties']['Login']);

            if (isset($accountFields['State'])) {
                $accountFields['State'] = intval($accountFields['State']);
            }

            if (isset($accountFields['comment']) && !empty($accountFields['comment'])) {
                $accountFields['LoginFieldFirst'] = $accountFields['MainProperties']['Login'];
                $accountFields['LoginFieldLast'] = $accountFields['comment'];
            } else {
                $accountFields['LoginFieldFirst'] = htmlentities($accountFields['Login']);

                if (isset($accountFields['MainProperties']['Number'])
                    && str_replace(' ', '', $accountFields['MainProperties']['Number']['Number']) != str_replace(' ', '', $accountFields['LoginFieldFirst'])) {
                    $accountFields['LoginFieldLast'] = $accountFields['MainProperties']['Number']['Number'];
                }
            }
        } else {
            $accountFields['MainProperties']['Login'] = $accountFields['LoginFieldFirst'];

            if (array_key_exists($accountFields['TypeID'], Providercoupon::DOCUMENT_TYPES)) {
                $typeId = (int) $accountFields['TypeID'];
                $accountFields['MainProperties']['Type'] = Providercoupon::DOCUMENT_TYPES[$typeId];
            } else {
                $accountFields['MainProperties']['Type'] = $accountFields['AccountStatus'];
            }

            if (0 === strlen($accountFields['LoginFieldFirst']) && !empty($accountFields['Description'])) {
                $accountFields['LoginFieldFirst'] = $accountFields['Description'];
            }

            if (empty($accountFields['LoginFieldLast']) && 'Coupon' != $accountFields['TableName']) { // refs #14378 - show in Balance column
                $accountFields['LoginFieldLast'] = $accountFields['Value'];
            }
        }

        if ($accountFields['LoginURL']) {
            $url = parse_url($accountFields['LoginURL']);

            if ($url === false) {
                $accountFields['LoginURL'] = null;
            } else {
                if (!isset($url['scheme'])) {
                    $accountFields['LoginURL'] = 'http://' . $accountFields['LoginURL'];
                }
            }
        }

        if (!empty($accountFields['DisableClientPasswordAccess'])) {
            $accountFields['AutoLogin'] = AUTOLOGIN_DISABLED;
            $accountFields['DisableClientPasswordAccess'] = true;
            $accountFields['Access']['autologinExtension'] = false;
        }

        // Add "*" - disable autologin

        if (!empty($accountFields['ProviderID'])) {
            $accountFields['DisplayName'] = $this->providerTranslator->translateDisplayNameByScalars(
                $accountFields['ProviderID'],
                $accountFields['DisplayName'],
                $locale
            );
        }
        $accountFields['DisplayNameFormated'] = $accountFields['DisplayName'];
        //        if ($accountFields['TableName'] == 'Account' && $accountFields['AutoLogin'] == AUTOLOGIN_DISABLED && $accountFields['Access']['autologin']) $accountFields['DisplayNameFormated'] .= '&nbsp;<span data-tip title="'.$this->translator->trans("award.account.list.autologin-disable.tip").'">*</span>';
        // Add soft break <wbr>

        $accountFields['BackgroundCheckState'] = $this->calcBackgroundCheckState(
            $mapperContext->options->get(Options::OPTION_USER),
            $accountFields,
            $locale
        );

        $accountFields['DisplayNameFormated'] = preg_replace(
            "/^([^\(]+)(\(.+)$/ims",
            "<span style='white-space:nowrap'>$1</span><wbr><span style='white-space:nowrap'>$2</span>",
            $accountFields['DisplayNameFormated']
        );

        // Goal progress
        // 10 indicators
        if (isset($accountFields['GoalProgress'])) {
            $ind = $accountFields['GoalProgress'] / 10;

            if ($ind > 9 && $ind < 10) {
                $ind = 9;
            } else {
                $ind = ceil($ind);
            }
            $accountFields['GoalIndicators'] = $ind;
        }

        if (isset($accountFields["SubAccountsArray"]) && is_array($accountFields["SubAccountsArray"])) {
            foreach ($accountFields['SubAccountsArray'] as $k => &$subAccount) {
                if (isset($subAccount['GoalProgress'])) {
                    $ind = $subAccount['GoalProgress'] / 10;

                    if ($ind > 9 && $ind < 10) {
                        $ind = 9;
                    } else {
                        $ind = ceil($ind);
                    }
                    $subAccount['GoalIndicators'] = $ind;
                }
            }
        }

        // Status progress
        if (isset($accountFields['EliteLevelsCount'], $accountFields['Rank']) && $accountFields['EliteLevelsCount'] > 0) {
            $accountFields['StatusIndicators'] =
                ($accountFields['Rank'] > $accountFields['EliteLevelsCount'])
                    ? (int) $accountFields['EliteLevelsCount'] : (int) $accountFields['Rank'];
        }

        if (
            !$mapperContext->options->get(Options::OPTION_USER)->isBusiness()
            && isset($accountFields['Access'])
            && isset($accountFields['Access']['edit'])
            && $accountFields['Access']['edit']
            && isset($accountFields['ChangesConfirmed'])
            && isset($accountFields['BalanceRaw'])
            && isset($accountFields['LastChangeRaw'])
        ) {
            $accountFields['ChangesConfirmed'] = (bool) $accountFields['ChangesConfirmed'];

            $totalBalance = $accountFields['BalanceRaw'];

            $lastBalance = $accountFields['BalanceRaw'] - $accountFields['LastChangeRaw'];
            $changeRaw = $accountFields['LastChangeRaw'];
            $accountFields['ChangedText'] = $this->translator->trans('award.account.list.updating.changed', [
                '%lastBalance%' => $this->localizer->formatNumber(round($lastBalance, 2), 2, $locale),
                '%balance%' => $this->localizer->formatNumber(round($totalBalance, 2), 2, $locale),
                '%lastChange%' => ($changeRaw > 0 ? '+' : '') . $this->localizer->formatNumber(round($changeRaw, 2), 2, $locale),
                '%changeClass%' => $changeRaw > 0 ? 'green' : 'blue',
            ], 'messages', $locale);
        } else {
            $accountFields['ChangesConfirmed'] = true;
        }

        // Scan history
        if ($accountFields['TableName'] == 'Account') {
            if (isset($accountFields['ParsedJson'])) {
                $parsed = json_decode($accountFields['ParsedJson']);

                if (is_object($parsed) && property_exists($parsed, 'Name')) {
                    $accountFields['HistoryName'] = $parsed->Name;
                }
            }
        }

        // CanCheck
        if (isset($accountFields['CanCheck'])) {
            $accountFields['CanCheck'] = intval($accountFields['CanCheck']);
        }

        // Credit card icon
        if (isset($accountFields['MainProperties']['DetectedCards'])) {
            $accountFields['DetectedCards'] = true;
        }

        // Expiration date (Time ago format)
        if ($accountFields['ExpirationDate'] && !in_array($accountFields['ExpirationStateType'], [
            ExpirationDateResolver::EXPIRE_STATE_TYPE_UNKNOWN,
            ExpirationDateResolver::EXPIRE_STATE_TYPE_NOT_EXPIRE,
        ]) && $accountFields['ExpirationDateTs'] > time()) {
            $accountFields['ExpirationDateYMD'] = (new \DateTime('@' . $accountFields['ExpirationDateTs']))->format('c');
            $accountFields['ExpirationDate'] = $this->intervalFormatter->shortFormatViaDates(
                $this->clock->current()->getAsDateTime(),
                new \DateTime('@' . $accountFields['ExpirationDateTs']),
                true,
                true,
                $locale
            );
            $accountFields['ExpirationDateTip'] = $this->localizer->formatDate(new \DateTime('@' . $accountFields['ExpirationDateTs']), 'short', $locale);
        }

        if (isset($accountFields["SubAccountsArray"]) && is_array($accountFields["SubAccountsArray"])) {
            foreach ($accountFields['SubAccountsArray'] as $k => &$subAccount) {
                if (array_key_exists('ExpirationDate', $subAccount) && $subAccount['ExpirationDate'] && !in_array($subAccount['ExpirationStateType'], [
                    ExpirationDateResolver::EXPIRE_STATE_TYPE_UNKNOWN,
                    ExpirationDateResolver::EXPIRE_STATE_TYPE_NOT_EXPIRE,
                ]) && $subAccount['ExpirationDateTs'] > time()) {
                    $subAccount['ExpirationDateYMD'] = (new \DateTime('@' . $subAccount['ExpirationDateTs']))->format('c');
                    $subAccount['ExpirationDate'] = $this->intervalFormatter->shortFormatViaDates(
                        $this->clock->current()->getAsDateTime(),
                        new \DateTime('@' . $subAccount['ExpirationDateTs']),
                        true,
                        true,
                        $locale
                    );
                    $subAccount['ExpirationDateTip'] = $this->localizer->formatDate(new \DateTime('@' . $subAccount['ExpirationDateTs']), 'short', $locale);
                }

                if (self::SUBACCOUNT_FICO_KEYWORD === substr($subAccount['Code'], 0 - strlen(self::SUBACCOUNT_FICO_KEYWORD))) {
                    $subAccount[self::SUBACCOUNT_FICO_KEYWORD] = true;
                    $subAccount['ficoRanges'] = $this->getFicoRanges($subAccount['Code']);

                    if (!empty($ficoUpdated = ($subAccount['Properties']['FICOScoreUpdatedOn']['Val'] ?? null))) {
                        $subAccount['FICOScoreUpdatedOnTs'] = strtotime($ficoUpdated);
                    }
                }
            }
        }

        if (array_key_exists('CardImages', $accountFields) && !empty($accountFields['CardImages'])) {
            $mapperContext->dataTemplate['CardImages'] = [
                CardImage::KIND_FRONT => $mapperContext->dataTemplate['CardImages'],
                CardImage::KIND_BACK => $mapperContext->dataTemplate['CardImages'],
            ];
        }

        return $accountFields;
    }

    protected function mapProperties(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::mapProperties($mapperContext, $accountID, $accountFields);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);

        // Elite level or status (transfer from $accountFields['MainProperties']['Status']['Status'])
        // for ease the client logic
        if (isset($accountFields['MainProperties']['Status'])) {
            $accountFields['AccountStatus'] = $accountFields['MainProperties']['Status']['Status'];
        }

        if (
            !empty($accountFields['Properties'])
            && array_key_exists('LastActivity', $accountFields['Properties'])
            && ($accountFields['Properties']['LastActivity']['Type'] ?? null) == EntityProviderproperty::TYPE_DATE
        ) {
            $date = date_create($accountFields['Properties']['LastActivity']['Val']);

            if ($date instanceof \DateTime) {
                $accountFields['Properties']['LastActivity']['Tip'] = $this->localizer->formatDate($date, 'long', $locale);
                $accountFields['Properties']['LastActivity']['Val'] = $this->intervalFormatter->shortFormatViaDates($this->clock->current()->getAsDateTime(), $date, true, true, $locale);
            }
        }

        return $accountFields;
    }

    protected function normalizeBalance($balance)
    {
        $balance = parent::normalizeBalance($balance);

        // replace <br> and remove html tags
        if (!empty($balance) && false !== strpos($balance, '<')) {
            $balance = preg_replace("/<br[^>]*>/ims", ", ", $balance);
            $balance = strip_tags($balance);
        }

        return $balance;
    }

    private function calcBackgroundCheckState(Usr $user, $accountFields, ?string $locale)
    {
        $state = (int) $accountFields['ProviderState'];
        $result = null;

        if (isset($accountFields['ProviderID'])) {
            $isMailboxStateBad = (PROVIDER_CHECKING_WITH_MAILBOX === $state && 0 === $this->userMailboxCounter->myOrFamilyMember($accountFields['UserID'], $accountFields['UserAgentID']));

            if (
                !(
                    ($state >= PROVIDER_ENABLED || $state === PROVIDER_TEST)
                    && $state !== PROVIDER_CHECKING_EXTENSION_ONLY
                    && $state !== PROVIDER_CHECKING_OFF
                    && $state !== PROVIDER_FIXING
                    && !$user->isFree()
                    && (!$isMailboxStateBad)
                    && $accountFields['CanCheck']
                )
            ) {
                $result = $user->isFree()
                    ? $this->translator->trans( /** @Desc("Background updating is turned off for technical reasons for AwardWallet Free members. Please upgrade to AwardWallet Plus to turn it on.") */ 'account.turned.off.awplus-only', [], 'messages', $locale)
                    : $this->translator->trans( /** @Desc("Background updating is turned off for technical reasons") */ 'account.turned.off.text2', [], 'messages', $locale);
            }

            if ($isMailboxStateBad) {
                $result = $this->translator->trans(/** @Desc("Link the mailbox associated with this account (from your AwardWallet profile) to enable background updating.") */ 'account-mailbox-enable-updating', [], 'messages', $locale);
            }

            if (!$result && $accountFields['SavePassword'] == SAVE_PASSWORD_LOCALLY) {
                $result = $this->translator->trans( /** @Desc("Background updating is turned off because this account password is stored locally.") */ 'account.turned.off.text3', [], 'messages', $locale);
            }

            // TODO Hardcoded. Case https://redmine.awardwallet.com/issues/15085#note-16
            if ($accountFields['ProviderID'] == 1 || ($accountFields['ProviderID'] == 104 && $accountFields['Login2'] == 'US')
                || (false === (bool) $accountFields['CanCheck'] && false === (bool) $accountFields['CanCheckBalance'])
            ) {
                $result = null;
            }
        }

        return $result;
    }

    private function getAllianceLevel($accountFields)
    {
        if (empty($accountFields['MainProperties']['Status']['Value'])) {
            $level = '';
        } else {
            // @TODO: $eliteLevelFields = $this->elRep->getEliteLevelFields($row['ProviderID'], $row['Val']);
            $level = $this->cacheManager->load(new CacheItemReference(
                "alliance_icon_" . $accountFields['ProviderID'] . '_' . preg_replace("#[^\w]+#ims", "_", strtolower($accountFields['MainProperties']['Status']['Value'])),
                Tags::addTagPrefix([Tags::TAG_ELITE_LEVELS]),
                function () use ($accountFields) {
                    $sql = "
                        SELECT
                            ael.Name as Name
                        FROM
                            TextEliteLevel tel
                            JOIN EliteLevel el
                              ON tel.EliteLevelID = el.EliteLevelID
                            JOIN AllianceEliteLevel ael
                              ON el.AllianceEliteLevelID = ael.AllianceEliteLevelID
                        WHERE
                            tel.ValueText = :text
                            AND el.ProviderID = :provider
                        LIMIT 1
                    ";
                    $statement = $this->em->getConnection()->prepare($sql);
                    $statement->bindParam(':text', $accountFields['MainProperties']['Status']['Value'], \PDO::PARAM_STR);
                    $statement->bindParam(':provider', $accountFields['ProviderID'], \PDO::PARAM_INT);
                    $statement->execute();
                    $row = $statement->fetch(\PDO::FETCH_ASSOC);

                    return $row === false ? null : $row['Name'];
                },
                null,
                null
            ));
        }

        return $level;
    }

    private function getAllianceIcon($accountFields)
    {
        if (isset($accountFields['AllianceLevel']) && !is_null($accountFields['AllianceLevel'])) {
            $level = $accountFields['AllianceLevel'];
        } else {
            $level = $this->getAllianceLevel($accountFields);
        }
        $level = strtolower(str_replace(" ", "", $level));

        // return '/assets/awardwalletnewdesign/img/alliances/'.$accountFields['AllianceAlias'].$level.'.png';
        return '/assets/awardwalletnewdesign/img/alliances/' . $accountFields['AllianceAlias'] . $level;
    }

    private function getFicoRanges(string $code): array
    {
        if (false !== stripos($code, 'citybank')
            || false !== stripos($code, 'rbcbank')
        ) {
            return [
                ['min' => 250, 'max' => 549, 'name' => 'Poor'],
                ['min' => 550, 'max' => 649, 'name' => 'Fair'],
                ['min' => 650, 'max' => 729, 'name' => 'Good'],
                ['min' => 730, 'max' => 799, 'name' => 'Very Good'],
                ['min' => 800, 'max' => 900, 'name' => 'Excellent'],
            ];
        }

        return [
            ['min' => 300, 'max' => 579, 'name' => 'Poor'],
            ['min' => 580, 'max' => 669, 'name' => 'Fair'],
            ['min' => 670, 'max' => 739, 'name' => 'Good'],
            ['min' => 740, 'max' => 799, 'name' => 'Very Good'],
            ['min' => 800, 'max' => 850, 'name' => 'Excellent'],
        ];
    }
}
