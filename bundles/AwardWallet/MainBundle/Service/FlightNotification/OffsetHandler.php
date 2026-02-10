<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use AwardWallet\MainBundle\Timeline\TripInfo\TripInfo;
use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

class OffsetHandler
{
    public const CATEGORY_PUSH = 'push';
    public const CATEGORY_MAIL = 'mail';

    public const CATEGORIES = [
        self::CATEGORY_PUSH,
        self::CATEGORY_MAIL,
    ];

    public const KIND_PRECHECKIN = 'precheckin';
    public const KIND_CHECKIN = 'checkin';
    public const KIND_DEPARTURE = 'departure';
    public const KIND_BOARDING = 'boarding';

    public const PREPARE_OFFSET = 3 * 60 * 60;

    private const DEFAULT_PROVIDER_OFFSETS = [
        16 => [ // rapidrewards
            self::CATEGORY_PUSH => [
                self::KIND_PRECHECKIN => 24.25,
            ],
        ],
    ];

    private ?LazyVal $offsetMap;

    private AirlineMap $airlineMap;

    public function __construct(Connection $connection, AirlineMap $airlineMap)
    {
        $this->offsetMap = lazy(function () use ($connection) {
            $map = [];
            $stmt = $connection->executeQuery("
                SELECT 
                   ProviderID,
                   CheckInReminderOffsets 
                FROM Provider
            ");

            while ($row = $stmt->fetchAssociative()) {
                $providerId = (int) $row['ProviderID'];
                $providerOffsets = @json_decode($row['CheckInReminderOffsets'], true);

                if (!is_array($providerOffsets)) {
                    $providerOffsets = self::getDefaultOffsets();
                }

                if (isset(self::DEFAULT_PROVIDER_OFFSETS[$providerId])) {
                    foreach (array_keys(self::DEFAULT_PROVIDER_OFFSETS[$providerId]) as $category) {
                        $providerOffsets[$category] = array_merge($providerOffsets[$category] ?? [], self::DEFAULT_PROVIDER_OFFSETS[$providerId][$category]);
                    }
                }

                $map[$providerId] = $this->setKinds($providerId, $providerOffsets);
            }

            return $map;
        });
        $this->airlineMap = $airlineMap;
    }

    /**
     * @return OffsetStatus[]
     */
    public function getOffsetsStatusesBySegment(
        Tripsegment $tripSegment,
        ?\DateTimeInterface $now = null
    ): array {
        $tripInfo = TripInfo::createFromTripSegment($tripSegment);
        $trip = $tripSegment->getTripid();
        $account = $trip->getAccount();

        if ($account && $account->getProviderid()) {
            $providerId = $account->getProviderid()->getId();
        } elseif ($trip->getRealProvider()) {
            $providerId = $trip->getRealProvider()->getId();
        } else {
            $providerId = null;
        }

        if (isset($tripInfo->primaryTripNumberInfo->companyInfo)) {
            $companyInfo = $tripInfo->primaryTripNumberInfo->companyInfo;

            if (
                $companyInfo->companyObject instanceof Airline
                && !empty($iataCode = $companyInfo->companyObject->getCode())
                && is_array($providers = $this->airlineMap->get($iataCode))
            ) {
                if ($providerId && in_array($providerId, $providers)) {
                    $operatingProviderId = $providerId;
                } else {
                    $operatingProviderId = $providers[0];
                }
            } elseif ($companyInfo->companyObject instanceof Provider) {
                $operatingProviderId = $companyInfo->companyObject->getId();
            } else {
                $operatingProviderId = $providerId;
            }
        } else {
            $operatingProviderId = $providerId;
        }

        return $this->getOffsetsStatusesByProviderId(
            $operatingProviderId,
            $now ?? new \DateTime(),
            $tripSegment->getUTCStartDate()
        );
    }

    /**
     * @return OffsetStatus[]
     */
    public function getOffsetsStatusesByProviderId(
        ?int $providerId,
        \DateTimeInterface $now,
        \DateTimeInterface $depDate,
        bool $mergeCategories = true
    ): array {
        $now = $now->getTimestamp();
        $depDate = $depDate->getTimestamp();

        if ($providerId && is_array($this->getOffsets($providerId))) {
            $providerOffsets = $this->getOffsets($providerId);
        } else {
            $providerOffsets = self::getDefaultOffsets();
        }
        /** @var OffsetStatus[] $statuses */
        $statuses = [];

        foreach (self::CATEGORIES as $category) {
            if (!isset($providerOffsets[$category])) {
                continue;
            }

            arsort($providerOffsets[$category]);
            $prevStatus = null;

            foreach ($providerOffsets[$category] as $kind => $hourOffset) {
                $secondsOffset = ceil($hourOffset * 60 * 60);
                $key = sprintf('%s-%d', $providerId ?? 'null', $secondsOffset);
                $deadline = $this->getDeadline($hourOffset, $providerOffsets[$category]);

                if (
                    (
                        $now + $secondsOffset + self::PREPARE_OFFSET
                    ) >= $depDate
                    && ($now + $deadline) <= $depDate
                ) {
                    if (isset($statuses[$key]) && $mergeCategories) {
                        $statuses[$key]->addCategory($category);
                    } else {
                        $status = new OffsetStatus(
                            $providerId,
                            $kind,
                            [$category],
                            $hourOffset,
                            $secondsOffset,
                            $depDate - $secondsOffset - $now,
                            $deadline,
                            $depDate - $secondsOffset
                        );

                        if ($prevStatus instanceof OffsetStatus) {
                            $prevStatus->setNextStatus($status);
                        }

                        $prevStatus = $status;

                        if ($mergeCategories) {
                            $statuses[$key] = $status;
                        } else {
                            $statuses[] = $status;
                        }
                    }
                }
            }
        }

        return array_values($statuses);
    }

    public function getOffsets(int $providerId): ?array
    {
        return $this->offsetMap[$providerId] ?? null;
    }

    public function getOffsetMap(): array
    {
        return $this->offsetMap->getValue();
    }

    public function getDeadline(float $currentHoursOffset, array $hoursOffsets): int
    {
        $kind = array_search($currentHoursOffset, $hoursOffsets);

        if ($kind === false) {
            throw new \InvalidArgumentException(sprintf('Provider offset "%s" was not found', $currentHoursOffset));
        }

        $current = ceil($currentHoursOffset * 3600);

        if (
            $kind === self::KIND_PRECHECKIN
            && isset($hoursOffsets[self::KIND_CHECKIN])
            && $currentHoursOffset > $hoursOffsets[self::KIND_CHECKIN]
        ) {
            return ceil($hoursOffsets[self::KIND_CHECKIN] * 3600) + 60;
        } elseif ($kind === self::KIND_CHECKIN) {
            return 3 * 3600;
        } elseif ($kind === self::KIND_DEPARTURE) {
            return \max($current - 3600, 0);
        } elseif ($kind === self::KIND_BOARDING) {
            return \max($current - 60 * 30, 0);
        }

        return \max($current - 3 * 3600, 0);
    }

    public static function getDefaultOffsets(): array
    {
        return self::setKinds(null, [
            self::CATEGORY_PUSH => [1, 4, 24],
            self::CATEGORY_MAIL => [24],
        ]);
    }

    private static function setKinds(?int $providerId, array $providerOffsets): array
    {
        $prepared = [];

        foreach ($providerOffsets as $category => $offsets) {
            $prepared[$category] = [];

            foreach ($offsets as $offset) {
                $prepared[$category][self::detectKind($offset, $category, $providerId)] = $offset;
            }

            asort($prepared[$category]);
        }

        return $prepared;
    }

    private static function detectKind(float $hourOffset, string $category, ?int $providerId): string
    {
        if (
            !is_null($providerId)
            && !is_null(self::DEFAULT_PROVIDER_OFFSETS[$providerId][$category] ?? null)
            && is_string($kind = array_search($hourOffset, self::DEFAULT_PROVIDER_OFFSETS[$providerId][$category]))
        ) {
            return $kind;
        }

        if ($hourOffset < 2) {
            return self::KIND_BOARDING;
        }

        if ($hourOffset < 10) {
            return self::KIND_DEPARTURE;
        }

        return self::KIND_CHECKIN;
    }
}
