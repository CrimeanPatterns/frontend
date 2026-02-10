<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class OpeningHoursDetailsView extends AbstractLoungeDetailsView
{
    public string $icon;

    public string $header;

    public ?string $description;

    /** @var OpeningHoursItemView[]|string|null */
    public $openingHours;

    /**
     * @param OpeningHoursItemView[]|string|null $openingHours
     */
    public function __construct(string $header, ?string $description, $openingHours = null)
    {
        parent::__construct();
        $this->icon = 'hours';
        $this->header = $header;
        $this->description = $description;
        $this->openingHours = $openingHours;
    }
}
