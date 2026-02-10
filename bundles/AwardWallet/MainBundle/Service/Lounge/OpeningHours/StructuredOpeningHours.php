<?php

namespace AwardWallet\MainBundle\Service\Lounge\OpeningHours;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\StringHandler;
use JMS\Serializer\Annotation as Serializer;

/**
 * @NoDI()
 */
class StructuredOpeningHours extends AbstractOpeningHours
{
    /**
     * @Serializer\Type("string")
     */
    private string $tz;

    /**
     * @Serializer\Type("array")
     */
    private array $data;

    public function __construct(string $tz, array $data)
    {
        if (StringHandler::isEmpty($tz)) {
            throw new \InvalidArgumentException('Timezone cannot be empty');
        }

        if (\count($data) === 0) {
            throw new \InvalidArgumentException('Opening hours data cannot be empty');
        }

        $this->tz = $tz;
        $this->data = $data;
    }

    public function build(): Builder
    {
        return new Builder($this->data, $this->tz);
    }

    public function getTz(): string
    {
        return $this->tz;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'structured',
            'tz' => $this->tz,
            'data' => $this->data,
        ];
    }

    public function isEquals(AbstractOpeningHours $openingHours): bool
    {
        if (!$openingHours instanceof StructuredOpeningHours) {
            return false;
        }

        return $this->tz === $openingHours->tz && $this->data === $openingHours->data;
    }
}
