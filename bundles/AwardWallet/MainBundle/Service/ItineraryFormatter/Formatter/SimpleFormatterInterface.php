<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter;

interface SimpleFormatterInterface
{
    public function getValue(string $code);

    /**
     * @param string[] $codes
     */
    public function getValues(array $codes): array;

    /**
     * @param string[] $codes
     */
    public function getExistingValues(array $codes): array;

    public function getPreviousValue(string $code);

    /**
     * @param string[] $codes
     */
    public function getPreviousValues(array $codes): array;

    /**
     * @param string[] $codes
     */
    public function getExistingPreviousValues(array $codes): array;

    public function translatePropertyName(string $code): string;
}
