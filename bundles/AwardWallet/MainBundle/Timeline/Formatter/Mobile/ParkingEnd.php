<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Utils\ParkingHeaderResolver;
use AwardWallet\MainBundle\Timeline\Item\ParkingEnd as ParkingEndItem;
use Symfony\Contracts\Translation\TranslatorInterface;

class ParkingEnd implements ItemFormatterInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var BlockHelper
     */
    private $blockHelper;
    /**
     * @var ParkingStart
     */
    private $parkingStart;
    /**
     * @var Desanitizer
     */
    private $desanitizer;
    /**
     * @var ParkingHeaderResolver
     */
    private $parkingHeaderResolver;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper,
        ParkingStart $parkingStart,
        Desanitizer $desanitizer,
        ParkingHeaderResolver $parkingHeaderResolver
    ) {
        $this->translator = $translator;
        $this->blockHelper = $blockHelper;
        $this->parkingStart = $parkingStart;
        $this->desanitizer = $desanitizer;
        $this->parkingHeaderResolver = $parkingHeaderResolver;
    }

    /**
     * @param ParkingEndItem $item
     * @return Formatted\SegmentItem
     */
    public function format($item, Timeline\QueryOptions $queryOptions)
    {
        $formatted = $this->parkingStart->format($item, $queryOptions);
        $formatOptions = $queryOptions->getFormatOptions();
        $this->blockHelper->formatCommonSegmentProperties($item, $formatted, $formatOptions);

        if (!$formatOptions->supports(FormatHandler::PARKINGS_ICON)) {
            $formatted->icon = 'car';
        }

        /** @var Parking $source */
        $source = $item->getSource();
        $changes = $item->getChanges();

        $formatted->startDate =
        $formatted->endDate = $this->blockHelper->createLocalizedDate(
            DateTimeExtended::create($item->getStartDate(), $item->getTimezoneAbbr()),
            $changes ?
                (($oldDate = Utils::getChangedDateTime($changes, PropertiesList::END_DATE)) ? DateTimeExtended::create($oldDate, $item->getTimezoneAbbr()) : null) :
                null,
            'full'
        );

        if (!$source instanceof Parking) {
            return $formatted;
        }

        $title = $this->parkingHeaderResolver->getLocation($item->getItinerary());
        $formatted->listView = new Formatted\Components\ListView\SimpleView(
            $this->translator->trans('parking-ends-at', Utils::transParams(['%location%' => '']), 'trips'),
            $this->desanitizer->fullDesanitize($title)
        );

        $formatted->changed =
            $item->isChanged()
            && $changes
            && array_intersect(
                $changes->getChangedProperties(),
                [PropertiesList::END_DATE]
            );

        return $formatted;
    }
}
