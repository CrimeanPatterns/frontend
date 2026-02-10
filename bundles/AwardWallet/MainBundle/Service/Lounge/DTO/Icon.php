<?php

namespace AwardWallet\MainBundle\Service\Lounge\DTO;

class Icon
{
    public string $path;

    public ?int $width;

    public ?int $height;

    public function __construct(string $path, ?int $width = null, ?int $height = null)
    {
        $this->path = $path;
        $this->width = $width;
        $this->height = $height;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }
}
