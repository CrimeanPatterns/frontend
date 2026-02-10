<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class Separator extends BaseBlock
{
    public const STYLE_BOLD = 'bold';
    /**
     * @var string
     */
    public $style;

    /**
     * Separator constructor.
     *
     * @param string $style
     */
    public function __construct($style)
    {
        $this->setType('viewSeparator');

        $this->style = $style;
    }
}
