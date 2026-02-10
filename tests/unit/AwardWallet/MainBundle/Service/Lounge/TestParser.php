<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Service\Lounge\Parser\ParserInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TestParser implements ParserInterface
{
    private string $code;

    private array $lounges;

    private bool $throwException;

    private bool $enabled;

    private bool $isParsingFrozen;

    public function __construct(string $code, array $lounges, bool $throwException = false, bool $enabled = true, bool $isParsingFrozen = false)
    {
        $this->code = $code;
        $this->setLounges($lounges);
        $this->throwException = $throwException;
        $this->enabled = $enabled;
        $this->isParsingFrozen = $isParsingFrozen;
    }

    public function __toString()
    {
        return $this->code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isParsingFrozen(): bool
    {
        return $this->isParsingFrozen;
    }

    public function requestAirports(callable $airportFilter): iterable
    {
        if ($this->throwException) {
            throw new \Exception('Test exception');
        }

        return $this->lounges;
    }

    public function getLounge(string $airportCode, $loungeData): ?LoungeSource
    {
        return $loungeData;
    }

    public function setLounges(array $lounges): self
    {
        foreach ($lounges as $lounge) {
            $lounge->setSourceCode($this->code);
        }

        $this->lounges = it($lounges)
            ->usort(fn (LoungeSource $a, LoungeSource $b) => $a->getAirportCode() <=> $b->getAirportCode())
            ->groupAdjacentBy(fn (LoungeSource $a, LoungeSource $b) => $a->getAirportCode() <=> $b->getAirportCode())
            ->reindex(function (array $lounges) {
                /** @var LoungeSource[] $lounges */
                return $lounges[0]->getAirportCode();
            })
            ->toArrayWithKeys();

        return $this;
    }

    public function addLounges(array $lounges): self
    {
        foreach ($lounges as $lounge) {
            $lounge->setSourceCode($this->code);
        }

        foreach ($lounges as $lounge) {
            $this->lounges[$lounge->getAirportCode()][] = $lounge;
        }

        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function setIsParsingFrozen(bool $isParsingFrozen): self
    {
        $this->isParsingFrozen = $isParsingFrozen;

        return $this;
    }
}
