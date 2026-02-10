<?php

namespace AwardWallet\MainBundle\Service\Lounge\OpeningHours;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\StringHandler;
use JMS\Serializer\Annotation as Serializer;

/**
 * @NoDI()
 */
class RawOpeningHours extends AbstractOpeningHours
{
    /**
     * @Serializer\Type("string")
     */
    private string $raw;

    public function __construct(string $raw)
    {
        if (StringHandler::isEmpty($raw)) {
            throw new \InvalidArgumentException('Raw opening hours cannot be empty');
        }

        $this->raw = $raw;
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'raw',
            'raw' => $this->raw,
        ];
    }

    public function isEquals(AbstractOpeningHours $openingHours): bool
    {
        return $openingHours instanceof RawOpeningHours && $this->raw === $openingHours->getRaw();
    }
}
