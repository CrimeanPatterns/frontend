<?php

namespace AwardWallet\MainBundle\Globals\AccountList;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\DesktopInfoMapper;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\DesktopListMapper;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\ExportListMapper;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\Mapper;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileMapper;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class OptionsFactory
{
    private const DOCUMENT_TYPES_ADDITIONS_AFTER_2021_10 = [
        Providercoupon::TYPE_VACCINE_CARD,
        Providercoupon::TYPE_VISA,
        Providercoupon::TYPE_INSURANCE_CARD,
        Providercoupon::TYPE_DRIVERS_LICENSE,
        Providercoupon::TYPE_PRIORITY_PASS,
    ];
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var Connection
     */
    private $dbConnection;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    /**
     * @var ExportListMapper
     */
    private $exportListMapper;
    /**
     * @var DesktopListMapper
     */
    private $desktopListMapper;
    /**
     * @var DesktopInfoMapper
     */
    private $desktopInfoMapper;
    /**
     * @var MobileMapper
     */
    private $defaultMapper;
    /**
     * @var MobileMapper
     */
    private $mobileMapper;
    private TranslatorInterface $translator;

    public function __construct(
        ?RequestStack $requestStack,
        Connection $dbConnection,
        ApiVersioningService $apiVersioning,
        ExportListMapper $exportListMapper,
        DesktopListMapper $desktopListMapper,
        DesktopInfoMapper $desktopInfoMapper,
        MobileMapper $mobileMapper,
        Mapper $defaultMapper,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->dbConnection = $dbConnection;
        $this->apiVersioning = $apiVersioning;
        $this->exportListMapper = $exportListMapper;
        $this->desktopListMapper = $desktopListMapper;
        $this->desktopInfoMapper = $desktopInfoMapper;
        $this->mobileMapper = $mobileMapper;
        $this->defaultMapper = $defaultMapper;
        $this->translator = $translator;
    }

    public function createDefaultOptions(): Options
    {
        $masterRequest = $this->requestStack ?
            $this->requestStack->getMasterRequest() :
            null;
        $clientIp = $masterRequest ?
            $masterRequest->getClientIp() :
            null;

        return new Options([
            Options::OPTION_LOCALE => $masterRequest ? $masterRequest->getLocale() : null,
            Options::OPTION_GROUPBY => Options::VALUE_GROUPBY_USER,
            Options::OPTION_ORDERBY => null,
            Options::OPTION_PER_PAGE => Options::VALUE_DEFAULT_PER_PAGE,
            Options::OPTION_WITHOUT_GROUP => false,
            Options::OPTION_FILTER => '',
            Options::OPTION_COUPON_FILTER => '',
            Options::OPTION_STATEFILTER => null,
            Options::OPTION_CLIENTIP => $clientIp,
            Options::OPTION_CALLBACK => null,
            Options::OPTION_LOAD_PHONES => Options::VALUE_PHONES_SUPPORT,
            Options::OPTION_LOAD_SUBACCOUNTS => true,
            Options::OPTION_LOAD_PROPERTIES => true,
            Options::OPTION_LOAD_HAS_ACTIVE_TRIPS => false,
            Options::OPTION_LOAD_PENDING_SCAN_DATA => false,
            Options::OPTION_JOINS => [],
            Options::OPTION_EXTRA_ACCOUNT_FIELDS => '',
            Options::OPTION_EXTRA_COUPON_FIELDS => '',
            Options::OPTION_CHANGE_PERIOD => '-1 week',
            Options::OPTION_CHANGE_PERIOD_DESC => $this->translator->trans(/** @Desc("Reflecting detected change in balance in the last 7 days") */ 'account.balance.last-change.period'),
            Options::OPTION_ONLY_ACTIVE_DEALS => false,
            Options::OPTION_COMMENT_LENGTH_LIMIT => 200,
            Options::OPTION_FORMATTER => $this->defaultMapper,
            Options::OPTION_LOAD_MILE_VALUE => false,
            Options::OPTION_LOAD_BLOG_POSTS => false,
        ]);
    }

