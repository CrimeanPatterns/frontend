<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
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
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value\TimeRental;
use AwardWallet\MainBundle\Timeline\Item\Pickup as PickupItem;
use AwardWallet\MainBundle\Timeline\Item\Taxi as TaxiItem;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;

class Pickup implements ItemFormatterInterface
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
     * @var PropertiesDB
     */
    private $propertiesDB;
    /**
     * @var LazyVal
     */
    private $privateProperties;
    /**
     * @var LazyVal
     */
    private $excludedProperties;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper,
        Desanitizer $desanitizer,
        PropertiesDB $propertiesDB
    ) {
        $this->translator = $translator;
        $this->blockHelper = $blockHelper;
        $this->desanitizer = $desanitizer;
        $this->propertiesDB = $propertiesDB;
        $this->excludedProperties = lazy(function () {
            return it($this->propertiesDB->getProperties())
                ->filterNot(function (PropertyInfo $propertyInfo) {
                    return
                        $propertyInfo->hasTag(Tags::COMMON)
                        || $propertyInfo->hasTag(Tags::RENTAL);
                })
                ->keys()
                ->toArray();
        });
        $this->privateProperties = lazy(function () {
            return it($this->propertiesDB->getProperties())
                ->filter(function (PropertyInfo $propertyInfo) { return $propertyInfo->isPrivate(); })
                ->keys()
                ->toArray();
        });
    }

    /**
     * @param PickupItem|TaxiItem $item
     * @return Formatted\SegmentItem
     */
    public function format($item, Timeline\QueryOptions $queryOptions)
    {
        $formatted = new Timeline\Formatter\Mobile\Formatted\SegmentItem();
        $formatOptions = $queryOptions->getFormatOptions();

        if ($formatOptions->supports(FormatHandler::TAXI_RIDE)) {
            $icon = $item->getIcon();
        } else {
            $icon = 'car';
        }

        $isTaxi = $item instanceof TaxiItem;

        $this->blockHelper->formatCommonSegmentProperties($item, $formatted, $formatOptions);
        $formatted->icon = $icon;

        /** @var ?Timeline\Item\AbstractRental $pairedSegment */
        $pairedSegment = $isTaxi ? null : $item->getConnection();
        $changes = $item->getChanges();
        $isBlocksV2Enabled = $formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2);

        /** @var Entity\Rental $source */
        $source = $item->getSource();
        $title = $source->getRentalCompanyName(true);

        if ($formatOptions->supports(FormatHandler::DESANITIZED_STRINGS)) {
            $title = $this->desanitizer->fullDesanitize($title);
        }

        $formatted->listView = new Formatted\Components\ListView\SimpleView(
            $isTaxi ?
                null :
                $this->translator->trans('pick-up-at', Utils::transParams(['%location%' => '']), 'trips'),
            $title
        );

        // Confirmation #    7530077
        if (
            !$queryOptions->noPersonalData()
            && (null !== $item->getConfNo())
        ) {
            $formatted->blocks[] = new Block(Block::KIND_CONFNO, null, $this->translator->trans('timeline.section.conf.long'), $item->getConfNo());
        }

        // ☰ Xeptz rental company
        if (null !== $title) {
            $formatted->blocks[] = new Block(Block::KIND_TITLE, $icon, $title);
        }
        // ♽ AI Warning
        $this->blockHelper->formatAIWarning($item, $formatted, $formatOptions);
        // ↡ Pick-up
        $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans(/** @Desc("Pick-up") */ 'timeline.section.pickup'));
        $days = $isTaxi ? null : $source->getDays();

        if ($isTaxi) {
            $pickupDate = $item->getStartDate();
            $dropOffDate = $item->getEndDate();
            $dropOffChanges = $item->getChanges();
        } elseif ($item instanceof PickupItem) {
            // $pairedSegment instanceof DropoffItem
            $pickupDate = $item->getStartDate();
            $dropOffDate = $pairedSegment->getStartDate();
            $dropOffChanges = $pairedSegment->getChanges();
        } else {
            // $item instanceof DropoffItem
            // $pairedSegment instanceof PickupItem
            $pickupDate = $pairedSegment->getStartDate();
            $dropOffDate = $item->getStartDate();
            $dropOffChanges = $item->getChanges();
        }

        // ▤ in 8 days on Friday, April 2, 2028 for 3 nights
        if ($isBlocksV2Enabled) {
            $changedDays =
                !$isTaxi
                && (
                    (
                        $diffDropoff = (
                            (
                                $pairedSegment->getChanges() ?
                                    ($date = $pairedSegment->getChanges()->getpreviousvalue(PropertiesList::DROP_OFF_DATE)) :
                                    null
                            ) ?
                                new \DateTime("@{$date}") :
                                $source->getDropoffdatetime()
                        )
                    )
                    && (
                        $diffPickup = (
                            (
                                $changes ?
                                    ($date = $changes->getpreviousvalue(PropertiesList::PICK_UP_DATE)) :
                                    null
                            ) ?
                                new \DateTime("@{$date}") :
                                $source->getPickupdatetime()
                        )
                    )
                ) ?
                    max(1, (strtotime($diffDropoff->format('Y-m-d')) - strtotime($diffDropoff->format('Y-m-d'))) / SECONDS_PER_DAY) :
                    null;

            $oldStartDateFormatted = null;

            $dateBlock = Block::fromValue(
                new TimeRental(
                    $startDateFormatted = $this->blockHelper->createLocalizedDate(DateTimeExtended::create($pickupDate, $item->getTimezoneAbbr()), null, 'full'),
                    $days
                ),
                ($changes && ($oldDateTime = Utils::getChangedDateTime($changes, PropertiesList::PICK_UP_DATE))) ?
                    new TimeRental(
                        $oldStartDateFormatted = $this->blockHelper->createLocalizedDate(DateTimeExtended::create($oldDateTime, $item->getTimezoneAbbr()), null, 'full'),
                        (isset($changedDays) && ($changedDays !== $days)) ?
                            $changedDays : null
                    ) :
                    null
            );
            $formatted->startDate = clone $startDateFormatted;
            $formatted->startDate->old = $oldStartDateFormatted;
        } else {
            $oldStartDateFormatted = $changes ? Utils::getChangedDate($changes, PropertiesList::PICK_UP_DATE) : null;
            $dateBlock = Block::fromValue(
                new TimeRental($startDateFormatted = new Components\Date($pickupDate), $days),
                $oldStartDateFormatted
            );
            $formatted->startDate = $startDateFormatted;
            $formatted->startDate->old = $oldStartDateFormatted;
        }

        $formatted->blocks[] = $dateBlock;

        // ✞ Lenin st., Moscow, Russia
        $formatted->blocks[] = Block::fromValue(
            new Location($source->getPickuplocation()),
            ($changes && (null !== ($oldValue = $changes->getpreviousvalue(PropertiesList::PICK_UP_LOCATION)))) ?
                new Location($oldValue) :
                null
        );

        // Pick-Up Hours    20 hours
        if (
            (null !== $source->getPickuphours())
            && ($property = $this->blockHelper->translateSegmentProperty($item, PropertiesList::PICK_UP_HOURS))
        ) {
            [$name, $value] = $property;
            $formatted->blocks[] = Block::fromKindNameValue(
                Block::KIND_BOXED,
                $name,
                $value,
                $changes ?
                    $changes->getpreviousvalue(PropertiesList::PICK_UP_HOURS) :
                    null
            );
        }

        // ↡ Drop-off
        $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans(/** @Desc("Drop-off") */ 'timeline.section.drop'));

        // ▤ 12:00 PM(UTC+10)  06/24/2014
        if ($isBlocksV2Enabled) {
            $formatted->blocks[] = Block::fromValue(
                new TimeRental(
                    $formatted->endDate = $this->blockHelper->createLocalizedDate(DateTimeExtended::create($dropOffDate, $item->getTimezoneAbbr()), null, 'full'),
                    null
                ),
                ($dropOffChanges && ($oldDateTime = Utils::getChangedDateTime($dropOffChanges, PropertiesList::DROP_OFF_DATE))) ?
                    new TimeRental(
                        $this->blockHelper->createLocalizedDate(DateTimeExtended::create($oldDateTime, $item->getTimezoneAbbr()), null, 'full'),
                        null
                    ) :
                    null
            );
        } else {
            $formatted->blocks[] = Block::fromValue(
                new TimeRental(new Components\Date($dropOffDate), null)
            );
        }

        // ✞ Pushkin st., Moscow, Russia
        $formatted->blocks[] = Block::fromValue(
            new Location($source->getDropofflocation()),
            ($dropOffChanges && (null !== ($oldValue = $dropOffChanges->getpreviousvalue(PropertiesList::DROP_OFF_LOCATION)))) ?
                new Location($oldValue) :
                null
        );

        $diffTrackedProperties = [];

        $formatted->changed =
            $item->isChanged()
            && $changes
            && array_intersect(
                $changes->getChangedProperties(),
                $diffTrackedProperties = $isBlocksV2Enabled ?
                    [
                        PropertiesList::COST,
                        PropertiesList::CAR_MODEL,
                        PropertiesList::CAR_TYPE,
                        PropertiesList::DISCOUNT,
                        PropertiesList::ACCOUNT_NUMBERS,
                        PropertiesList::TOTAL_CHARGE,
                        PropertiesList::PICK_UP_DATE,
                        PropertiesList::DROP_OFF_DATE,
                        PropertiesList::DROP_OFF_HOURS,
                        PropertiesList::PICK_UP_DATE,
                        PropertiesList::PICK_UP_LOCATION,
                        PropertiesList::PICK_UP_HOURS,
                        PropertiesList::DROP_OFF_LOCATION,
                        PropertiesList::DROP_OFF_FAX,
                        PropertiesList::PICK_UP_FAX,
                        PropertiesList::CAR_TYPE,
                        PropertiesList::CAR_MODEL,
                        PropertiesList::DISCOUNT,
                    ] :
                    ['PickupDatetime']
            );

        $excludedProperties = \array_merge(
            [
                PropertiesList::RENTAL_COMPANY,
                PropertiesList::PICK_UP_HOURS,
                PropertiesList::CONFIRMATION_NUMBER,
                PropertiesList::PICK_UP_DATE,
                PropertiesList::DROP_OFF_DATE,
                PropertiesList::PICK_UP_LOCATION,
                PropertiesList::DROP_OFF_LOCATION,
                PropertiesList::PICK_UP_HOURS,
                PropertiesList::DROP_OFF_HOURS,
                PropertiesList::RETRIEVE_FROM,
                PropertiesList::NOTES,
                PropertiesList::CANCELLATION_POLICY,
                PropertiesList::CAR_IMAGE_URL,
                PropertiesList::PICK_UP_PHONE,
                PropertiesList::DROP_OFF_PHONE,
                PropertiesList::COMMENT,
            ],
            $source->getAllConfirmationNumbers() === [$source->getConfirmationNumber()] ? [PropertiesList::CONFIRMATION_NUMBERS] : []
        );

        if ($queryOptions->noPersonalData()) {
            $excludedProperties = array_merge($excludedProperties, $this->privateProperties->getValue());
        }

        $excludedProperties = \array_merge(
            $excludedProperties,
            $this->excludedProperties->getValue()
        );
        $propertiesOrder = [
            PropertiesList::RENTAL_COMPANY,
            PropertiesList::CONFIRMATION_NUMBER,
            PropertiesList::CONFIRMATION_NUMBERS,
            PropertiesList::PICK_UP_DATE,
            PropertiesList::PICK_UP_LOCATION,
            PropertiesList::DROP_OFF_DATE,
            PropertiesList::DROP_OFF_LOCATION,
            PropertiesList::PICK_UP_PHONE,
            PropertiesList::TRAVELER_NAMES,
            PropertiesList::ACCOUNT_NUMBERS,
            PropertiesList::TRAVEL_AGENCY_ACCOUNT_NUMBERS,
            PropertiesList::CAR_TYPE,
            PropertiesList::CAR_MODEL,
            PropertiesList::PICK_UP_HOURS,
            PropertiesList::PICK_UP_FAX,
            PropertiesList::DROP_OFF_HOURS,
            PropertiesList::DROP_OFF_PHONE,
            PropertiesList::DROP_OFF_FAX,
            PropertiesList::EARNED_AWARDS,
            PropertiesList::RESERVATION_DATE,
            PropertiesList::SPENT_AWARDS,
            PropertiesList::COST,
            PropertiesList::FEES,
            PropertiesList::DISCOUNT,
            PropertiesList::TOTAL_CHARGE,
            PropertiesList::DISCOUNT_DETAILS,
            PropertiesList::PRICED_EQUIPMENT,
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
            && (null !== $spotHeroUrl = Utils::parkingUrl($source->getPickupgeotagid(), $source->getPickupdatetime(), '+1 hour'))
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
