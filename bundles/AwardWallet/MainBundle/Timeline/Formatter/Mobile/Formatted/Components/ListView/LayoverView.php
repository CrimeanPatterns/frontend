<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\ListView;

class LayoverView extends SimpleView
{
    /**
     * @var int[]
     */
    public $duration;

    public ?int $lounges;

    public ?string $airportCode;

    public ?string $arrTerminal;

    public ?string $depTerminal;

    public ?\JsonSerializable $listOfLounges;

    public $kind = 'layover';

    public function __construct($title, $val)
    {
        parent::__construct($title, $val);
    }

    public function setDuration($duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function setLounges(?int $lounges): self
    {
        $this->lounges = $lounges;

        return $this;
    }

    public function setAirportCode(?string $airportCode): self
    {
        $this->airportCode = $airportCode;

        return $this;
    }

    public function setArrTerminal(?string $arrTerminal): self
    {
        $this->arrTerminal = $arrTerminal;

        return $this;
    }

    public function setDepTerminal(?string $depTerminal): self
    {
        $this->depTerminal = $depTerminal;

        return $this;
    }

    public function setListOfLounges(?\JsonSerializable $listOfLounges): self
    {
        $this->listOfLounges = $listOfLounges;

        return $this;
    }
}
