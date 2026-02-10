<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\Date as DateItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;

class Date extends AbstractItem
{
    private LocalizeService $localizeService;

    public function __construct(LocalizeService $localizeService)
    {
        $this->localizeService = $localizeService;
    }

    /**
     * @param DateItem $item
     */
    public function format(ItemInterface $item, Timeline\QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        $result['localDate'] = $this->localizeService->formatDateTime($item->getLocalDate(), 'full', null);
        $result['localDateISO'] = sprintf('%sT00:00', $item->getStartDate()->format('Y-m-d'));
        $result['localDateTimeISO'] = $item->getStartDate()->format('c');

        return $result;
    }
}
