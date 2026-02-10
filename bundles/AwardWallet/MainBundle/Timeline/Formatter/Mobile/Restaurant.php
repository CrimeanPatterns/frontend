<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertiesDB;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertyInfo;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\Tags;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Block;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\BaseMenu;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value\Location;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value\Time;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;

class Restaurant implements ItemFormatterInterface
{
    /**
     * @var Desanitizer
     */
    private $desanitizer;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var BlockHelper
     */
    private $blockHelper;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var PropertiesDB
     */
    private $propertiesDB;
    /**
     * @var LazyVal
     */
    private $privatePropertiesVal;
    /**
     * @var LazyVal
     */
    private $excludedPropertiesVal;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper,
        UrlGeneratorInterface $urlGenerator,
        Desanitizer $desanitizer,
        PropertiesDB $propertiesDB
    ) {
        $this->translator = $translator;
        $this->blockHelper = $blockHelper;
        $this->urlGenerator = $urlGenerator;
        $this->desanitizer = $desanitizer;
        $this->propertiesDB = $propertiesDB;
        $this->privatePropertiesVal = lazy(function () {
            return it($this->propertiesDB->getProperties())
                ->filter(function (PropertyInfo $property) { return $property->isPrivate(); })
                ->keys()
                ->toArray();
        });
        $this->excludedPropertiesVal = lazy(function () {
            return it($this->propertiesDB->getProperties())
                ->filterNot(function (PropertyInfo $propertyInfo) {
                    return
                        $propertyInfo->hasTag(Tags::COMMON)
                        || $propertyInfo->hasTag(Tags::RESTAURANT);
                })
                ->keys()
                ->toArray();
        });
    }

    /**
     * @param Timeline\Item\Event $item
     * @return Formatted\SegmentItem
     */
    public function format($item, Timeline\QueryOptions $queryOptions)
    {
        /** @var Entity\Restaurant $itinerary */
        $itinerary = $item->getItinerary();

        if ($itinerary->getEventType() === EVENT_SHOW) {
            $icon = 'event-show';
        } else {
            $icon = $item->getIcon();
        }

        $formatted = new Timeline\Formatter\Mobile\Formatted\SegmentItem();
        $formatOptions = $queryOptions->getFormatOptions();
        $isBlocksV2Enabled = $formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2);
        $this->blockHelper->formatCommonSegmentProperties($item, $formatted, $formatOptions);
        $formatted->icon = $icon;

        /** @var Entity\Restaurant $source */
        $source = $item->getSource();
        $changes = $item->getChanges();

        $title = Utils::getRestaurantName($source);

        if ($formatOptions->supports(FormatHandler::DESANITIZED_STRINGS)) {
            $title = $this->desanitizer->fullDesanitize($title);
        }

        $formatted->listView = new Formatted\Components\ListView\SimpleView(null, $title);

        // Confirmation #    7530077
        if (
            !$queryOptions->noPersonalData()
            && (null !== $item->getConfNo())
        ) {
            $formatted->blocks[] = new Block(Block::KIND_CONFNO, null, $this->translator->trans('timeline.section.conf.long'), $item->getConfNo());
        }

        // ☠ McDonalds
        if (null !== $title) {
            $formatted->blocks[] = new Block(Block::KIND_TITLE, $icon, $title);
        }

        // ♽ AI Warning
        $this->blockHelper->formatAIWarning($item, $formatted, $formatOptions);
        // ↡ Start
        $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans(/** @Desc("Start") */ 'itineraries.restaurant.start-date', [], 'trips'));
        $isReadableRestaurantDatesEnabled = $formatOptions->supports(FormatHandler::READABLE_TRIP_AND_RESTAURANT_DATES);

        // ▤ in 8 days on Friday, April 2, 2028 for 3 nights
        if ($isBlocksV2Enabled) {
            $oldStartDateFormatted = null;
            $dateBlock = Block::fromValue(
                new Time($startDateFormatted = $this->blockHelper->createLocalizedDate(
                    DateTimeExtended::create($item->getStartDate(), $item->getTimezoneAbbr()),
                    null,
                    $isReadableRestaurantDatesEnabled ? LocalizeService::FORMAT_MEDIUM : LocalizeService::FORMAT_SHORT,
                )),
                ($changes && ($oldDateTime = Utils::getChangedDateTime($changes, PropertiesList::START_DATE))) ?
                    new Time($oldStartDateFormatted = $this->blockHelper->createLocalizedDate(
                        DateTimeExtended::create($oldDateTime, $item->getTimezoneAbbr()),
                        null,
                        $isReadableRestaurantDatesEnabled ? LocalizeService::FORMAT_MEDIUM : LocalizeService::FORMAT_SHORT,
                    )) :
                    null
            );
            $formatted->startDate = clone $startDateFormatted;
            $formatted->startDate->old = $oldStartDateFormatted;
        } else {
            $oldStartDateFormatted = $changes ? Utils::getChangedDate($changes, PropertiesList::START_DATE) : null;
            $dateBlock = Block::fromValue(
                new Time($startDateFormatted = new Components\Date($item->getStartDate())),
                $oldStartDateFormatted
            );
            $formatted->startDate = $startDateFormatted;
            $formatted->startDate->old = $oldStartDateFormatted;
        }

        $formatted->blocks[] = $dateBlock;

        // ✞ Lenin st., Moscow, Russia
        if (null !== $source->getAddress()) {
            $formatted->blocks[] = Block::fromValue(
                new Location($source->getAddress()),
                $changes && (null !== ($address = $changes->getpreviousvalue(PropertiesList::ADDRESS))) ? new Location($address) : null
            );
        }

        if ($property = $this->blockHelper->translateSegmentProperty($item, PropertiesList::GUEST_COUNT)) {
            [$name, $value] = $property;
            $formatted->blocks[] = Block::fromKindNameValue(
                Block::KIND_BOXED,
                $name,
                $value,
                $changes ? $changes->getpreviousvalue(PropertiesList::GUEST_COUNT) : null
            );
        }

        // ↡ End
        if ($item->getEndDate()) {
            $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans(/** @Desc("End") */ 'itineraries.restaurant.end-date', [], 'trips'));
            // ▤ 12:00 PM(UTC+10)  06/24/2014

            if ($isBlocksV2Enabled) {
                $formatted->blocks[] = Block::fromValue(
                    new Time($formatted->endDate = $this->blockHelper->createLocalizedDate(
                        DateTimeExtended::create($item->getEndDate(), $item->getTimezoneAbbr()),
                        null,
                        $isReadableRestaurantDatesEnabled ? LocalizeService::FORMAT_MEDIUM : LocalizeService::FORMAT_SHORT,
                    )),
                    ($changes && ($oldDateTime = Utils::getChangedDateTime($changes, PropertiesList::END_DATE))) ?
                        new Time($this->blockHelper->createLocalizedDate(
                            DateTimeExtended::create($oldDateTime, $item->getTimezoneAbbr()),
                            null,
                            $isReadableRestaurantDatesEnabled ? LocalizeService::FORMAT_MEDIUM : LocalizeService::FORMAT_SHORT,
                        )) :
                        null
                );
            } else {
                $formatted->blocks[] = Block::fromValue(
                    new Time(new Components\Date($item->getEndDate())),
                    $changes ?
                        Utils::getChangedDate($changes, PropertiesList::END_DATE) :
                        null
                );
            }
        } elseif ($isBlocksV2Enabled) {
            $formatted->endDate = $formatted->startDate;
        }

        $diffTrackedProperties = [];

        $formatted->changed =
            $item->isChanged()
            && $changes
            && array_intersect(
                $diffTrackedProperties = $changes->getChangedProperties(),
                [
                    PropertiesList::ACCOUNT_NUMBERS,
                    PropertiesList::TRAVELER_NAMES,
                    PropertiesList::END_DATE,
                    PropertiesList::GUEST_COUNT,
                    PropertiesList::SPENT_AWARDS,
                    PropertiesList::START_DATE,
                    PropertiesList::TOTAL_CHARGE,
                ]
            );

        $excludedProperties = \array_merge(
            [
                PropertiesList::GUEST_COUNT,
                PropertiesList::ADDRESS,
                PropertiesList::CONFIRMATION_NUMBER,
                PropertiesList::START_DATE,
                PropertiesList::END_DATE,
                PropertiesList::NOTES,
                PropertiesList::CANCELLATION_POLICY,
                PropertiesList::EVENT_NAME,
                PropertiesList::RETRIEVE_FROM,
                PropertiesList::COMMENT,
            ],
            $source->getAllConfirmationNumbers() === [$source->getConfirmationNumber()] ? [PropertiesList::CONFIRMATION_NUMBERS] : []
        );
        $excludedProperties = \array_merge(
            $excludedProperties,
            $this->excludedPropertiesVal->getValue()
        );

        if ($queryOptions->noPersonalData()) {
            $excludedProperties = array_merge($excludedProperties, $this->privatePropertiesVal->getValue());
        }

        $propertiesOrder = [
            PropertiesList::ACCOUNT_NUMBERS,
            PropertiesList::ADDRESS,
            PropertiesList::CONFIRMATION_NUMBER,
            PropertiesList::START_DATE,
            PropertiesList::END_DATE,
            PropertiesList::PHONE,
            PropertiesList::TRAVELER_NAMES,
            PropertiesList::EARNED_AWARDS,
            PropertiesList::EVENT_NAME,
            PropertiesList::GUEST_COUNT,
            PropertiesList::RESERVATION_DATE,
            PropertiesList::SPENT_AWARDS,
            PropertiesList::COST,
            PropertiesList::FEES,
            PropertiesList::DISCOUNT,
            PropertiesList::TOTAL_CHARGE,
        ];

        // Show more >>
        $extPropBlocks = $this->blockHelper->getExtPropertiesBlocks(
            $item,
            $formatOptions,
            $excludedProperties,
            $diffTrackedProperties,
            $propertiesOrder,
            !$queryOptions->noPersonalData()
        );

        $this->blockHelper->formatNotes($item, $formatted, $queryOptions, $extPropBlocks);

        if ($extPropBlocks) {
            $formatted->addBlocksOrFold($extPropBlocks, ($formatOptions->supports(FormatHandler::NO_SHOW_MORE)) ? 100 : 7);
        }

        $formatted->menu = $this->blockHelper->formatBaseMenuProperties(new BaseMenu(), $item, $formatOptions, $queryOptions->noPersonalData());
        $this->blockHelper->formatConfirmChanges($item, $formatted, $formatOptions);

        if (
            $formatOptions->supports(FormatHandler::PARKINGS_ADS)
            && (null !== $spotHeroUrl = Utils::parkingUrl($itinerary->getGeotagid(), $itinerary->getStartdate(), '-1 hour'))
        ) {
            $formatted->menu->parkingUrl = $spotHeroUrl;
        }

        if (
            !$queryOptions->noPersonalData()
            && $formatOptions->supports(FormatHandler::SOURCES)
        ) {
            $this->blockHelper->formatSegmentSources($item, $formatted, $formatOptions);
        }

        $this->blockHelper->formatNoteFiles($item, $formatted, $formatOptions);

        return $formatted;
    }
}
