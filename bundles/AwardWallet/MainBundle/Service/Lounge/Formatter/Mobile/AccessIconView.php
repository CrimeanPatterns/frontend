<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

use AwardWallet\MainBundle\Service\Lounge\DTO\Icon;

class AccessIconView extends AbstractView
{
    /**
     * @var Icon|string|null
     */
    public $icon;

    public ?bool $isGranted;

    public ?string $description;

    /**
     * @param Icon|string|null $icon
     */
    public function __construct($icon, bool $isGranted, ?string $description = null)
    {
        $this->icon = $icon;
        $this->isGranted = $isGranted ? true : null;
        $this->description = $description;
    }
}
