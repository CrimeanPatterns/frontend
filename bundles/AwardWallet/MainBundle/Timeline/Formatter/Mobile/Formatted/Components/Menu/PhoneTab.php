<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu;

class PhoneTab
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var Phones[]
     */
    public $phonesLists = [];

    public function __construct(?string $title, ?string $icon)
    {
        $this->title = $title;
        $this->icon = $icon;
    }
}
