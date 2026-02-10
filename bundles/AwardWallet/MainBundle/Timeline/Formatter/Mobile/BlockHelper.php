<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Files\AbstractFile;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Repositories\CountryRepository;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant as RestaurantEntity;
use AwardWallet\MainBundle\Entity\ShowAIWarningForEmailSourceInterface;
use AwardWallet\MainBundle\Entity\Trip as TripEntity;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\FrameworkExtension\Twig\AwTwigExtension;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\Features\FeaturesBitSet;
use AwardWallet\MainBundle\Globals\GeneralUtils;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;
use AwardWallet\MainBundle\Manager\Files\PlanFileManager;
use AwardWallet\MainBundle\Service\ItineraryFormatter;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\Tags;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Service\LegacyUrlGenerator;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\MobileExtensionHandler;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Block;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Link;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\LocalizedDate;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\LocalizedDateParts;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\BaseMenu;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\Direction;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\Phones;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\PhoneTab;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value\Group;
use AwardWallet\MainBundle\Timeline\Formatter\Origin;
use AwardWallet\MainBundle\Timeline\Item;
use Clock\ClockInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;

class BlockHelper
{
    protected const PHONES_SECTION_LIMIT = 5;

    protected const PARSED_EMAIL_SOURCE_TYPE_SENT_TO = 'sent_to';
    protected const PARSED_EMAIL_SOURCE_TYPE_FORWARDED = 'forwarded';

    protected const PARSED_EMAIL_SOURCE_TYPE = [
        ParsedEmailSource::SOURCE_PLANS => self::PARSED_EMAIL_SOURCE_TYPE_SENT_TO,
        ParsedEmailSource::SOURCE_SCANNER => self::PARSED_EMAIL_SOURCE_TYPE_SENT_TO,
    ];

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var string
     */
    private $host;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var CountryRepository
     */
    private $countryRepository;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var LocalizeService
     */
    private $localizer;
    /**
     * @var ItineraryFormatter\Util
     */
    private $itineraryFormatterUtil;
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;
    /**
     * @var AwTwigExtension
     */
    private $awTwigExtension;
    /**
     * @var ItineraryFormatter\Formatter\BaseStringFormatterFactory
     */
    private $mobileFormatterFactory;
    /**
     * @var ItineraryFormatter\PropertiesDB\PropertiesDB
     */
    private $propertiesDB;
    /**
     * @var Desanitizer
     */
    private $desanitizer;
    private Origin $sourcesFormatter;
    private LegacyUrlGenerator $legacyUrlGenerator;
    private ClockInterface $clock;
    private PlanFileManager $planFileManager;

    public function __construct(
        TranslatorInterface $translator,
        CountryRepository $countryRepository,
        AuthorizationCheckerInterface $authorizationChecker,
        LocalizeService $localizer,
        ItineraryFormatter\Util $itineraryFormatterUtil,
        PropertyAccessorInterface $propertyAccessor,
        AwTwigExtension $awTwigExtension,
        ItineraryFormatter\Formatter\BaseStringFormatterFactory $mobileFormatterFactory,
        ItineraryFormatter\PropertiesDB\PropertiesDB $propertiesDB,
        UrlGeneratorInterface $urlGenerator,
        Desanitizer $desanitizer,
        Origin $sourcesFormatter,
        LegacyUrlGenerator $legacyUrlGenerator,
        ClockInterface $clock,
        string $host,
        PlanFileManager $planFileManager
    ) {
        $this->translator = $translator;
        $this->countryRepository = $countryRepository;
        $this->authorizationChecker = $authorizationChecker;
        $this->localizer = $localizer;
        $this->itineraryFormatterUtil = $itineraryFormatterUtil;
        $this->propertyAccessor = $propertyAccessor;
        $this->awTwigExtension = $awTwigExtension;
        $this->host = $host;
        $this->urlGenerator = $urlGenerator;
        $this->mobileFormatterFactory = $mobileFormatterFactory;
        $this->propertiesDB = $propertiesDB;
        $this->desanitizer = $desanitizer;
        $this->sourcesFormatter = $sourcesFormatter;
        $this->legacyUrlGenerator = $legacyUrlGenerator;
        $this->clock = $clock;
        $this->planFileManager = $planFileManager;
    }

