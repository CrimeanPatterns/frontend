<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\Fixture;

class AirHelpView
{
    /**
     * @var string|null
     */
    public $locale;
    /**
     * @var string|null
     */
    public $recordLocator;
    /**
     * @var string|null
     */
    public $url;
    /**
     * @var string|null
     */
    public $flight_status_upper;
    /**
     * @var string|null
     */
    public $delay_info;
    /**
     * @var string|null
     */
    public $departure_city;
    /**
     * @var string|null
     */
    public $localized_departure_city;
    /**
     * @var string|null
     */
    public $flight_start;
    /**
     * @var string|null
     */
    public $arrival_city;
    /**
     * @var string|null
     */
    public $localized_arrival_city;
    /**
     * @var string|null
     */
    public $flight_end;
    /**
     * @var string|null
     */
    public $ec261_compensation_localized;
    /**
     * @var string|null
     */
    public $flight_scheduled_departure_localized;
    /**
     * @var string|null
     */
    public $flight_scheduled_arrival_localized;
    /**
     * @var string|null
     */
    public $flight_scheduled_departure_top_localized;
    /**
     * @var string|null
     */
    public $flight_status_lower;
    /**
     * @var string|null
     */
    public $flight_status_text_block;
    /**
     * @var \DateTime
     */
    public $flight_scheduled_departure;
    /**
     * @var string|null
     */
    public $status_original;

    public function __construct(
        ?string $locale,
        ?string $recordLocator,
        ?string $url,
        ?string $status_original,
        ?string $flight_status_upper,
        ?string $flight_status_lower,
        ?string $flight_status_text_block,
        ?string $delay_info,
        ?string $ec261_compensation_localized,
        ?string $departure_city,
        ?string $localized_departure_city,
        ?string $flight_start,
        ?string $flight_scheduled_departure_localized,
        \DateTime $flight_scheduled_departure,
        ?string $flight_scheduled_departure_top_localized,
        ?string $arrival_city,
        ?string $localized_arrival_city,
        ?string $flight_end,
        ?string $flight_scheduled_arrival_localized
    ) {
        $this->locale = $locale;
        $this->recordLocator = $recordLocator;
        $this->url = $url;
        $this->flight_status_upper = $flight_status_upper;
        $this->delay_info = $delay_info;
        $this->departure_city = $departure_city;
        $this->localized_departure_city = $localized_departure_city;
        $this->flight_start = $flight_start;
        $this->arrival_city = $arrival_city;
        $this->localized_arrival_city = $localized_arrival_city;
        $this->flight_end = $flight_end;
        $this->ec261_compensation_localized = $ec261_compensation_localized;
        $this->flight_scheduled_departure_localized = $flight_scheduled_departure_localized;
        $this->flight_scheduled_arrival_localized = $flight_scheduled_arrival_localized;
        $this->flight_scheduled_departure_top_localized = $flight_scheduled_departure_top_localized;
        $this->flight_status_lower = $flight_status_lower;
        $this->flight_status_text_block = $flight_status_text_block;
        $this->flight_scheduled_departure = $flight_scheduled_departure;
        $this->status_original = $status_original;
    }
}
