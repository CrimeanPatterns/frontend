<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Item implements \JsonSerializable
{
    /**
     * @var string
     */
    private $key;
    /**
     * @var string
     */
    private $value;
    /**
     * @var string
     */
    private $title;

    private $payload;

    public function __construct(
        string $key,
        string $value,
        string $title,
        $payload
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->title = $title;
        $this->payload = $payload;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function jsonSerialize()
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'title' => $this->title,
            'payload' => $this->payload,
        ];
    }
}
