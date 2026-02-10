<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\Fixture;

use AwardWallet\MainBundle\Globals\StringUtils;

class AirHelpData
{
    private const DATE_FORMAT = 'd/m/y H:i';

    public string $partner_travel_id;
    public string $airline_iata_code;
    public string $flight_number;
    public string $flight_date;
    public string $flight_start;
    public string $flight_end;
    public string $segment_start;
    public string $segment_end;
    public ?\DateTime $flight_scheduled_departure;
    public ?\DateTime $flight_actual_departure;
    public ?\DateTime $flight_scheduled_arrival;
    public ?\DateTime $flight_actual_arrival;
    public string $flight_status;
    public string $booking_reference;
    public string $number_passengers;
    public string $ec261_compensation_gross;
    public string $ec261_compensation_currency;
    public string $quote_compensation_net;
    public string $quote_compensation_currency;
    public string $url;
    public string $email;
    public string $first_name;
    public string $last_name;
    public string $campaign;
    public string $locale;
    public string $delay_mins;
    public string $delay_info;
    public string $departure_city;
    public string $arrival_city;
    public string $departure_date;
    public string $departure_time;
    public string $arrival_date;
    public string $arrival_time;
    public string $airline_name;
    public string $flight_name;
    public string $ahcid;
    public string $uuid;
    public string $mail_to;
    public string $salutation;
    public string $unsubscription_url;
    public string $segment_departure_city;
    public string $segment_arrival_city;
    public string $localized_departure_city;
    public string $localized_arrival_city;
    public string $localized_segment_departure_city;
    public string $localized_segment_arrival_city;
    public string $segment_airport_start;
    public string $segment_airport_end;
    public string $flight_airport_start;
    public string $flight_airport_end;
    public string $ec261_compensation_currency_symbol;
    public string $quote_compensation_currency_symbol;
    public string $segment_departure_date;

    public function __construct(
        string $partner_travel_id,
        string $airline_iata_code,
        string $flight_number,
        string $flight_date,
        string $flight_start,
        string $flight_end,
        string $segment_start,
        string $segment_end,
        string $flight_scheduled_departure,
        string $flight_actual_departure,
        string $flight_scheduled_arrival,
        string $flight_actual_arrival,
        string $flight_status,
        string $booking_reference,
        string $number_passengers,
        string $ec261_compensation_gross,
        string $ec261_compensation_currency,
        string $quote_compensation_net,
        string $quote_compensation_currency,
        string $url,
        string $email,
        string $first_name,
        string $last_name,
        string $campaign,
        string $locale,
        string $delay_mins,
        string $delay_info,
        string $departure_city,
        string $arrival_city,
        string $departure_date,
        string $departure_time,
        string $arrival_date,
        string $arrival_time,
        string $airline_name,
        string $flight_name,
        string $ahcid,
        string $uuid,
        string $mail_to,
        string $salutation,
        string $unsubscription_url,
        string $segment_departure_city,
        string $segment_arrival_city,
        string $localized_departure_city,
        string $localized_arrival_city,
        string $localized_segment_departure_city,
        string $localized_segment_arrival_city,
        string $segment_airport_start,
        string $segment_airport_end,
        string $flight_airport_start,
        string $flight_airport_end,
        string $ec261_compensation_currency_symbol,
        string $quote_compensation_currency_symbol,
        string $segment_departure_date
    ) {
        $this->partner_travel_id = $partner_travel_id;
        $this->airline_iata_code = $airline_iata_code;
        $this->flight_number = $flight_number;
        $this->flight_date = $flight_date;
        $this->flight_start = $flight_start;
        $this->flight_end = $flight_end;
        $this->segment_start = $segment_start;
        $this->segment_end = $segment_end;
        $this->flight_scheduled_departure = self::convertToDate($flight_scheduled_departure);
        $this->flight_actual_departure = self::convertToDate($flight_actual_departure);
        $this->flight_scheduled_arrival = self::convertToDate($flight_scheduled_arrival);
        $this->flight_actual_arrival = self::convertToDate($flight_actual_arrival);
        $this->flight_status = $flight_status;
        $this->booking_reference = $booking_reference;
        $this->number_passengers = $number_passengers;
        $this->ec261_compensation_gross = $ec261_compensation_gross;
        $this->ec261_compensation_currency = $ec261_compensation_currency;
        $this->quote_compensation_net = $quote_compensation_net;
        $this->quote_compensation_currency = $quote_compensation_currency;
        $this->url = $url;
        $this->email = $email;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->campaign = $campaign;
        $this->locale = $locale;
        $this->delay_mins = $delay_mins;
        $this->delay_info = $delay_info;
        $this->departure_city = $departure_city;
        $this->arrival_city = $arrival_city;
        $this->departure_date = $departure_date;
        $this->departure_time = $departure_time;
        $this->arrival_date = $arrival_date;
        $this->arrival_time = $arrival_time;
        $this->airline_name = $airline_name;
        $this->flight_name = $flight_name;
        $this->ahcid = $ahcid;
        $this->uuid = $uuid;
        $this->mail_to = $mail_to;
        $this->salutation = $salutation;
        $this->unsubscription_url = $unsubscription_url;
        $this->segment_departure_city = $segment_departure_city;
        $this->segment_arrival_city = $segment_arrival_city;
        $this->localized_departure_city = $localized_departure_city;
        $this->localized_arrival_city = $localized_arrival_city;
        $this->localized_segment_departure_city = $localized_segment_departure_city;
        $this->localized_segment_arrival_city = $localized_segment_arrival_city;
        $this->segment_airport_start = $segment_airport_start;
        $this->segment_airport_end = $segment_airport_end;
        $this->flight_airport_start = $flight_airport_start;
        $this->flight_airport_end = $flight_airport_end;
        $this->ec261_compensation_currency_symbol = $ec261_compensation_currency_symbol;
        $this->quote_compensation_currency_symbol = $quote_compensation_currency_symbol;
        $this->segment_departure_date = $segment_departure_date;
    }

    protected static function convertToDate(string $value): ?\DateTime
    {
        if (StringUtils::isNotEmpty($value)) {
            return \DateTime::createFromFormat(self::DATE_FORMAT, $value);
        }

        return null;
    }
}
