<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Address as SchemaAddress;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\ParsedNumber as SchemaParsedNumber;
use AwardWallet\Schema\Itineraries\Person as SchemaPerson;
use AwardWallet\Schema\Itineraries\PricingInfo as SchemaPricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo as SchemaProviderInfo;
use AwardWallet\Schema\Itineraries\TravelAgency as SchemaTravelAgency;

abstract class AbstractFakeBuilder
{
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaItinerary $schemaItinerary)
    {
        $this->schemaItinerary = $schemaItinerary;
    }

    public function map(callable $callback): self
    {
        $this->schemaItinerary = $callback($this->schemaItinerary);

        return $this;
    }

    public function withTravelAgency(?SchemaTravelAgency $travelAgency): self
    {
        $this->schemaItinerary->travelAgency = $travelAgency;

        return $this;
    }

    public function withPricingInfo(?SchemaPricingInfo $pricingInfo): self
    {
        $this->schemaItinerary->pricingInfo = $pricingInfo;

        return $this;
    }

    public function withProviderInfo(?SchemaProviderInfo $providerInfo): self
    {
        $this->schemaItinerary->providerInfo = $providerInfo;

        return $this;
    }

    public function cancelled(bool $cancelled = true): self
    {
        $this->schemaItinerary->cancelled = $cancelled;

        return $this;
    }

    public function get(): SchemaItinerary
    {
        return $this->schemaItinerary;
    }

    /**
     * @param string[]|array[] $confirmationNumbers
     */
    public static function travelAgency(
        ?string $name = 'Expedia.com',
        ?string $code = 'expedia',
        ?array $confirmationNumbers = ['J3HND-8776'],
        ?array $phoneNumbers = ['+1-44-EXPEDIA']
    ): SchemaTravelAgency {
        if (is_array($confirmationNumbers)) {
            $confirmationNumbers = array_map(
                fn ($confNo) => is_array($confNo)
                    ? SchemaBuilder::makeSchemaConfNo(...$confNo)
                    : SchemaBuilder::makeSchemaConfNo($confNo, null, 'Confirmation #'),
                $confirmationNumbers
            );
        }

        if (is_array($phoneNumbers)) {
            $phoneNumbers = array_map(
                fn ($phoneNumber) => is_array($phoneNumber)
                    ? SchemaBuilder::makeSchemaPhoneNumber(...$phoneNumber)
                    : SchemaBuilder::makeSchemaPhoneNumber($phoneNumber, 'Help Desk'),
                $phoneNumbers
            );
        }

        return SchemaBuilder::makeSchemaTravelAgency(
            SchemaBuilder::makeSchemaProviderInfo(
                $code,
                $name,
                [
                    SchemaBuilder::makeSchemaParsedNumber('EXP-11298'),
                ],
                '1 booking'
            ),
            $confirmationNumbers,
            $phoneNumbers
        );
    }

    public static function pricingInfo(
        ?float $total = 250.50,
        ?float $cost = 200.50,
        ?string $currency = 'USD',
        ?float $discount = 40,
        ?string $spentAwards = '10000 points',
        ?array $fees = [
            ['Tax', 30],
            ['Insurance', 20],
        ]
    ): SchemaPricingInfo {
        if (is_array($fees)) {
            $fees = array_map(
                fn ($fee) => is_array($fee)
                    ? SchemaBuilder::makeSchemaFee(...$fee)
                    : SchemaBuilder::makeSchemaFee('Tax', $fee),
                $fees
            );
        }

        return SchemaBuilder::makeSchemaPricingInfo(
            $total,
            $cost,
            $discount,
            $spentAwards,
            $currency,
            $fees
        );
    }

    public static function providerInfo(
        ?string $name = 'Test Provider',
        ?string $code = 'testprovider',
        ?array $accountNumbers = ['12345'],
        ?string $earnedRewards = '100 points'
    ): SchemaProviderInfo {
        if (is_array($accountNumbers)) {
            $accountNumbers = array_map(
                fn ($accountNumber) => is_array($accountNumber)
                    ? SchemaBuilder::makeSchemaParsedNumber(...$accountNumber)
                    : SchemaBuilder::makeSchemaParsedNumber($accountNumber),
                $accountNumbers
            );
        }

        return SchemaBuilder::makeSchemaProviderInfo($code, $name, $accountNumbers, $earnedRewards);
    }

    public static function address(
        string $text = '20 Cooper Square, New York, NY 10003, USA',
        ?string $addressLine = null,
        ?string $city = null,
        ?string $state = null,
        ?string $countryName = null,
        ?string $countryCode = null,
        ?string $postalCode = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $timezoneOffset = null,
        ?string $timezoneId = null
    ): SchemaAddress {
        return SchemaBuilder::makeSchemaAddress(
            $text,
            $addressLine,
            $city,
            $state,
            $countryName,
            $countryCode,
            $postalCode,
            $latitude,
            $longitude,
            $timezoneOffset,
            $timezoneId
        );
    }

    public static function person(
        string $name = 'John Doe',
        bool $full = true,
        ?string $type = null
    ): SchemaPerson {
        return SchemaBuilder::makeSchemaPerson($name, $full, $type);
    }

    /**
     * @return SchemaPerson[]
     */
    public static function persons(array $names): array
    {
        return array_map(
            fn ($name) => is_array($name)
                ? SchemaBuilder::makeSchemaPerson(...$name)
                : SchemaBuilder::makeSchemaPerson($name),
            $names
        );
    }

    public static function confNumber(
        string $number = '12345',
        ?bool $isPrimary = false,
        ?string $description = null
    ): SchemaConfNo {
        return SchemaBuilder::makeSchemaConfNo($number, $isPrimary, $description);
    }

    /**
     * @return SchemaConfNo[]
     */
    public static function confNumbers(array $confNumbers): array
    {
        return array_map(
            fn ($confNo) => is_array($confNo)
                ? SchemaBuilder::makeSchemaConfNo(...$confNo)
                : SchemaBuilder::makeSchemaConfNo($confNo),
            $confNumbers
        );
    }

    public static function parsedNumber(string $number = '12345', ?bool $masked = false): SchemaParsedNumber
    {
        return SchemaBuilder::makeSchemaParsedNumber($number, $masked);
    }

    /**
     * @return SchemaParsedNumber[]
     */
    public static function parsedNumbers(array $parsedNumbers): array
    {
        return array_map(
            fn ($parsedNumber) => is_array($parsedNumber)
                ? SchemaBuilder::makeSchemaParsedNumber(...$parsedNumber)
                : SchemaBuilder::makeSchemaParsedNumber($parsedNumber),
            $parsedNumbers
        );
    }

    public static function create(SchemaItinerary $schemaItinerary): self
    {
        return new static($schemaItinerary);
    }
}