    public function formatCommonProperties(Item\ItemInterface $item, Formatted\AbstractItem $formattedItem)
    {
        $formattedItem->id = $item->getId();

        if ($item instanceof Item\AbstractTrip) {
            $formattedItem->type = 'trip';
        } else {
            $formattedItem->type = $item->getType();
        }

        if ($item instanceof Item\AbstractItinerary) {
            $source = $item->getSource();

            // layover has no source
            if ($source) {
                if ($source instanceof Tripsegment) {
                    $source = $source->getTripid();
                }

                $formattedItem->futureCountId = $source->getIdString();
            }
        }

        $formattedItem->breakAfter = $item->isBreakAfter();
    }

    public function formatSegmentSources(Item\ItineraryInterface $itinerary, Formatted\SegmentItem $formattedItem, FeaturesBitSet $formatOptions): void
    {
        $sourcesFormatted = $this->sourcesFormatter->format($itinerary);

        if (!$sourcesFormatted) {
            return;
        }

        [
            'auto' => $sources,
            'manual' => $isManual
        ] = $sourcesFormatted;

        $sourcesAutoBlocks = [];

        /** @var array $source */
        foreach ($sources as $source) {
            $sourceAutoBlock = Block::fromKindValue(
                Block::KIND_SOURCE,
                $source,
            );

            switch ($source['type']) {
                case Origin::TYPE_ACCOUNT:
                    $sourceAutoBlock->link = new Link($this->legacyUrlGenerator->generateAbsoluteUrl("/m/account/details/a" . $source['accountId']));
                    $sourcesAutoBlocks[] = $sourceAutoBlock;

                    break;

                case Origin::TYPE_CONFIRMATION_NUMBER:
                    $sourcesAutoBlocks[] = $sourceAutoBlock;

                    break;

                case Origin::TYPE_TRIPIT:
                    if ($formatOptions->supports(FormatHandler::SOURCES_TRIPIT)) {
                        $sourcesAutoBlocks[] = $sourceAutoBlock;
                    }

                    break;

                case Origin::TYPE_EMAIL:
                    $sourceAutoBlock->val['from'] =
                        self::PARSED_EMAIL_SOURCE_TYPE[$source['from']] ??
                        self::PARSED_EMAIL_SOURCE_TYPE_FORWARDED;

                    if (ParsedEmailSource::SOURCE_SCANNER == $source['from']) {
                        $sourceAutoBlock->link = new Link($this->urlGenerator->generate('aw_usermailbox_view', [], UrlGeneratorInterface::ABSOLUTE_URL));
                    }

                    $sourcesAutoBlocks[] = $sourceAutoBlock;

                    break;

                default:
                    return;
            }
        }

        $group = Group::fromName($this->translator->trans(/** @Desc("Sources") */ 'trips.sources', [], 'trips'));
        $group->desc = $isManual ?
            $this->translator->trans('trips.segment.manually-added', [], 'trips') :
            (
                \count($sourcesAutoBlocks) > 1 ?
                    $this->translator->trans('trips.segment.auto-added-from', [], 'trips') :
                    $this->translator->trans(/** @Desc("This trip segment was automatically added by retrieving it from:") */ 'trips.segment.single-added-from', [], 'trips')
            );

        $formattedItem->blocks[] = $group;

        foreach ($sourcesAutoBlocks as $sourceBlock) {
            $formattedItem->blocks[] = $sourceBlock;
        }
    }

    public function formatAIWarning(
        Item\AbstractItinerary $item,
        Formatted\SegmentItem $formattedItem,
        FeaturesBitSet $features
    ): void {
        if (!$features->supports(FormatHandler::AI_WARNING)) {
            return;
        }

        $source = $item->getSource();

        if (
            $source instanceof ShowAIWarningForEmailSourceInterface
            && $source->isShowAIWarningForEmailSource()
        ) {
            $formattedItem->blocks[] = Block::fromKind(Block::KIND_AI_RESERVATION);
        }
    }

