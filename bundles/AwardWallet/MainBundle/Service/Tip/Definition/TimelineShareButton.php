<?php

namespace AwardWallet\MainBundle\Service\Tip\Definition;

use AwardWallet\MainBundle\Entity\Usr;

class TimelineShareButton extends Generic implements TipDefinitionInterface
{
    public function getElementId(): string
    {
        return 'timelineShareButton';
    }

    public function getSelector(): string
    {
        return 'a[data-ng-click="segment.shareTravelplanDialog = true"]:visible';
    }

    public function show(Usr $user, string $routeName): ?bool
    {
        $tip = $this->findTip($routeName);

        if (empty($tip)) {
            return null;
        }

        $userTipRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\UserTip::class);
        $userTip = $userTipRep->findOneBy([
            'userId' => $user,
            'tipId' => $tip,
        ]);

        if (!empty($userTip)) {
            $eventDate = $userTip->getClickDate() ?? $userTip->getCloseDate() ?? false;

            if (empty($eventDate)) {
                return true;
            }

            $interval = $userTip->getShowDate()->diff(new \DateTime());

            if ((int) $interval->format('%a') < $tip->getReshowInterval()) {
                return false;
            }
        }

        return true;
    }
}