    public function createHighlyOpinionatedAndHardcodedOptions(Options $providedOptions): Options
    {
        $newOptions = $this->createDefaultOptions()->updateFrom($providedOptions);

        $quote = function ($v) {
            return $this->dbConnection->quote('%' . str_replace(['%', '_'], ['', ''], $v) . '%');
        };

        $newOptions->set(Options::OPTION_LOAD_HAS_ACTIVE_TRIPS, true);

        $newOptions->set(Options::OPTION_LOAD_PHONES, $providedOptions->get(Options::OPTION_LOAD_PHONES) ?? Options::VALUE_PHONES_NOLOAD);
        $newOptions->set(Options::OPTION_LOAD_CARD_IMAGES, $providedOptions->get(Options::OPTION_LOAD_CARD_IMAGES) ?? false);
        $newOptions->set(Options::OPTION_LOAD_LOYALTY_LOCATIONS, $providedOptions->get(Options::OPTION_LOAD_LOYALTY_LOCATIONS) ?? false);
        $newOptions->set(Options::OPTION_LOAD_HISTORY_PRESENCE, $providedOptions->get(Options::OPTION_LOAD_HISTORY_PRESENCE) ?? false);
        $newOptions->set(Options::OPTION_LOAD_BALANCE_CHANGES_COUNT, $providedOptions->get(Options::OPTION_LOAD_BALANCE_CHANGES_COUNT) ?? false);
        $newOptions->set(Options::OPTION_LOAD_PENDING_SCAN_DATA, false);
        $newOptions->set(Options::OPTION_LOAD_PROPERTIES, true);
        $newOptions->set(Options::OPTION_STATEFILTER, 'a.State > 0');

        if (!empty($providedOptions->get(Options::OPTION_PAGE))) {
            $newOptions->set(Options::OPTION_PAGE, (int) $providedOptions->get(Options::OPTION_PAGE));
        }

        if (!empty($providedOptions->get(Options::OPTION_PER_PAGE))) {
            $newOptions->set(Options::OPTION_PER_PAGE, (int) $providedOptions->get(Options::OPTION_PER_PAGE));
        }

        if (
            !$providedOptions->has(Options::OPTION_GROUPBY)
            || !$providedOptions->has(Options::OPTION_WITHOUT_GROUP)
        ) {
            if (!empty($providedOptions->get(Options::OPTION_GROUP))) {
                $newOptions->set(Options::OPTION_WITHOUT_GROUP, false);
                $newOptions->set(Options::OPTION_GROUPBY, 1);
            } else {
                $newOptions->set(Options::OPTION_WITHOUT_GROUP, true);
                $newOptions->set(Options::OPTION_GROUPBY, null);
            }
        }

        if (!empty($providedOptions->get(Options::OPTION_ORDER))) {
            $newOptions->set(Options::OPTION_ORDERBY, $providedOptions->get(Options::OPTION_ORDER));
        }

        $filters = [];
        $couponFilters = [];
        $joins = [];

        if (!empty($providedOptions->get(Options::OPTION_AGENTID))) {
            if ($providedOptions->get(Options::OPTION_AGENTID) === 'my') {
                $filters[] = '([ShareUserAgentID] is null and [UserAgentID] is null)';
                $couponFilters[] = '([ShareUserAgentID] is null and [UserAgentID] is null)';
            } else {
                $agentId = intval($providedOptions->get(Options::OPTION_AGENTID));

                if ($agentId > 0) {
                    $filters[] = '([ShareUserAgentID] = ' . $agentId . ' or [UserAgentID] = ' . $agentId . ')';
                    $couponFilters[] = '([ShareUserAgentID] = ' . $agentId . ' or [UserAgentID] = ' . $agentId . ')';
                }
            }
        }

        if (!empty($providedOptions->get(Options::OPTION_DENY_PROVIDERS)) && is_array($providedOptions->get(Options::OPTION_DENY_PROVIDERS))) {
            $filters[] = "(ProviderID not in (" . implode(",", it($providedOptions->get(Options::OPTION_DENY_PROVIDERS))->mapToInt()->toArray()) . ") or ProviderID is null)";
        }

        if (!empty($providedOptions->get(Options::OPTION_FILTER_ERROR))) {
            $filters[] = '(a.ErrorCode >= 2 and p.CanCheck != 0)';
            $couponFilters[] = '(0 = 1)';
        }

        if (!empty($providedOptions->get(Options::OPTION_FILTER_RECENT))) {
            $recent = date('Y-m-d', strtotime('-' . $providedOptions->get(Options::OPTION_FILTER_RECENT) . ' days'));
            $filters[] = 'LastChangeDate >= "' . $recent . '"';
            $couponFilters[] = '0 = 1';
        }

        if (!empty($providedOptions->get(Options::OPTION_FILTER_PROGRAM))) {
            $filters[] = '[DisplayName] LIKE ' . $quote($providedOptions->get(Options::OPTION_FILTER_PROGRAM));
            $couponFilters[] = '[DisplayName] LIKE ' . $quote($providedOptions->get(Options::OPTION_FILTER_PROGRAM));
        }

        if (!empty($providedOptions->get(Options::OPTION_FILTER_OWNER))) {
            $filters[] = '[UserName] LIKE ' . $quote($providedOptions->get(Options::OPTION_FILTER_OWNER));
            $couponFilters[] = '[UserName] LIKE ' . $quote($providedOptions->get(Options::OPTION_FILTER_OWNER));
        }

        if (!empty($providedOptions->get(Options::OPTION_FILTER_ACCOUNT))) {
            $filters[] = '(a.Login LIKE ' . $quote($providedOptions->get(Options::OPTION_FILTER_ACCOUNT)) . ' or a.Login2 LIKE ' . $quote($providedOptions->get(Options::OPTION_FILTER_ACCOUNT)) . ' or apNumber.Val LIKE ' . $quote($providedOptions->get(Options::OPTION_FILTER_ACCOUNT)) . ' or a.Comment LIKE ' . $quote($providedOptions->get(Options::OPTION_FILTER_ACCOUNT)) . ')';
            $joins[] = "LEFT OUTER JOIN AccountProperty apNumber on a.AccountID = apNumber.AccountID";
            $joins[] = "LEFT OUTER JOIN ProviderProperty ppNumber on apNumber.ProviderPropertyID = ppNumber.ProviderPropertyID and ppNumber.Kind = " . PROPERTY_KIND_NUMBER;
            $couponFilters[] = 'c.Description LIKE ' . $quote($providedOptions->get(Options::OPTION_FILTER_ACCOUNT));
            $extraAccountFields = ', apNumber.Val as AccountNumber';
            $extraCouponFields = ', null as AccountNumber';
        }

        if (!empty($providedOptions->get(Options::OPTION_FILTER_STATUS))) {
            $joins[] = "JOIN AccountProperty apStatus on a.AccountID = apStatus.AccountID AND apStatus.Val LIKE " . $quote($providedOptions->get(Options::OPTION_FILTER_STATUS));
            $joins[] = "JOIN ProviderProperty ppStatus on apStatus.ProviderPropertyID = ppStatus.ProviderPropertyID and ppStatus.Kind = " . PROPERTY_KIND_STATUS;
        }

        if (!empty($providedOptions->get(Options::OPTION_FILTER_BALANCE))) {
            $filters[] = '[RawBalance] >= ' . intval($providedOptions->get(Options::OPTION_FILTER_BALANCE));
            $couponFilters[] = '[RawBalance] >= ' . intval($providedOptions->get(Options::OPTION_FILTER_BALANCE));
        }

        if (!empty($providedOptions->get(Options::OPTION_FILTER_EXPIRE))) {
            $filters[] = 'ExpirationDate <= "' . date('Y-m-d', intval($providedOptions->get(Options::OPTION_FILTER_EXPIRE))) . '"';
            $couponFilters[] = 'ExpirationDate <= "' . date('Y-m-d', intval($providedOptions->get(Options::OPTION_FILTER_EXPIRE))) . '"';
        }

        if (!empty($providedOptions->get(Options::OPTION_FILTER_LAST_UPDATE_DATE))) {
            $date = date('Y-m-d', intval($providedOptions->get(Options::OPTION_FILTER_LAST_UPDATE_DATE)));
            $filters[] = '(a.SuccessCheckDate <= "' . $date . '" OR a.LastChangeDate <= "' . $date . '")';
            $couponFilters[] = '0 = 1';
        }

        // for updater render
        if (!empty($providedOptions->get(Options::OPTION_FORMAT_FORCE_ERRORS))) {
            $extraAccountFields = ', 1 as ForceErrorDisplay';
            $extraCouponFields = ', null as ForceErrorDisplay';
        }

        if (!empty($providedOptions->get(Options::OPTION_ALLIANCEID))) {
            $filters[] = '[AllianceID] = ' . ((int) $providedOptions->get(Options::OPTION_ALLIANCEID));
            $newOptions->set(Options::OPTION_COUPON_FILTER, ' AND 0=1 ');
        }

        if (!empty($providedOptions->get(Options::OPTION_ACCOUNT_IDS)) && is_array($providedOptions->get(Options::OPTION_ACCOUNT_IDS))) {
            $filters[] = "a.AccountID in (" . implode(",", it($providedOptions->get(Options::OPTION_ACCOUNT_IDS))->mapToInt()->toArray()) . ")";
            $newOptions->set(Options::OPTION_COUPON_FILTER, ' AND 0=1 ');
        }

        // get list
        if (count($filters)) {
            $newOptions->set(Options::OPTION_FILTER, ' AND ' . implode(' AND ', $filters));
        }

        if (count($couponFilters)) {
            $newOptions->set(Options::OPTION_COUPON_FILTER, ' AND ' . implode(' AND ', $couponFilters));
        }

        if (!empty($joins)) {
            $newOptions->set(Options::OPTION_JOINS, $joins);
        }

        if (!empty($extraAccountFields)) {
            $newOptions->set(Options::OPTION_EXTRA_ACCOUNT_FIELDS, $extraAccountFields);
        }

        if (!empty($extraCouponFields)) {
            $newOptions->set(Options::OPTION_EXTRA_COUPON_FIELDS, $extraCouponFields);
        }

        return $newOptions;
    }

