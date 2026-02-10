<?php

namespace AwardWallet\MainBundle\Entity\Listener;

use AwardWallet\MainBundle\Entity\AbPassenger;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class AbPassengerListener
{
    public function prePersist(AbPassenger $passenger, LifecycleEventArgs $args)
    {
        if (!$passenger->getBirthday() || !$passenger->getUseragent()) {
            return;
        }

        $passenger->getUseragent()->setBirthday($passenger->getBirthday());
    }

    public function preUpdate(AbPassenger $passenger, PreUpdateEventArgs $args)
    {
        if (!$passenger->getBirthday() || !$passenger->getUseragent()) {
            return;
        }

        if (!$passenger->getUseragent()->getBirthday()) {
            $passenger->getUseragent()->setBirthday($passenger->getBirthday());
        } else {
            if ($args->hasChangedField('Birthday')) {
                $passenger->getUseragent()->setBirthday($passenger->getBirthday());
            }
        }
    }
}
