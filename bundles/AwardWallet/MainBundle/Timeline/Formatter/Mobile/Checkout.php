<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Item\Checkout as CheckoutItem;
use Symfony\Contracts\Translation\TranslatorInterface;

class Checkout implements ItemFormatterInterface
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
     * @var Checkin
     */
    private $checkin;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper,
        Checkin $checkin
    ) {
        $this->translator = $translator;
        $this->blockHelper = $blockHelper;
        $this->checkin = $checkin;
    }

    /**
     * @param CheckoutItem $item
     * @return Formatted\SegmentItem
     */
    public function format($item, Timeline\QueryOptions $queryOptions)
    {
        /** @var Timeline\Item\Checkin $checkin */
        $checkin = $item->getConnection();
        $formatted = $this->checkin->format($checkin, $queryOptions);
        $formatOptions = $queryOptions->getFormatOptions();
        $this->blockHelper->formatCommonSegmentProperties($checkin, $formatted, $formatOptions);
        $this->blockHelper->formatCommonProperties($item, $formatted);
        /** @var Entity\Reservation $source */
        $source = $item->getSource();
        $changes = $item->getChanges();

        if ($formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2)) {
            $formatted->startDate =
            $formatted->endDate = $this->blockHelper->createLocalizedDate(
                DateTimeExtended::create($item->getStartDate(), $item->getTimezoneAbbr()),
                $changes ?
                    (($oldDate = Utils::getChangedDateTime($changes, PropertiesList::CHECK_OUT_DATE)) ? DateTimeExtended::create($oldDate, $item->getTimezoneAbbr()) : null) :
                    null,
                'full'
            );
        } else {
            $formatted->startDate = new Formatted\Components\Date(
                $item->getStartDate(),
                $changes ?
                    Utils::getChangedDateTime($changes, PropertiesList::CHECK_IN_DATE) :
                    null
            );
        }

        if (!$source instanceof Entity\Reservation) {
            return $formatted;
        }

        $formatted->listView = new Formatted\Components\ListView\SimpleView(
            $this->translator->trans('check-out-from', Utils::transParams(['%hotel%' => '']), 'trips'),
            Utils::getReservationName($source)
        );

        $formatted->changed =
            $item->isChanged()
            && $changes
            && array_intersect(
                $changes->getChangedProperties(),
                ['CheckOutDate']
            );

        return $formatted;
    }
}
