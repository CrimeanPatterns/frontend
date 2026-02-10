<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class CardView extends AbstractView
{
    public string $id;

    public string $label;

    public string $icon;

    public bool $selected;

    public ?bool $autoSelected;

    public function __construct(string $id, string $label, string $icon, bool $selected, ?bool $autoSelected)
    {
        $this->id = $id;
        $this->label = $label;
        $this->icon = $icon;
        $this->selected = $selected;
        $this->autoSelected = $autoSelected;
    }
}
