<?php

namespace AwardWallet\MainBundle\Service\Tip\Definition;

use AwardWallet\MainBundle\Entity\Tip;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;

abstract class Generic
{
    protected EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function findTip(string $routeName): ?Tip
    {
        $tipRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Tip::class);

        return $tipRep->findOneBy([
            'element' => $this->getElementId(),
            'route' => $routeName,
            'enabled' => 1,
        ]);
    }

    protected function isAvailable(Usr $user, string $routeName): bool
    {
        $tip = $this->findTip($routeName);

        if (empty($tip)) {
            return false;
        }

        $userTipRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\UserTip::class);
        $userTip = $userTipRep->findOneBy([
            'userId' => $user,
            'tipId' => $tip,
        ]);

        if (!empty($userTip)) {
            $eventDate = $userTip->getClickDate() ?? $userTip->getCloseDate() ?? null;

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
