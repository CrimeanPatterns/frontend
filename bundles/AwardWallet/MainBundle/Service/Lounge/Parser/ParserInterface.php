<?php

namespace AwardWallet\MainBundle\Service\Lounge\Parser;

use AwardWallet\MainBundle\Entity\LoungeSource;

interface ParserInterface
{
    public function __toString();

    /**
     * @return string unique code of parser
     */
    public function getCode(): string;

    public function isEnabled(): bool;

    public function isParsingFrozen(): bool;

    /**
     * @return iterable<string, mixed[]>
     */
    public function requestAirports(callable $airportFilter): iterable;

    public function getLounge(string $airportCode, $loungeData): ?LoungeSource;
}
