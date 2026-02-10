<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Service\Lounge\Finder;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\ViewInflater;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\ListView\LayoverView;
use AwardWallet\MainBundle\Timeline\Item\Layover as LayoverItem;
use Clock\ClockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Layover implements ItemFormatterInterface
{
    private TranslatorInterface $translator;
    private BlockHelper $blockHelper;
    private Finder $loungeFinder;
    private ViewInflater $loungeViewInflater;
    private ClockInterface $clock;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper,
        Finder $loungeFinder,
        ViewInflater $loungeViewInflater,
        ClockInterface $clock
    ) {
        $this->translator = $translator;
        $this->blockHelper = $blockHelper;
        $this->loungeFinder = $loungeFinder;
        $this->loungeViewInflater = $loungeViewInflater;
        $this->clock = $clock;
    }

    /**
     * @param LayoverItem $item
     * @return Formatted\SegmentItem
     */
    public function format($item, Timeline\QueryOptions $queryOptions)
    {
        $formatted = new Timeline\Formatter\Mobile\Formatted\SegmentItem();
        $formatOptions = $queryOptions->getFormatOptions();
        $this->blockHelper->formatCommonSegmentProperties($item, $formatted, $formatOptions);

        if ($queryOptions->getFormatOptions()->supports(FormatHandler::DETAILS_BLOCKS_V2)) {
            $formatted->startDate = $this->blockHelper->createLocalizedDate(DateTimeExtended::create($item->getStartDate(), $item->getTimezoneAbbr()));
        } else {
            $formatted->startDate = new Formatted\Components\Date($item->getStartDate());
        }

        $formatted->listView = new LayoverView(
            $this->translator->trans(/** @Desc("Layover") */ 'layover', [], 'trips'),
            $this->translator->trans(
                /** @Desc("<gray>@</gray> %location%") */
                'layover-at',
                Utils::transParams([
                    '%location%' => $item->getLocation(),
                    '<gray>' => '',
                ]),
                'trips',
            )
        );
        $formatted->listView->setDuration($formatted->duration);

        if (
            $queryOptions->getFormatOptions()->supports(FormatHandler::LOUNGES)
            && $item instanceof Timeline\Item\AirLayover
            && !empty($airportCode = $item->getAirportCode())
        ) {
            $startDate = $this->clock->current()->getAsDateTime();
            $endDate = clone $startDate;
            $endDate->modify('+10 days');

            $formatted->listView
                ->setAirportCode($airportCode)
                ->setArrTerminal($item->getArrivalTerminal())
                ->setDepTerminal($item->getDepartureTerminal())
                ->setLounges($this->loungeFinder->getNumberAirportLounges($airportCode))
                ->setListOfLounges(
                    $queryOptions->getFormatOptions()->supports(FormatHandler::LOUNGES_OFFLINE)
                    && (
                        ($item->getStartDate() >= $startDate && $item->getStartDate() <= $endDate)
                        || ($item->getEndDate() >= $startDate && $item->getEndDate() <= $endDate)
                    )
                    ? $this->loungeViewInflater->listLounges(
                        $queryOptions->getUser(),
                        $item->getLeftSource(),
                        null,
                        $item->getArrivalTerminal(),
                        $item->getDepartureTerminal(),
                        null,
                        true
                    ) : null
                );
        }

        return $formatted;
    }
}
