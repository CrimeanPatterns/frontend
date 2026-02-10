<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertiesDB;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertyInfo;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\Tags;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Block;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\ListView;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\BaseMenu;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value\Location;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value\TimeReservation;
use AwardWallet\MainBundle\Timeline\Item\Checkin as CheckinItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;

class Checkin implements ItemFormatterInterface
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
        $this->excludedProperties = lazy(function () {
            return it($this->propertiesDB->getProperties())
                ->filterNot(function (PropertyInfo $propertyInfo) {
                    return
                        $propertyInfo->hasTag(Tags::COMMON)
                        || $propertyInfo->hasTag(Tags::RESERVATION);
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
     * @param CheckinItem $item
     * @return Formatted\SegmentItem
     */
    public function format($item, Timeline\QueryOptions $queryOptions)
    {
        $formatted = new Timeline\Formatter\Mobile\Formatted\SegmentItem();
        $formatOptions = $queryOptions->getFormatOptions();
        $this->blockHelper->formatCommonSegmentProperties($item, $formatted, $formatOptions);
        /** @var Entity\Reservation $source */
        $source = $item->getSource();
        $changes = $item->getChanges();
        /** @var Timeline\Item\Checkout $checkout */
        $checkout = $item->getConnection();
        $isBlocksV2Enabled = $formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2);

        $title = Utils::getReservationName($source);

        if ($formatOptions->supports(FormatHandler::DESANITIZED_STRINGS)) {
            $title = $this->desanitizer->fullDesanitize($title);
        }

        $formatted->listView = new ListView\SimpleView(
            $this->translator->trans('check-in-at', Utils::transParams(['%location%' => '']), 'trips'),
            $title
        );

        // Confirmation #   100500
        if (
            !$queryOptions->noPersonalData()
            && (null !== $item->getConfNo())
        ) {
            $formatted->blocks[] = new Block(Block::KIND_CONFNO, null, $this->translator->trans('timeline.section.conf.long'), $item->getConfNo());
        }

        // ☰ Sheraton Ritz Carlton
        if (null !== $title) {
            $formatted->blocks[] = new Block(Block::KIND_TITLE, $item->getIcon(), $title);
        }

        // ♽ AI Warning
        $this->blockHelper->formatAIWarning($item, $formatted, $formatOptions);
        // ↡ Check-In
        $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans(/** @Desc("Check-in") */ 'itineraries.reservation.check-in-date', [], 'trips'));
        // ▤ in 8 days on Friday, April 2, 2028 for 3 nights

        if ($isBlocksV2Enabled) {
            $changedNights =
                (
                    (
                        $diffCheckout = (
                            (
                                $checkout->getChanges() ?
                                        ($date = $checkout->getChanges()->getpreviousvalue(PropertiesList::CHECK_OUT_DATE)) :
                                        null
                            ) ?
                                new \DateTime("@{$date}") :
                                $source->getCheckoutdate()
                        )
                    )
                    && (
                        $diffCheckin = (
                            (
                                $changes ?
                                    ($date = $changes->getpreviousvalue(PropertiesList::CHECK_IN_DATE)) :
                                    null
                            ) ?
                                new \DateTime("@{$date}") :
                                $source->getCheckindate()
                        )
                    )
                ) ?
                    max(1, (strtotime($diffCheckout->format('Y-m-d')) - strtotime($diffCheckin->format('Y-m-d'))) / SECONDS_PER_DAY) :
                    null;

            $oldStartDateFormatted = null;

            $dateBlock = Block::fromValue(
                new TimeReservation(
                    $startDateFormatted = $this->blockHelper->createLocalizedDate(
                        DateTimeExtended::create($item->getStartDate(), $item->getTimezoneAbbr()),
                        null,
                        'full'
                    ),
                    $source->getNights()
                ),
                ($changes && ($oldDateTime = Utils::getChangedDateTime($changes, PropertiesList::CHECK_IN_DATE))) ?
                    new TimeReservation(
                        $oldStartDateFormatted = $this->blockHelper->createLocalizedDate(
                            DateTimeExtended::create($oldDateTime, $item->getTimezoneAbbr()),
                            null,
                            'full'
                        ),
                        (isset($changedNights) && ($changedNights !== $source->getNights())) ?
                            $changedNights :
                            null
                    ) :
                    null
            );
            $formatted->startDate = clone $startDateFormatted;
            $formatted->startDate->old = $oldStartDateFormatted;
        } else {
            $oldStartDateFormatted = $changes ? Utils::getChangedDate($changes, PropertiesList::CHECK_IN_DATE) : null;
            $dateBlock = Block::fromValue(
                new TimeReservation($startDateFormatted = new Components\Date($item->getStartDate()), $source->getNights()),
                $oldStartDateFormatted
            );
            $formatted->startDate = $startDateFormatted;
            $formatted->startDate->old = $oldStartDateFormatted;
        }

        $formatted->blocks[] = $dateBlock;

        // ✞ Lenin st., Moscow, Russia
        if (!StringUtils::isEmpty($address = $source->getAddress())) {
            $formatted->blocks[] = Block::fromValue(
                new Location($address),
                ($changes && (null !== ($oldValue = $changes->getpreviousvalue(PropertiesList::ADDRESS)))) ?
                    new Location($oldValue) :
                    null
            );
        }

        // ↡ Check out
        $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans(/** @Desc("Check out") */ 'itineraries.reservation.check-out-date', [], 'trips'));

        // ▤ 12:00 PM(UTC+10)  06/24/2014
        if ($isBlocksV2Enabled) {
            $formatted->blocks[] = Block::fromValue(
                new TimeReservation(
                    $formatted->endDate = $this->blockHelper->createLocalizedDate(
                        DateTimeExtended::create($checkout->getStartDate(), $checkout->getTimezoneAbbr()),
                        null,
                        'full'
                    ),
                    null
                ),
                ($checkout->getChanges() && ($oldDateTime = Utils::getChangedDateTime($checkout->getChanges(), PropertiesList::CHECK_OUT_DATE))) ?
                    new TimeReservation(
                        $this->blockHelper->createLocalizedDate(
                            DateTimeExtended::create($oldDateTime, $checkout->getTimezoneAbbr()),
                            null,
                            'full'
                        ),
                        null
                    ) :
                    null
            );
        } else {
            $formatted->blocks[] = Block::fromValue(
                new TimeReservation(new Components\Date($checkout->getStartDate()), null)
            );
        }

        $diffTrackedProperties = [];

        $formatted->changed =
            $item->isChanged()
            && $item->getChanges()
            && array_intersect(
                $item->getChanges()->getChangedProperties(),
                $diffTrackedProperties = $isBlocksV2Enabled ?
                    [
                        PropertiesList::CHECK_IN_DATE,
                        PropertiesList::CHECK_OUT_DATE,
                        PropertiesList::COST,
                        PropertiesList::TRAVELER_NAMES,
                        PropertiesList::GUEST_COUNT,
                        PropertiesList::KIDS_COUNT,
                        PropertiesList::ROOM_RATE,
                        PropertiesList::ROOM_RATE_DESCRIPTION,
                        PropertiesList::ROOM_COUNT,
                        PropertiesList::FREE_NIGHTS,
                        PropertiesList::ROOM_LONG_DESCRIPTION,
                        PropertiesList::SPENT_AWARDS,
                        PropertiesList::TOTAL_CHARGE,
                    ] :
                    [PropertiesList::CHECK_IN_DATE]
            );

        $excludedProperties = \array_merge(
            [
                PropertiesList::PHONE,
                PropertiesList::HOTEL_NAME,
                PropertiesList::CHECK_OUT_DATE,
                PropertiesList::CHECK_IN_DATE,
                PropertiesList::ADDRESS,
                PropertiesList::CONFIRMATION_NUMBER,
                PropertiesList::RETRIEVE_FROM,
                PropertiesList::NOTES,
                PropertiesList::CANCELLATION_POLICY,
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
            PropertiesList::HOTEL_NAME,
            PropertiesList::CONFIRMATION_NUMBER,
            PropertiesList::CONFIRMATION_NUMBERS,
            PropertiesList::ADDRESS,
            PropertiesList::CHECK_IN_DATE,
            PropertiesList::CHECK_OUT_DATE,
            PropertiesList::PHONE,
            PropertiesList::ACCOUNT_NUMBERS,
            PropertiesList::TRAVELER_NAMES,
            PropertiesList::GUEST_COUNT,
            PropertiesList::CANCELLATION_POLICY,
            PropertiesList::EARNED_AWARDS,
            PropertiesList::TRAVEL_AGENCY_EARNED_AWARDS,
            PropertiesList::FAX,
            PropertiesList::KIDS_COUNT,
            PropertiesList::ROOM_RATE,
            PropertiesList::ROOM_RATE_DESCRIPTION,
            PropertiesList::RESERVATION_DATE,
            PropertiesList::ROOM_SHORT_DESCRIPTION,
            PropertiesList::ROOM_COUNT,
            PropertiesList::FREE_NIGHTS,
            PropertiesList::ROOM_LONG_DESCRIPTION,
            PropertiesList::SPENT_AWARDS,
            PropertiesList::COST,
            PropertiesList::FEES,
            PropertiesList::DISCOUNT,
            PropertiesList::TOTAL_CHARGE,
            PropertiesList::NON_REFUNDABLE,
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
            && (null !== $spotHeroUrl = Utils::parkingUrl($source->getGeotagid(), $source->getCheckindate(), '-1 hour'))
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

        if ($formatOptions->supports(FormatHandler::OFFER)) {
            $block = Block::fromKindValue(Block::KIND_OFFER, $this->translator->trans('uber.ad.1'));
            $url = 'https://awardwallet.com/blog/link/uber';
            $block->link = new Components\Link(
                $url,
                $this->translator->trans('uber.ad.2')
            );
            $formatted->blocks[] = $block;
        }

        return $formatted;
    }
}