    public function createDesktopListOptions(?Options $providedOptions = null): Options
    {
        $newOptions = $providedOptions ? clone $providedOptions : new Options();

        if (!$newOptions->has(Options::OPTION_AS_OBJECT)) {
            $newOptions->set(Options::OPTION_AS_OBJECT, true);
        }

        $newOptions->set(Options::OPTION_FORMATTER, $this->desktopListMapper);

        return $this->createHighlyOpinionatedAndHardcodedOptions($newOptions);
    }

    public function createDesktopListViewOptions(?Options $providedOptions = null): Options
    {
        $newOptions = $providedOptions ? clone $providedOptions : new Options();
        $newOptions
            ->set(Options::OPTION_FORMATTER, $this->exportListMapper)
            ->set(Options::OPTION_AS_OBJECT, false)
            ->set(Options::OPTION_GROUP, true)
            ->set(Options::OPTION_WITHOUT_GROUP, true)
            ->set(Options::OPTION_GROUPBY, Options::VALUE_GROUPBY_KIND)
        ;

        return $this->createHighlyOpinionatedAndHardcodedOptions($newOptions);
    }

    public function createDesktopInfoOptions(?Options $providedOptions = null): Options
    {
        $newOptions = $providedOptions ? clone $providedOptions : new Options();

        if (!$newOptions->has(Options::OPTION_AS_OBJECT)) {
            $newOptions->set(Options::OPTION_AS_OBJECT, true);
        }

        $newOptions->set(Options::OPTION_FORMATTER, $this->desktopInfoMapper);

        return $this->createHighlyOpinionatedAndHardcodedOptions($newOptions);
    }

