<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertiesDB;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertyInfo;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\Tags;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Block;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\BaseMenu;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value\Location;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value\TimeRental;
use AwardWallet\MainBundle\Timeline\Formatter\Utils\ParkingHeaderResolver;
use AwardWallet\MainBundle\Timeline\Item\ParkingStart as ParkingStartItem;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;

class ParkingStart implements ItemFormatterInterface
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
    private $privateProperties;
    /**
     * @var LazyVal
     */
    private $excludedProperties;
    /**
     * @var ParkingHeaderResolver
     */
    private $parkingHeaderResolver;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper,
        UrlGeneratorInterface $urlGenerator,
        Desanitizer $desanitizer,
        PropertiesDB $propertiesDB,
        ParkingHeaderResolver $parkingHeaderResolver
    ) {
        $this->translator = $translator;
        $this->blockHelper = $blockHelper;
        $this->urlGenerator = $urlGenerator;
        $this->desanitizer = $desanitizer;
        $this->propertiesDB = $propertiesDB;
        $this->excludedProperties = lazy(function () {
            return it($this->propertiesDB->getProperties())
                ->filterNot(function (PropertyInfo $propertyInfo) {
                    return
                        $propertyInfo->hasTag(Tags::COMMON)
                        || $propertyInfo->hasTag(Tags::PARKING);
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
        $this->parkingHeaderResolver = $parkingHeaderResolver;
    }

    /**
     * @param ParkingStartItem $item
     * @param Timeline\QueryOptions $queryOptions
     * @return Formatted\SegmentItem
     */
    public function format($item, QueryOptions $queryOptions)
    {
        $formatted = new Timeline\Formatter\Mobile\Formatted\SegmentItem();
        $formatOptions = $queryOptions->getFormatOptions();
        $this->blockHelper->formatCommonSegmentProperties($item, $formatted, $formatOptions);

        /** @var Timeline\Item\ParkingEnd $parkingEnd */
        $parkingEnd = $item->getConnection();
        $changes = $item->getChanges();
        /** @var Parking $source */
        $source = $item->getSource();

        if (!$formatOptions->supports(FormatHandler::PARKINGS_ICON)) {
            $formatted->icon = 'car';
        }

        $title = $this->parkingHeaderResolver->getLocation($item->getItinerary());
        $formatted->listView = new Formatted\Components\ListView\SimpleView(
            $this->translator->trans('parking-starts-at', Utils::transParams(['%location%' => '']), 'trips'),
            $this->desanitizer->fullDesanitize($title)
        );

        // Confirmation #    7530077
        if (
            !$queryOptions->noPersonalData()
            && (null !== $item->getConfNo())
        ) {
            $formatted->blocks[] = new Block(Block::KIND_CONFNO, null, $this->translator->trans('timeline.section.conf.long'), $item->getConfNo());
        }

        // ☰ Xeptz parking
        if (null !== $title) {
            $formatted->blocks[] = new Block(Block::KIND_TITLE, $formatted->icon, $title);
        }

        // ♽ AI Warning
        $this->blockHelper->formatAIWarning($item, $formatted, $formatOptions);
        // ↡ Start
        $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans('timeline.section.start'));
        $days = $source->getDays();

        // ▤ in 8 days on Friday, April 2, 2028 for 3 nights
        $changedDays =
            (
                (
                    $diffEnd = (
                        (
                            $parkingEnd->getChanges() ?
                                ($date = $parkingEnd->getChanges()->getpreviousvalue(PropertiesList::END_DATE)) :
                                null
                        ) ?
                            new \DateTime("@{$date}") :
                            $source->getEndDatetime()
                    )
                )
                && (
                    $diffStart = (
                        (
                            $changes ?
                                ($date = $changes->getpreviousvalue(PropertiesList::START_DATE)) :
                                null
                        ) ?
                            new \DateTime("@{$date}") :
                            $source->getStartDatetime()
                    )
                )
            ) ?
                max(1, (strtotime($diffEnd->format('Y-m-d')) - strtotime($diffEnd->format('Y-m-d'))) / SECONDS_PER_DAY) :
                null;

        $oldStartDateFormatted = null;

        $formatted->blocks[] = Block::fromValue(
            new TimeRental(
                $startDateFormatted = $this->blockHelper->createLocalizedDate(
                    DateTimeExtended::create(
                        $item->getStartDate(),
                        $item->getTimezoneAbbr()
                    ),
                    null,
                    'full'
                ),
                $days
            ),
            ($changes && ($oldDateTime = Utils::getChangedDateTime($changes, PropertiesList::START_DATE))) ?
                new TimeRental(
                    $oldStartDateFormatted = $this->blockHelper->createLocalizedDate(
                        DateTimeExtended::create(
                            $oldDateTime,
                            $item->getTimezoneAbbr()
                        ),
                        null,
                        'full'
                    ),
                    (isset($changedDays) && ($changedDays !== $days)) ?
                        $changedDays : null
                ) :
                null
        );
        $formatted->startDate = clone $startDateFormatted;
        $formatted->startDate->old = $oldStartDateFormatted;

        // ✞ Lenin st., Moscow, Russia
        $formatted->blocks[] = Block::fromValue(
            new Location($source->getLocation()),
            ($changes && (null !== ($oldValue = $changes->getpreviousvalue(PropertiesList::LOCATION)))) ?
                new Location($oldValue) :
                null
        );

        if ($parkingEnd) {
            $endDate = $parkingEnd->getStartDate();
            $endChanges = $parkingEnd->getChanges();
        } else {
            $endDate = $item->getEndDate();
            $endChanges = $item->getChanges();
        }

        // ↡ End
        $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans('timeline.section.end'));
        // ▤ 12:00 PM(UTC+10)  06/24/2014
        $formatted->blocks[] = Block::fromValue(
            new TimeRental(
                $formatted->endDate = $this->blockHelper->createLocalizedDate(DateTimeExtended::create($endDate, $item->getTimezoneAbbr()), null, 'full'),
                null
            ),
            ($endChanges && ($oldDateTime = Utils::getChangedDateTime($endChanges, PropertiesList::END_DATE))) ?
                new TimeRental(
                    $this->blockHelper->createLocalizedDate(DateTimeExtended::create($oldDateTime, $item->getTimezoneAbbr()), null, 'full'),
                    null
                ) :
                null
        );
        $diffTrackedProperties = [];

        $formatted->changed =
            $item->isChanged()
            && $changes
            && array_intersect(
                $changes->getChangedProperties(),
                $diffTrackedProperties =
                    [
                        PropertiesList::ACCOUNT_NUMBERS,
                        PropertiesList::CAR_DESCRIPTION,
                        PropertiesList::COST,
                        PropertiesList::DISCOUNT,
                        PropertiesList::END_DATE,
                        PropertiesList::FAX,
                        PropertiesList::LICENSE_PLATE,
                        PropertiesList::LOCATION,
                        PropertiesList::PHONE,
                        PropertiesList::SPOT_NUMBER,
                        PropertiesList::START_DATE,
                        PropertiesList::TOTAL_CHARGE,
                    ]
            );

        $excludedProperties = \array_merge(
            [
                PropertiesList::CANCELLATION_POLICY,
                PropertiesList::CONFIRMATION_NUMBER,
                PropertiesList::END_DATE,
                PropertiesList::LOCATION,
                PropertiesList::PHONE,
                PropertiesList::NOTES,
                PropertiesList::PARKING_COMPANY,
                PropertiesList::RETRIEVE_FROM,
                PropertiesList::START_DATE,
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
            PropertiesList::LICENSE_PLATE,
            PropertiesList::SPOT_NUMBER,
            PropertiesList::CONFIRMATION_NUMBERS,
            PropertiesList::TRAVELER_NAMES,
            PropertiesList::CAR_DESCRIPTION,
            PropertiesList::ACCOUNT_NUMBERS,
            PropertiesList::TRAVEL_AGENCY_ACCOUNT_NUMBERS,
            PropertiesList::FAX,
            PropertiesList::EARNED_AWARDS,
            PropertiesList::RESERVATION_DATE,
            PropertiesList::SPENT_AWARDS,
            PropertiesList::COST,
            PropertiesList::FEES,
            PropertiesList::DISCOUNT,
            PropertiesList::TOTAL_CHARGE,
            PropertiesList::RATE_TYPE,
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
            !$queryOptions->noPersonalData()
            && $formatOptions->supports(FormatHandler::SOURCES)
        ) {
            $this->blockHelper->formatSegmentSources($item, $formatted, $formatOptions);
        }

        $this->blockHelper->formatNoteFiles($item, $formatted, $formatOptions);

        return $formatted;
    }
}
