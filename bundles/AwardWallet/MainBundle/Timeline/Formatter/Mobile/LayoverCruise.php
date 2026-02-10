<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\ListView\LayoverView;
use AwardWallet\MainBundle\Timeline\Item\CruiseLayover as CruiseLayoverItem;
use Symfony\Contracts\Translation\TranslatorInterface;

class LayoverCruise implements ItemFormatterInterface
{
    private BlockHelper $blockHelper;
    private TranslatorInterface $translator;
    private DateTimeIntervalFormatter $dateTimeIntervalFormatter;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper,
        DateTimeIntervalFormatter $dateTimeIntervalFormatter
    ) {
        $this->blockHelper = $blockHelper;
        $this->translator = $translator;
        $this->dateTimeIntervalFormatter = $dateTimeIntervalFormatter;
    }

    /**
     * @param CruiseLayoverItem $item
     * @return Formatted\SegmentItem
     */
    public function format($item, Timeline\QueryOptions $queryOptions)
    {
        $formatted = new Timeline\Formatter\Mobile\Formatted\SegmentItem();
        $formatOptions = $queryOptions->getFormatOptions();
        $this->blockHelper->formatCommonSegmentProperties($item, $formatted, $formatOptions);
        $formatted->type = Timeline\Item\Type::LAYOVER;
        $formatted->startDate = $this->blockHelper->createLocalizedDate(DateTimeExtended::create($item->getStartDate(), $item->getTimezoneAbbr()));

        $formatted->listView = new LayoverView(
            $item->getLocation(),
            $this->translator->trans(
                /** @Desc("%duration% on land") */
                'duration-layover-on-land',
                [
                    '%duration%' => $this->dateTimeIntervalFormatter->formatDurationViaInterval($item->getDuration(), false, true),
                ],
                'trips'
            )
        );

        return $formatted;
    }
}
