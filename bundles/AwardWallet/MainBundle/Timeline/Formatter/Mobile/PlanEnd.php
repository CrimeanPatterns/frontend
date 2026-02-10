<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\PlanItem;
use AwardWallet\MainBundle\Timeline\Item\PlanEnd as PlanEndItem;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlanEnd implements ItemFormatterInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var BlockHelper
     */
    private $blockHelper;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper
    ) {
        $this->translator = $translator;
        $this->blockHelper = $blockHelper;
    }

    /**
     * @param PlanEndItem $item
     * @return PlanItem
     */
    public function format($item, QueryOptions $queryOptions)
    {
        $formattedItem = $this->blockHelper->createFromPlan($item, $queryOptions->getFormatOptions());
        $formattedItem->name = $this->translator->trans(/** @Desc("%plan-name% ends") */ 'trips.plan.ends', ['%plan-name%' => $item->getPlan()->getName()], 'mobile');

        return $formattedItem;
    }
}
