<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class Discount30Listener
{
    public const LOGON_LONGTIME_DAYS = 1;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var SessionInterface */
    private $session;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();
        $user = $event->getAuthenticationToken()->getUser();

        if (!($user instanceof Usr) || null === $session) {
            return;
        }

        if (!$user->isAwPlus()
            && empty($user->getSubscription())
            && null !== $user->getPlusExpirationDate()
            && $user->getPlusExpirationDate()->getTimestamp() < time()
            && null !== $user->getLastlogondatetime()
            && $user->getLastlogondatetime()->diff(new \DateTime())->days >= self::LOGON_LONGTIME_DAYS
            && $user->getUpgradeSkippedCount() <= PlusManager::LIMIT_UPGRADE_SKIPPED) {
            $session->set(PlusManager::SESSION_KEY_SHOW_UPGRADE_POPUP, true);
        }
    }
}
