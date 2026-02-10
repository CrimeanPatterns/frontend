<?php

namespace AwardWallet\MainBundle\Service\Lounge\OpeningHours;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Spatie\OpeningHours\OpeningHours;

/**
 * @NoDI()
 */
class Builder
{
    public const CODE_RANGE_UNKNOWN_START = 'CODE_RANGE_UNKNOWN_START';
    public const CODE_RANGE_UNKNOWN_END = 'CODE_RANGE_UNKNOWN_END';
    public const CODE_RANGE_UNKNOWN_BOTH = 'CODE_RANGE_UNKNOWN_BOTH';
    public const CODE_OPEN24 = 'CODE_OPEN24';
    public const CODE_CLOSED = 'CODE_CLOSED';
    public const CODE_HOURS_VARY = 'CODE_HOURS_VARY';
    public const CODE_UNKNOWN = 'CODE_UNKNOWN';
    public const CODE_MERGED = 'CODE_MERGED';

    public const DAY_CODES = [
        self::CODE_OPEN24,
        self::CODE_CLOSED,
        self::CODE_HOURS_VARY,
    ];

    private OpeningHours $openingHours;

    private \DateTimeZone $timeZone;

    public function __construct(array $data, ?string $timeZone = 'UTC')
    {
        try {
            $this->timeZone = new \DateTimeZone($timeZone);
        } catch (\Exception $e) {
            $this->timeZone = new \DateTimeZone('UTC');
        }

        $data['overflow'] = true;
        $this->openingHours = OpeningHours::create($data, $this->timeZone);
    }

    public function getOpeningHours(): OpeningHours
    {
        return $this->openingHours;
    }

    public function getTimeZone(): \DateTimeZone
    {
        return $this->timeZone;
    }

    public function opened(?\DateTimeInterface $dateTime = null): bool
    {
        if (!$dateTime) {
            $dateTime = new \DateTime('now', $this->timeZone);
        }

        $range = $this->openingHours->currentOpenRange($dateTime);

        if (!$range) {
            return false;
        }

        if (
            (
                is_array($data = $range->getData())
                && in_array($data['code'] ?? null, [self::CODE_RANGE_UNKNOWN_START, self::CODE_RANGE_UNKNOWN_END, self::CODE_RANGE_UNKNOWN_BOTH, self::CODE_MERGED])
            )
            || (
                is_array($data = $this->openingHours->forDate($dateTime)->getData())
                && in_array($data['code'] ?? null, [self::CODE_HOURS_VARY])
            )
        ) {
            return false;
        }

        return true;
    }

    public function mayBeOpened(?\DateTimeInterface $dateTime = null): bool
    {
        if (!$dateTime) {
            $dateTime = new \DateTime('now', $this->timeZone);
        }

        $range = $this->openingHours->currentOpenRange($dateTime);

        if (!$range) {
            return false;
        }

        return true;
    }
}
