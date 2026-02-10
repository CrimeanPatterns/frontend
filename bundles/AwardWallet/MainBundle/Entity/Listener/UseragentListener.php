<?php

namespace AwardWallet\MainBundle\Entity\Listener;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Service\TwoFactorAuthChecker;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class UseragentListener
{
    private TwoFactorAuthChecker $twoFactorAuthChecker;

    public function __construct(TwoFactorAuthChecker $twoFactorAuthChecker)
    {
        $this->twoFactorAuthChecker = $twoFactorAuthChecker;
    }

    public function prePersist(Useragent $useragent, LifecycleEventArgs $args)
    {
        $this->twoFactorAuthChecker->resetCache($useragent->getAgentid());
    }

    public function preUpdate(Useragent $useragent, PreUpdateEventArgs $args)
    {
        $this->twoFactorAuthChecker->resetCache($useragent->getAgentid());
    }
}