    public function formatCommonSegmentProperties(Item\ItemInterface $item, Formatted\SegmentItem $formattedItem, FeaturesBitSet $formatOptions)
    {
        if ($item instanceof Item\ItineraryInterface) {
            $source = $item->getSource();
            $formattedItem->icon = $item->getIcon();
            $formattedItem->changed = $item->isChanged() && $item->getChanges() && $item->getChanges()->getChangedProperties();

            if ($source instanceof Tripsegment) {
                $item->getContext()->setPropFormatter(
                    $this->mobileFormatterFactory->createFromTripSegment($source, $item->getChanges())
                );
                $formattedItem->deleted = $source->getHidden() || $source->getTripid()->getHidden();
            } elseif ($source instanceof Itinerary) {
                $item->getContext()->setPropFormatter(
                    $this->mobileFormatterFactory->createFromItinerary($source, $item->getChanges())
                );
                $formattedItem->deleted = $source->getHidden();
            }

            if ($source instanceof ShowAIWarningForEmailSourceInterface) {
                $formattedItem->aiWarning = $source->isShowAIWarningForEmailSource();
            }

            if (
                $formatOptions->supports(FormatHandler::MOVE_TRAVEL_SEGMENTS)
                && $source instanceof Tripsegment
                && ($count = $source->getTripid()->getSegments()->count()) > 1
            ) {
                $formattedItem->confirmationSummary = new Formatted\Components\ConfirmationSummary(
                    !empty($item->getConfNo()) ? $item->getConfNo() : null,
                    $count
                );
            }
        }

        $this->formatCommonProperties($item, $formattedItem);

        if (null !== ($endDate = $item->getEndDate())) {
            if ($formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2)) {
                $formattedItem->endDate = $this->createLocalizedDate(DateTimeExtended::create($endDate));
            } else {
                $formattedItem->endDate = new Formatted\Components\Date($endDate);
            }
        }

