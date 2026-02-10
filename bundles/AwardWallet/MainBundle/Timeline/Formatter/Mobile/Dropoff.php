<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Item\Dropoff as DropoffItem;
use Symfony\Contracts\Translation\TranslatorInterface;

class Dropoff implements ItemFormatterInterface
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
     * @var Pickup
     */
    private $pickup;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper,
        Pickup $pickup
    ) {
        $this->translator = $translator;
        $this->blockHelper = $blockHelper;
        $this->pickup = $pickup;
    }

    /**
     * @param DropoffItem $item
     * @return Formatted\SegmentItem
     */
    public function format($item, Timeline\QueryOptions $queryOptions)
    {
        $formatted = $this->pickup->format($item, $queryOptions);
        $formatOptions = $queryOptions->getFormatOptions();
        $this->blockHelper->formatCommonSegmentProperties($item, $formatted, $formatOptions);
        /** @var Entity\Rental $source */
        $source = $item->getSource();
        $changes = $item->getChanges();

        if ($formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2)) {
            $formatted->startDate =
            $formatted->endDate = $this->blockHelper->createLocalizedDate(
                DateTimeExtended::create($item->getStartDate(), $item->getTimezoneAbbr()),
                $changes ?
                    (($oldDate = Utils::getChangedDateTime($changes, PropertiesList::DROP_OFF_DATE)) ? DateTimeExtended::create($oldDate, $item->getTimezoneAbbr()) : null) :
                    null,
                'full'
            );
        } else {
            $formatted->startDate = new Formatted\Components\Date(
                $item->getStartDate(),
                $changes ?
                    Utils::getChangedDateTime($changes, PropertiesList::DROP_OFF_DATE) :
                    null
            );
        }

        if (!$source instanceof Entity\Rental) {
            return $formatted;
        }

        $formatted->listView = new Formatted\Components\ListView\SimpleView(
            $this->translator->trans('drop-off-at', Utils::transParams(['%location%' => '']), 'trips'),
            $source->getRentalCompanyName(true)
        );

        $formatted->changed =
            $item->isChanged()
            && $changes
            && array_intersect(
                $changes->getChangedProperties(),
                [PropertiesList::DROP_OFF_DATE]
            );

        return $formatted;
    }
}
