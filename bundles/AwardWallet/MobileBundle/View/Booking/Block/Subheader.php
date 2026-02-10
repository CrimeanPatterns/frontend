<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MobileBundle\View\AbstractBlock;

class Subheader extends AbstractBlock
{
    /** @var string */
    public $name;

    /** @var string */
    public $icon;

    public function __construct($name, $icon = null)
    {
        parent::__construct();
        $this->setName($name);

        if (isset($icon)) {
            $this->setIcon($icon);
        }
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }
}
