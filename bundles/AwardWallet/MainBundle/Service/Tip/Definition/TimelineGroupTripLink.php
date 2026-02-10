<?php

namespace AwardWallet\MainBundle\Service\Tip\Definition;

use AwardWallet\MainBundle\Entity\Usr;

class TimelineGroupTripLink extends Generic implements TipDefinitionInterface
{
    public function getElementId(): string
    {
        return 'timelineGroupTripLink';
    }

    public function getSelector(): string
    {
        return 'a.create-trip:first:visible';
    }

    public function show(Usr $user, string $routeName): bool
    {
        if (!$this->isAvailable($user, $routeName)) {
            return false;
        }

        $planExists = $this->entityManager->getConnection()->fetchColumn('
            SELECT COUNT(*)
            FROM Plan
            WHERE UserID = ?',
            [$user->getUserid()],
            0,
            [\PDO::PARAM_INT]
        );

        return empty($planExists);
    }
}
