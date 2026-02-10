<?php

namespace AwardWallet\MainBundle\Service\Tip\Definition;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Manager as TimelineManager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TimelineLink extends Generic implements TipDefinitionInterface
{
    private const MAX_FUTURE_SEGMENTS_FOR_TIP_CALCULATION = 1000;
    protected TimelineManager $timelineManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        TimelineManager $timelineManager
    ) {
        parent::__construct($entityManager);
        $this->timelineManager = $timelineManager;
    }

    public function getElementId(): string
    {
        return 'headerTimelineButtonLink';
    }

    public static function getTipQueryOptions(Usr $user): QueryOptions
    {
        return (new QueryOptions())
            ->setUser($user)
            ->setWithDetails(true)
            ->setFormat(ItemFormatterInterface::DESKTOP)
            ->setFuture(true)
            ->setMaxSegments(10)
            ->setMaxFutureSegments(100)
            ->setShowDeleted(false);
    }

    public function show(Usr $user, string $routeName): ?bool
    {
        if ($this->timelineManager->getSegmentCount($user) >= self::MAX_FUTURE_SEGMENTS_FOR_TIP_CALCULATION) {
            return null;
        }

        $queryOptions = self::getTipQueryOptions($user);
        $data = $this->timelineManager->query($queryOptions);
        $currentTime = \time();
        $foundTrip =
            it($data)
            ->any(fn (array $item) =>
                ('segment' === $item['type'])
                && ($currentTime < $item['startDate'])
            );

        return $foundTrip ? $this->isAvailable($user, $routeName) : null;
    }
}
