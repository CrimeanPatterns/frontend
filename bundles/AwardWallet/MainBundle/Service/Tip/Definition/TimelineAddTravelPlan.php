<?php

namespace AwardWallet\MainBundle\Service\Tip\Definition;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Timeline\Manager as TimelineManager;
use Doctrine\ORM\EntityManagerInterface;

class TimelineAddTravelPlan extends Generic implements TipDefinitionInterface
{
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
        return 'headerTimelineButtonAdd';
    }

    public function show(Usr $user, string $routeName): bool
    {
        if (!$this->isAvailable($user, $routeName)) {
            return false;
        }

        $countTrips = array_sum(array_column($this->timelineManager->getTotals($user), 'count'));

        return 0 === $countTrips;
    }
}