    public function createMobileOptions(?Options $providedOptions = null): Options
    {
        $newOptions = $this
            ->createDefaultOptions()
            ->updateFrom($providedOptions ?? (new Options()))
            ->set(Options::OPTION_ONLY_ACTIVE_DEALS, true)
            ->set(Options::OPTION_LOAD_PHONES, Options::VALUE_PHONES_FULL)
            ->set(Options::OPTION_LOAD_HISTORY_PRESENCE, true)
            ->set(Options::OPTION_LOAD_BALANCE_CHANGES_COUNT, true)
            ->set(Options::OPTION_LOAD_MILE_VALUE, true)
            ->set(Options::OPTION_LOAD_BLOG_POSTS, true)
            ->set(Options::OPTION_FORMATTER, $this->mobileMapper);

        if ($this->apiVersioning->supports(MobileVersions::CARD_IMAGES)) {
            $newOptions->set(Options::OPTION_LOAD_CARD_IMAGES, true);
        }

        if ($this->apiVersioning->supports(MobileVersions::LOCATION_STORAGE)) {
            $newOptions->set(Options::OPTION_LOAD_LOYALTY_LOCATIONS, true);
        }

        if ($this->apiVersioning->notSupports(MobileVersions::DOCUMENT_KIND)) {
            $newOptions
                ->set(Options::OPTION_COUPON_FILTER, ' AND (c.Kind <>' . PROVIDER_KIND_DOCUMENT . ') ')
                ->set(Options::OPTION_FILTER, ' AND (a.Kind IS NULL OR a.Kind <> ' . PROVIDER_KIND_DOCUMENT . ') ');
        } elseif ($this->apiVersioning->notSupports(MobileVersions::DOCUMENT_VACCINE_VISA_INSURANCE_TYPES)) {
            $newOptions
                ->set(
                    Options::OPTION_COUPON_FILTER,
                    ' AND (c.TypeID NOT IN (' . it(self::DOCUMENT_TYPES_ADDITIONS_AFTER_2021_10)->joinToString(', ') . '))'
                );
        } elseif ($this->apiVersioning->notSupports(MobileVersions::DOCUMENT_PRIORITY_PASS)) {
            $newOptions
                ->set(
                    Options::OPTION_COUPON_FILTER,
                    ' AND (c.TypeID NOT IN (' . Providercoupon::TYPE_PRIORITY_PASS . '))'
                );
        }

        return $this->createHighlyOpinionatedAndHardcodedOptions($newOptions);
    }

    public function createExportListOptions(?Options $providedOptions = null): Options
    {
        $newOptions = $providedOptions ? clone $providedOptions : new Options();
        $newOptions
            ->set(Options::OPTION_GROUP, true)
            ->set(Options::OPTION_LOAD_PHONES, false)
            ->set(Options::OPTION_FORMATTER, $this->exportListMapper);

        if (!$newOptions->has(Options::OPTION_AS_OBJECT)) {
            $newOptions->set(Options::OPTION_AS_OBJECT, false);
        }

        return $this->createHighlyOpinionatedAndHardcodedOptions($providedOptions);
    }
}
