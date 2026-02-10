<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;

class DateTimeResolver
{
    private Connection $connection;

    private array $airportTimezones = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function resolveByTimeZoneId(\DateTime $dateTime, string $timeZoneId): ?\DateTime
    {
        try {
            return new \DateTime($dateTime->format('Y-m-d H:i:s'), $this->getTimeZone($timeZoneId));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function resolveByTimeZoneOffset(\DateTime $dateTime, int $offset): ?\DateTime
    {
        $negative = $offset < 0;
        $offset = abs($offset);
        $hours = floor($offset / (60 * 60));
        $minutes = floor(($offset / 60) % 60);
        $tz = sprintf(
            '%s%s:%s',
            $negative ? '-' : '+',
            str_pad($hours, 2, 0, STR_PAD_LEFT),
            str_pad($minutes, 2, 0, STR_PAD_LEFT),
        );

        return $this->resolveByTimeZoneId($dateTime, $tz);
    }

    public function resolveByAirCode(\DateTime $dateTime, string $airCode): ?\DateTime
    {
        if (isset($this->airportTimezones[$airCode])) {
            $tz = $this->airportTimezones[$airCode];
        } else {
            $tz = $this->connection->executeQuery("
                SELECT TimeZoneLocation FROM AirCode WHERE AirCode = ?",
                [$airCode]
            )->fetchOne();

            if ($tz === false) {
                $tz = null;
            }

            $this->airportTimezones[$airCode] = $tz;
        }

        if (is_null($tz)) {
            return null;
        }

        return $this->resolveByTimeZoneId($dateTime, $tz);
    }

    public function resolve(
        \DateTime $dateTime,
        ?string $timeZoneId = null,
        ?int $offset = null,
        ?string $airCode = null
    ): ?\DateTime {
        $result = null;

        if (!is_null($timeZoneId)) {
            $result = $this->resolveByTimeZoneId($dateTime, $timeZoneId);
        }

        if (is_null($result) && !is_null($offset)) {
            $result = $this->resolveByTimeZoneOffset($dateTime, $offset);
        }

        if (is_null($result) && !is_null($airCode)) {
            $result = $this->resolveByAirCode($dateTime, $airCode);
        }

        return $result;
    }

    private function getTimeZone(string $timeZoneId): \DateTimeZone
    {
        return new \DateTimeZone($timeZoneId);
    }
}
