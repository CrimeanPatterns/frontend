<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted;

use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Block;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\ConfirmationSummary;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Date;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\BaseMenu;

class SegmentItem extends AbstractItem
{
    /**
     * @var Date
     */
    public $endDate;

    /**
     * @var Date
     */
    public $duration;

    /**
     * @var Components\Block[]
     */
    public $blocks;

    /**
     * @var string
     */
    public $icon;

    public $listView;

    /**
     * @var BaseMenu
     */
    public $menu;

    /**
     * @var bool
     */
    public $deleted = false;

    /**
     * @var bool
     */
    public $createPlan = false;
    public bool $aiWarning = false;

    public ?ConfirmationSummary $confirmationSummary = null;

    /**
     * @param Block[] $blocks
     * @param int $limit
     */
    public function addBlocksOrFold(array $blocks, $limit)
    {
        if (!$blocks) {
            return;
        }

        $blocksCount = count($blocks);

        if (
            ($blocksCount > 2)
            && (count($this->blocks) + $blocksCount > $limit)
        ) {
            $this->blocks[] = Block::fromKindValue(Block::KIND_SHOWMORE, $blocks);
        } else {
            $this->blocks = array_merge($this->blocks, $blocks);
        }
    }
}
