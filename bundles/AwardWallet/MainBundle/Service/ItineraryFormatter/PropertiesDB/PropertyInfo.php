<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class PropertyInfo
{
    protected string $code;

    protected array $tagsMap = [];

    protected bool $isPrivate = false;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function addTags(array $tags): self
    {
        foreach ($tags as $tag) {
            $this->tagsMap[$tag] = true;
        }

        return $this;
    }

    public function hasTag(string $tag): bool
    {
        return isset($this->tagsMap[$tag]);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setPrivate(bool $isPrivate = true): self
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }
}