        if ($item instanceof Item\LayoverInterface && null !== ($duration = $item->getDuration())) {
            $formattedItem->duration = Utils::formatDateInterval($duration);
        }
    }

    /**
     * @return Block[]
     */
    public function getExtPropertiesBlocks(
        Item\AbstractItinerary $item,
        FeaturesBitSet $formatOptions,
        array $excluded = [],
        array $diffTrackedProperties = [],
        array $propertiesOrder = [],
        bool $showPrivate
    ) {
        $blocks = [];
        $isDetailedItinerariesV2Enabled = $formatOptions->supports(FormatHandler::DETAILED_ITINERARIES_V2_INFO);
        /*
         * Itinerary formatter does not populate properties with old values if minChangeDate
         * is not provided (see \AwardWallet\MainBundle\Service\ItineraryFormatter\Segment::addProperty).
         * Pass some date as workaround.
         */
        $excluded = it($excluded)->mapToLower()->toArray();

        /** @var \AwardWallet\MainBundle\Service\ItineraryMail\Property $property */
        $properties = [];

        /*
         * key => scalar: plain property
         * key => array: flattened property
         */
        $boldFormattedMoneyAtTheEndForV2BlocksMap = [
            PropertiesList::SPENT_AWARDS => 1,
            PropertiesList::COST => 1,
            PropertiesList::DISCOUNT => 1,
            PropertiesList::FEES_LIST => [],
            PropertiesList::TOTAL_CHARGE => 1,
        ];
        $boldFormattedMoneyAtTheEndForV2Blocks = \array_keys($boldFormattedMoneyAtTheEndForV2BlocksMap);

        $formatter = $item->getContext()->getPropFormatter();

        foreach ($this->propertiesDB->getProperties() as $propertyInfo) {
            $propertyCode = $propertyInfo->getCode();

            if (
                \in_array(\strtolower($propertyCode), $excluded)
                || ($propertyInfo->isPrivate() && !$showPrivate)
                || ($propertyInfo->hasTag(Tags::PRICING_INFO) && !$showPrivate)
                || (
                    $isDetailedItinerariesV2Enabled
                    && (
                        \in_array($propertyCode, $boldFormattedMoneyAtTheEndForV2Blocks)
                        || $propertyInfo->hasTag(Tags::PRICING_INFO)
                    )
                )
                || $propertyInfo->hasTag(Tags::INTERNAL)
            ) {
                continue;
            }

            $value = $formatter->getValue($propertyCode);

            if (\is_null($value) || !is_scalar($value)) {
                continue;
            }

            $properties[$propertyCode] = [
                $propertyCode,
                $formatter->translatePropertyName($propertyCode),
                $value,
                $formatter->getPreviousValue($propertyCode),
            ];
        }

        $propertiesOrderMap = \array_flip($propertiesOrder);

        if (!empty($propertiesOrder)) {
            $maxIndex = \count($propertiesOrder);
            \usort(
                $properties,
                fn (array $a, array $b) =>
                    ($propertiesOrderMap[$a[0]] ?? $maxIndex) <=> ($propertiesOrderMap[$b[0]] ?? $maxIndex)
            );
        }

        $moneyProps = [];

        if ($isDetailedItinerariesV2Enabled) {
            $moneyProps =
                it($boldFormattedMoneyAtTheEndForV2BlocksMap)
                ->flatMapIndexed(function ($type, string $code) use ($formatter) {
                    $value = $formatter->getValue($code);

                    if (\is_scalar($type)) {
                        if (\is_null($value) || !is_scalar($value)) {
                            return;
                        }

                        yield $code => [
                            $code,
                            $formatter->translatePropertyName($code),
                            $value,
                            $formatter->getPreviousValue($code),
                        ];
                    } else {
                        if (!$value) {
                            return;
                        }

                        yield $code =>
                            it($value)
                            ->mapIndexed(fn (string $valueItemData, string $valueItemName) => [
                                $valueItemName,
                                $formatter->translatePropertyName($valueItemName),
                                $valueItemData,
                                null,
                            ])
                            ->toArrayWithKeys();
                    }
                })
                ->toArrayWithKeys();
        }

        foreach ($properties as $property) {
            $blocks[] = $this->createPropertyBlock($property, $formatOptions, $diffTrackedProperties);
        }

        if ($moneyProps) {
            $moneyBlockFactoryByCode = function (string $code) use ($boldFormattedMoneyAtTheEndForV2BlocksMap, $moneyProps, $formatOptions, $diffTrackedProperties): iterable {
                if (!isset($moneyProps[$code])) {
                    return [];
                }

                if (\is_scalar($boldFormattedMoneyAtTheEndForV2BlocksMap[$code])) {
                    $block = $this->createPropertyBlock($moneyProps[$code], $formatOptions, $diffTrackedProperties);
                    $blocks = [$block];
                } else {
                    $blocks =
                        it($moneyProps[$code])
                        ->map(fn (array $propertyData) => $this->createPropertyBlock(
                            $propertyData,
                            $formatOptions,
                            $diffTrackedProperties
                        ));
                }

                return
                    it($blocks)
                    ->onEach(fn (Block $block) => $block->background = 'gray');
            };

            $moneyBlocks =
                it([
                    $moneyBlockFactoryByCode(PropertiesList::SPENT_AWARDS),
                    $moneyBlockFactoryByCode(PropertiesList::COST),
                    $moneyBlockFactoryByCode(PropertiesList::FEES_LIST),
                    $moneyBlockFactoryByCode(PropertiesList::DISCOUNT),

                    it($moneyBlockFactoryByCode(PropertiesList::TOTAL_CHARGE))
                    ->onEach(function (Block $block) {
                        $block->bold = true;
                        $block->background = null;
                    }),
                ])
                ->flatten(1)
                ->toArray();

            if ($moneyBlocks) {
                $blocks = \array_merge(
                    $blocks,
                    $moneyBlocks
                );
            }
        }

        return $blocks;
    }

    /**
     * @param bool $clearPersonalData
     * @return BaseMenu
     */
    public function formatBaseMenuProperties(BaseMenu $baseMenu, Item\AbstractItinerary $item, FeaturesBitSet $formatOptions, $clearPersonalData = false)
    {
        $canCheck = lazy(fn () => $this->authorizationChecker->isGranted('EDIT', $item->getItinerary()));
        $canAutologin = lazy(fn () => $this->authorizationChecker->isGranted('AUTOLOGIN', $item->getItinerary()));

        if (
            $canAutologin()
            && !$clearPersonalData
        ) {
            if ($account = $item->getAccount()) {
                $baseMenu->accountId = $account->getAccountid();
            }

            $baseMenu->itineraryAutologin = [
                'itineraryId' => $item->getId(),
                'type' => MobileExtensionHandler::DESKTOP_TYPE,
            ];
        }

        if ($geotag = $item->getGeotag()) {
            $baseMenu->direction = new Direction($geotag);
        }

        if (
            $item->getStartDate()
            && ($item->getStartDate() > $this->clock->current()->getAsDateTime()->modify('-6 months'))
        ) {
            if ($formatOptions->supports(FormatHandler::DETAILED_ITINERARIES_V2_INFO)) {
                $phonesTabs = [];

                $firstTabPhones =
                    it([
                        Timeline\PhonesSection::SECTION_ACCOUNT,
                        Timeline\PhonesSection::SECTION_ISSUING_AIRLINE,
                        Timeline\PhonesSection::SECTION_MARKETING_AIRLINE,
                        Timeline\PhonesSection::SECTION_OPERATING_AIRLINE,
                    ])
                    ->filterByInMap($item->getPhones())
                    ->map(fn (string $sectionCode): Phones => $this->createPhones($item, $item->getPhones()[$sectionCode]))
                    ->toArray();

                if ($firstTabPhones) {
                    $tab = new PhoneTab($this->createPhoneTabTitle($item), $item->getIcon());
                    $tab->phonesLists = $firstTabPhones;
                    $phonesTabs[] = $tab;
                }

                if (isset($item->getPhones()[Timeline\PhonesSection::SECTION_TRAVEL_AGENCY])) {
                    $tab = new PhoneTab($this->translator->trans('itineraries.travel-agency.phones.title', [], 'trips'), 'agency');
                    $section = $this->createPhones($item, $item->getPhones()[Timeline\PhonesSection::SECTION_TRAVEL_AGENCY]);
                    $section->icon = 'agency';
                    $tab->phonesLists = [$section];
                    $phonesTabs[] = $tab;
                }

                if ($phonesTabs) {
                    $baseMenu->phones = $phonesTabs;
                }
            } elseif (isset($item->getPhones()[Timeline\PhonesSection::SECTION_ACCOUNT])) {
                $baseMenu->phones = $this->createPhones(
                    $item,
                    $item->getPhones()[Timeline\PhonesSection::SECTION_ACCOUNT]
                );
            }
        }

        if (
            $canAutologin()
            || $canCheck()
        ) {
            $baseMenu->shareCode = $item->getItinerary()->getEncodedShareCode();
        }

        return $baseMenu;
    }

    /**
     * @param LocalizeService::FORMAT_* $dateFormat
     * @param LocalizeService::FORMAT_* $timeFormat
     * @return LocalizedDate
     */
    public function createLocalizedDate(DateTimeExtended $date, ?DateTimeExtended $oldDate = null, $dateFormat = LocalizeService::FORMAT_SHORT, $timeFormat = LocalizeService::FORMAT_SHORT)
    {
        $localizedDate = new LocalizedDate();
        $localizedDate->ts = $date->getTimestamp();
        $localizedDate->localYMD = $date->format('Y-m-d');
        // fix for days from today feature
        // 00:00:01 fix client check < 24 hour
        $localizedDate->localYMD .= "T00:00:01";
        $localizedDate->fmtParts = [
            'y' => (int) $date->format('Y'),
            'm' => (int) (($m = $date->format('m')) > 0 ? $m - 1 : $m),
            'd' => (int) $date->format('d'),
            'h' => (int) $date->format('H'),
            'i' => (int) $date->format('i'),
        ];
        $localizedDate->offset = (int) $date->getOffset();
        $parts = new LocalizedDateParts();
        $parts->tz = $date->getTimezoneAbbr() ?? $date->format('T');
        $parts->t = preg_replace(
            '/([0-9]{1,2}:[0-9]{1,2}).*/ims',
            '$1',
            $formattedTime = $this->localizer->formatDateTime($date, null, $timeFormat)
        );
        $parts->d = $this->localizer->formatDateTime($date, $dateFormat, null);

        // whether time format with AM/PM
        if ($formattedTime !== $parts->t) {
            $parts->p = $date->format('A');
        }

        $localizedDate->fmt = $parts;

        if ($oldDate) {
            $localizedDate->old = $this->createLocalizedDate($oldDate, null, $dateFormat, $timeFormat);
        }

        return $localizedDate;
    }

    /**
     * @param string $name
     */
    public function translateSegmentProperty(Item\AbstractItinerary $segment, $name): array
    {
        $source = $segment->getSource();
        $value = $this->getNullableProperty($source, $name);

        if ($source instanceof Tripsegment) {
            $value = $value ?? $this->getNullableProperty($source->getTripid(), $name);
        }

        if (StringUtils::isEmpty($value)) {
            return [];
        }

        return [
            $this->itineraryFormatterUtil->translatePropertyName($name, $source->getType())['translation'],
            $value,
        ];
    }

    /**
     * @param string $value
     * @param bool $forceOut
     * @return string
     */
    public function formatRichText($value, $forceOut)
    {
        return $this->awTwigExtension->auto_link(nl2br($value), '', $forceOut, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param Item\PlanStart|Item\PlanEnd $plan
     */
    public function createFromPlan($plan, FeaturesBitSet $formatOptions): Formatted\PlanItem
    {
        $planEntity = $plan->getPlan();
        $item = new Formatted\PlanItem();
        $item->id = $plan->getId();
        $item->type = $plan->getType();
        $item->name = $planEntity->getName();
        $item->planId = $planEntity->getId();
        $item->hasNotes = !empty($planEntity->getNotes()) || $planEntity->getFiles()->count();

        if (
            $formatOptions->supports(FormatHandler::TRAVEL_PLAN_DURATION)
            && $plan instanceof Item\PlanStart
            && !is_null($plan->getStartSegmentDate())
            && !is_null($plan->getEndSegmentDate())
            && ($nights = Timeline\Builder::getNights($plan->getStartSegmentDate(), $plan->getEndSegmentDate())) > 0) {
            $item->duration = sprintf('%s %s', $this->localizer->formatNumber($nights), $this->translator->trans('nights', [
                '%count%' => $nights,
            ]));
        }

        if ($formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2)) {
            $item->startDate = $this->createLocalizedDate(DateTimeExtended::create($plan->getLocalDate() ?? $plan->getStartDate(), $plan->getTimezoneAbbr()), null, 'full');
        } else {
            $item->startDate = new Formatted\Components\Date($plan->getLocalDate() ?? $plan->getStartDate());
        }

        $item->startDate->ts = $plan->getStartDate()->getTimestamp();

        $item->endDate = $item->startDate;
        $item->breakAfter = $plan->isBreakAfter();

        if ($this->authorizationChecker->isGranted('EDIT', $planEntity)) {
            $item->shareCode = $planEntity->getEncodedShareCode();
        }

        return $item;
    }

    public static function isEmpty($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('Value is not an object!');
        }

        foreach ($object as $_ => $propertyValue) {
            if (!StringUtils::isEmpty($propertyValue)) {
                return false;
            }
        }

        return true;
    }

    public function calculateAllowConfirmChanges(Item\AbstractItinerary $item, $segmentChanged = false): bool
    {
        return $segmentChanged
            && !$item->getSource()->getHidden()
            && $this->authorizationChecker->isGranted('EDIT', $item->getItinerary());
    }

    public function generateSafeUrl(string $url): string
    {
        if (!in_array(parse_url($url, PHP_URL_HOST), [$this->host, 'awardwallet.com'], true)) {
            return $this->urlGenerator->generate('aw_out', ['url' => $url], UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            return $url;
        }
    }

    public function formatConfirmChanges(
        Item\AbstractItinerary $item,
        Timeline\Formatter\Mobile\Formatted\SegmentItem $formatted,
        FeaturesBitSet $formatOptions
    ): void {
        if ($formatOptions->supports(FormatHandler::CONFIRM_CHANGES)) {
            $formatted->menu->allowConfirmChanges = $this->calculateAllowConfirmChanges($item, $formatted->changed);
        }
    }

    public function formatNotes(
        Item\AbstractItinerary $item,
        Timeline\Formatter\Mobile\Formatted\SegmentItem $formatted,
        Timeline\QueryOptions $queryOptions,
        array &$extPropBlocks
    ): void {
        $formatOptions = $queryOptions->getFormatOptions();
        $isBlocksV2Enabled = $formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2);

        $notes = $isBlocksV2Enabled
            ? $this->formatRichText($item->getItinerary()->getNotes(), $formatOptions->supports(FormatHandler::OUT_URL))
            : $item->getItinerary()->getNotes();

        if ($formatOptions->supports(FormatHandler::DESANITIZED_STRINGS)) {
            $notes = StringUtils::stripTagsAttributes($notes);
            $notes = $this->awTwigExtension->auto_link_abs($notes);
        }

        if ($formatOptions->supports(FormatHandler::ITINERARY_NOTE_AND_FILES)) {
            $extPropBlocks[] = Block::fromKindNameValue(
                Block::KIND_NOTES_AND_FILES,
                $this->translator->trans('itineraries.notes', [], 'trips'),
                [
                    'notes' => $notes,
                    'files' => $this->planFileManager->getFlatFiles($item->getItinerary()->getFiles()),
                ]
            );
        } elseif (!$queryOptions->noPersonalData()
            && !StringUtils::isEmpty($notes)
        ) {
            $extPropBlocks[] = Block::fromKindNameValue(
                Block::KIND_TEXT,
                $this->translator->trans('itineraries.notes', [], 'trips'),
                $notes
            );
        }
    }

    public function formatNoteFiles(
        Item\ItineraryInterface $item,
        Formatted\SegmentItem $formattedItem,
        FeaturesBitSet $formatOptions
    ): void {
        if (!$formatOptions->supports(FormatHandler::ITINERARY_FILES)
            || $formatOptions->supports(FormatHandler::ITINERARY_NOTE_AND_FILES)
        ) {
            return;
        }

        $files = $item->getItinerary()->getFiles();

        if (null === $files || 0 === count($files)) {
            $formattedItem->blocks[] = new Block(
                Block::KIND_ATTACHMENTS,
                null,
                $this->translator->trans('attachments', [], 'trips'),
                []
            );

            return;
        }

        $list = [];

        /** @var $file AbstractFile * */
        foreach ($files as $file) {
            $list[] = [
                'id' => $file->getId(),
                'name' => $file->getFileName(),
                'description' => $file->getDescription(),
                'size' => $this->localizer->formatNumberShort($file->getFileSize(), 2),
                'time' => $file->getUploadDate()->getTimestamp(),
                'date' => $this->localizer->formatDate($file->getUploadDate(), 'medium')
                    . ', ' . $this->localizer->formatTime($file->getUploadDate()),
            ];
        }

        $attachmentGroup = new Block(
            Block::KIND_ATTACHMENTS,
            null,
            $this->translator->trans(/** @Desc("Attachments") */ 'attachments', [], 'trips'),
            $list
        );

        $formattedItem->blocks[] = $attachmentGroup;
    }

    protected function createPropertyBlock(array $propertyData, FeaturesBitSet $formatOptions, array $diffTrackedProperties): Block
    {
        $isBlocksV2Enabled = $formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2);
        $proxyExternalLinks = $formatOptions->supports(FormatHandler::OUT_URL);
        [$propertyCode, $translatedPropertyName, $propertyValue, $oldValue] = $propertyData;

        $isRichText = preg_match('/[\r\n]/', $propertyValue) || (mb_strlen($propertyValue) > 100);

        if ($isRichText && $isBlocksV2Enabled) {
            $blockKind = Block::KIND_TEXT;
            $newValue = $this->formatRichText($propertyValue, $proxyExternalLinks);
        } else {
            $blockKind = Block::KIND_STRING;
            $newValue = $propertyValue;
        }

        if (!$isRichText && \in_array($propertyCode, $diffTrackedProperties, true)) {
            if (StringUtils::isEmpty($oldValue)) {
                $oldValue = null;
            }
        } else {
            $oldValue = null;
        }
        $block = Block::fromKindNameValue(
            $blockKind,
            $translatedPropertyName,
            $newValue,
            $oldValue
        );

        return $block;
    }

    protected function createPhoneTabTitle(Item\AbstractItinerary $item): ?string
    {
        $itinerary = $item->getItinerary();

        if ($itinerary instanceof TripEntity) {
            switch ($itinerary->getCategory()) {
                case TripEntity::CATEGORY_AIR:
                    return $this->translator->trans('itineraries.trip.air.phones.title', [], 'trips');

                case TripEntity::CATEGORY_BUS:
                    return $this->translator->trans('itineraries.trip.bus.phones.title', [], 'trips');

                case TripEntity::CATEGORY_TRAIN:
                    return $this->translator->trans('itineraries.trip.train.phones.title', [], 'trips');

                case TripEntity::CATEGORY_CRUISE:
                    return $this->translator->trans('itineraries.trip.cruise.phones.title', [], 'trips');

                case TripEntity::CATEGORY_FERRY:
                    return $this->translator->trans('itineraries.trip.ferry.phones.title', [], 'trips');

                case TripEntity::CATEGORY_TRANSFER:
                    return $this->translator->trans('itineraries.trip.transfer.phones.title', [], 'trips');
            }
        } elseif ($itinerary instanceof Rental) {
            if ($itinerary->getType() === Rental::TYPE_TAXI) {
                return $this->translator->trans('itineraries.taxi.phones.title', [], 'trips');
            } else {
                return $this->translator->trans('itineraries.rental.phones.title', [], 'trips');
            }
        } elseif ($itinerary instanceof Reservation) {
            return $this->translator->trans('itineraries.reservation.phones.title', [], 'trips');
        } elseif ($itinerary instanceof RestaurantEntity) {
            return $this->translator->trans('itineraries.restaurant.phones.title', [], 'trips');
        }

        return '';
    }

    protected function createPhones(Item\AbstractItinerary $segment, Timeline\PhonesSection $phonesSection): Phones
    {
        global $phoneForOptions;

        $localPhones = $phonesSection->getLocalPhones();
        $addressBookPhones = $phonesSection->getAddressBookPhones();
        $sectionName = $phonesSection->getName();

        $phones = new Phones($segment->getIcon(), $sectionName);

        if ($localPhones) {
            $phones->groups[] = ['name' => ($localGroup = $phones->getAutoGroupId())];

            foreach ($localPhones as $localPhone) {
                $phones->phones[] = [
                    'phone' => $localPhone['Phone'],
                    'group' => $localGroup,
                    'name' => StringUtils::isNotEmpty($localPhone['Name'] ?? null) ?
                        $localPhone['Name'] :
                        $phoneForOptions[PHONE_FOR_RESERVATIONS] ?? null,
                ];
            }
        }

        if ($addressBookPhones) {
            $geoCount = 0;
            $generalCount = 0;
            $generalGroup = $phones->getAutoGroupId();
            $geoGroup = $phones->getAutoGroupId();

            if (
                ($itineraryOwner = $segment->getItinerary()->getUser())
                && ($countryCodes = $this->countryRepository->getCountryCodes())
                && isset($countryCodes[$countryId = $itineraryOwner->getCountryid()])
                && !StringHandler::isEmpty($countryCodes[$countryId])
            ) {
                $phones->ownerCountry = strtolower($countryCodes[$countryId]);
            }

            $limit = self::PHONES_SECTION_LIMIT;

            foreach ($addressBookPhones as $phone) {
                if ($limit <= 0) {
                    break;
                }

                if ($isGeo = !StringHandler::isEmpty($phone['CountryCode'])) {
                    $group = $geoGroup;
                    $geoCount++;
                } else {
                    $group = $generalGroup;
                    $generalCount++;
                }

                $phones->phones[] = [
                    'phone' => $phone['Phone'],
                    'name' => GeneralUtils::coalesce($phone['EliteLevel'], @$phoneForOptions[$phone['PhoneFor']]),
                    'country' => $phone['Country'],
                    'region' => $phone['Country'],
                    'countryCode' => $isGeo ? strtolower($phone['CountryCode']) : null,
                    'group' => $group,
                    'rank' => null !== $phone['Rank'] ? (int) $phone['Rank'] : -1,
                ];
                $limit--;
            }

            if ($geoCount) {
                $phones->groups[] = [
                    'name' => $geoGroup,
                    'order' => [
                        Phones::GROUP_GEO,
                        '-rank',
                    ],
                ];
            }

            if ($generalCount) {
                $phones->groups[] = [
                    'name' => $generalGroup,
                    'order' => ['-rank'],
                ];
            }
        }

        return $phones;
    }

    protected function getNullableProperty($object, $property)
    {
        try {
            $value = $this->propertyAccessor->getValue($object, $property);

            if ([] === $value) {
                return null;
            }

            return $value;
        } catch (AccessException $exception) {
            return null;
        }
    }
}
