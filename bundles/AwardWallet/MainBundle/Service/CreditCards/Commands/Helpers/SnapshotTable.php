<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands\Helpers;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class SnapshotTable
{
    private const TABLE_SUFFIX_DATE_FORMAT = 'YmdHis';
    private string $name;
    private \DateTimeImmutable $maxDate;
    private ?\DateTimeImmutable $minDate = null;
    private ?int $days;

    public function __construct(string $name, \DateTimeImmutable $maxDate, ?int $days = null)
    {
        $this->name = $name;
        $this->maxDate = $maxDate;
        $this->days = $days;

        if ($days) {
            $this->minDate = $maxDate->modify("-{$days} day");
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMaxDate(): \DateTimeImmutable
    {
        return $this->maxDate;
    }

    public function getMinDate(): ?\DateTimeImmutable
    {
        return $this->minDate;
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function getSuffix(): string
    {
        return self::makeSuffix($this->maxDate, $this->days);
    }

    public static function makeSuffix(\DateTimeInterface $date, ?int $days = null): string
    {
        return $date->format(self::TABLE_SUFFIX_DATE_FORMAT) . ($days ? "_{$days}d" : '');
    }
}
