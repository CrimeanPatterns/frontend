<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Entity\DateRangeInterface;
use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Service\ItineraryComparator\Comparator;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Util;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Tracker
{
    private const MAX_BYTES = 250;

    /** @var PropertySourceInterface[] */
    protected array $sources;
    protected ?\Doctrine\DBAL\Driver\Statement $query = null;
    protected EventDispatcherInterface $eventDispatcher;
    protected Comparator $comparator;
    private LoggerInterface $logger;
    private Connection $connection;
    private ClockInterface $clock;

    public function __construct(
        Connection $connection,
        EventDispatcherInterface $eventDispatcher,
        Comparator $comparator,
        LoggerInterface $logger,
        ClockInterface $clock
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->comparator = $comparator;
        $this->logger = $logger;
        $this->connection = $connection;
        $this->clock = $clock;
    }

    public function addSource(PropertySourceInterface $source)
    {
        $this->sources[] = $source;
    }

    /**
     * use this function to grab state of account before making changes to account.
     *
     * @param int $accountId
     * @return Properties[]
     */
    public function getProperties($accountId)
    {
        $result = [];

        foreach ($this->sources as $source) {
            $result = array_merge(
                $result,
                array_filter(
                    $source->getProperties($accountId),
                    function (Properties $properties) {
                        return !empty($properties->values);
                    }
                )
            );
        }

        foreach ($result as $properties) {
            $properties->values = array_map(function (string $value) {
                $chars = self::MAX_BYTES;

                do {
                    // substr actully works with bytes
                    $result = substr($value, 0, $chars);
                    $chars--;
                } while (strlen($result) > self::MAX_BYTES);

                return $result;
            }, $properties->values);
        }

        return $result;
    }

    /**
     * use this function to record changes after you have changed account.
     *
     * @param Properties[] $oldProperties - array from getProperties call
     * @param int $accountId
     * @return int
     */
    public function recordChanges(array $oldProperties, $accountId, $userId)
    {
        $newProperties = $this->getProperties($accountId);
        [$added, $removed, $changed, $changedOld, $changedNames] = $this->compareAndRecord($oldProperties, $newProperties);

        if (
            count($added) > 0
            || count($removed) > 0
            || count($changed) > 0
        ) {
            $this->eventDispatcher->dispatch(new ItineraryUpdateEvent($userId, $added, $removed, $changed, $changedOld, $changedNames), ItineraryUpdateEvent::NAME);
        }

        return count($changed);
    }

    /**
     * @param Properties[] $oldProperties
     * @param Properties[] $newProperties
     * @return array [Properties[], Properties[], Properties[]]
     */
    protected function compareAndRecord(array $oldProperties, array $newProperties)
    {
        if ($this->query === null) {
            $this->query = $this->connection->prepare("
            INSERT INTO DiffChange(SourceID, Property, OldVal, NewVal, ChangeDate, ExpirationDate)
            VALUES(?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE NewVal = ?
            ");
        }

        $changedProperties =
        $changedOldProperties =
        $changedNames =
        $addedProperties =
        $removedProperties = [];

        $changeDate = $this->clock->current()->getAsDateTime();

        foreach ($oldProperties as $sourceId => $old) {
            if (empty($newProperties[$sourceId])) {
                $removedProperties[$sourceId] = $old;
            } else {
                $entity = $old->getEntity();

                if ($this->isEntityInPast($entity)) {
                    continue;
                }
                $kind = Util::getKind($entity);
                $new = $newProperties[$sourceId];
                $changed = false;

                foreach ($old->values as $property => $oldValue) {
                    if (
                        (trim($new->values[$property] ?? '') !== '')
                        && !$this->comparator->equals($new->values[$property], $oldValue, $property, $kind)
                    ) {
                        try {
                            $this->query->execute([
                                $sourceId,
                                $property,
                                $oldValue,
                                $new->values[$property],
                                $changeDate->format('Y-m-d H:i:s'),
                                $new->expirationDate->format('Y-m-d H:i:s'),
                                $new->values[$property],
                            ]);
                        } catch (DriverException $e) {
                            $this->logger->warning("error inserting DiffChange: " . json_encode([
                                $sourceId,
                                $property,
                                $oldValue,
                                $new->values[$property],
                                $changeDate->format('Y-m-d H:i:s'),
                                $new->expirationDate->format('Y-m-d H:i:s'),
                                $new->values[$property],
                            ]));

                            throw $e;
                        }
                        $changed = true;
                        $changedNames[$sourceId][] = $property;
                    }
                }

                if ($changed) {
                    $changedProperties[$sourceId] = $new;
                    $changedOldProperties[$sourceId] = $old;
                    $new->source->recordChanges($new, $changeDate);
                }
            }
        }

        foreach ($newProperties as $sourceId => $new) {
            if ($this->isEntityInPast($new->getEntity())) {
                continue;
            }

            if (empty($oldProperties[$sourceId])) {
                $addedProperties[$sourceId] = $new;
            }
        }

        return [$addedProperties, $removedProperties, $changedProperties, $changedOldProperties, $changedNames];
    }

    private function isEntityInPast($entity): bool
    {
        return
            $entity instanceof DateRangeInterface
            && ($endDate = $entity->getUTCEndDate())
            && $endDate->getTimestamp() < ($this->clock->current()->getAsSecondsInt() - 86400)
        ;
    }
}
