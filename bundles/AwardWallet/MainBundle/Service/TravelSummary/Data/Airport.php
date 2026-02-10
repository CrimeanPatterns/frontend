<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Airport implements \JsonSerializable
{
    /**
     * @var Segment[]
     */
    protected $segments = [];
    /**
     * @var string
     */
    private $code;
    /**
     * @var Point
     */
    private $point;
    /**
     * @var string
     */
    private $title;

    public function __construct(
        string $code,
        Point $point
    ) {
        $this->code = $code;
        $this->point = $point;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getPoint(): Point
    {
        return $this->point;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function setSegments(array $segments): self
    {
        $this->segments = $segments;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'code' => $this->code,
            'point' => $this->point,
            'title' => $this->title,
            'segments' => $this->title,
        ];
    }
}
