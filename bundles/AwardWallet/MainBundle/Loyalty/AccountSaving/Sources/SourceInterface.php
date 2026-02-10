<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

interface SourceInterface
{
    public function getId(): string;

    public function stillExists(): void;

    public function getDate(): ?\DateTimeInterface;

    /**
     * used in setSourceId, deprecated
     * should be deleted after migrations and release.
     *
     * @TODO: delete after release
     */
    public function getOldId(): ?string;
}
