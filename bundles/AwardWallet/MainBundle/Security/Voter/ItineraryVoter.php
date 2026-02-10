<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ItineraryVoter extends AbstractVoter
{
    /**
     * @var TimelineShareVoter
     */
    protected $timelineShareVoter;

    public function __construct(ContainerInterface $container, TimelineShareVoter $timelineShareVoter)
    {
        parent::__construct($container);

        $this->timelineShareVoter = $timelineShareVoter;
    }

    public function autologin(TokenInterface $token, Itinerary $itinerary)
    {
        $context = $this->container->get('security.authorization_checker');
        $isGranted = empty($itinerary->getAccount()) ? true : $context->isGranted('AUTOLOGIN', $itinerary->getAccount());
        $isImpersonated = $context->isGranted('USER_IMPERSONATED');

        if (!$isGranted && !$isImpersonated) {
            return false;
        }

        $user = $this->getBusinessUser($token);

        if (null === $user || false === $this->view($token, $itinerary)) {
            return false;
        }

        $itineraryAutologin = $itinerary->getProvider() ? $itinerary->getProvider()->getItineraryautologin() : null;
        $isAutologinAccount = $itinerary->canAutologinWithAccount($user);
        $isAutologinConfNo = $itinerary->canAutologinWithConfNo($user);

        if (ITINERARY_AUTOLOGIN_DISABLED === $itineraryAutologin) {
            return false;
        }

        if ((ITINERARY_AUTOLOGIN_ACCOUNT === $itineraryAutologin && $isAutologinAccount)
            || (ITINERARY_AUTOLOGIN_CONFNO === $itineraryAutologin && $isAutologinConfNo)
            || (ITINERARY_AUTOLOGIN_BOTH === $itineraryAutologin && ($isAutologinAccount || $isAutologinConfNo))
        ) {
            return true;
        }

        return false;
    }

    public function view(TokenInterface $token, Itinerary $itinerary)
    {
        $user = $this->getBusinessUser($token);

        if (empty($user)) {
            return false;
        }

        if ($user->getUserid() == $itinerary->getUser()->getUserid()) {
            return true;
        }
        $timelineShare = $user->getTimelineShareWith($itinerary->getUser(), $itinerary->getUserAgent());

        if (!$timelineShare) {
            return false;
        }

        return $this->timelineShareVoter->view($token, $timelineShare);
    }

    public function update(TokenInterface $token, Itinerary $itinerary)
    {
        $context = $this->container->get('security.authorization_checker');
        $user = $this->getBusinessUser($token);

        if ($user instanceof Usr) {
            $account = $itinerary->getAccount();

            if (!empty($account)) {
                return $context->isGranted('UPDATE', $account);
            } else {
                return $this->view($token, $itinerary) && $itinerary->canRefreshByConfNo($user);
            }
        }

        return false;
    }

    public function edit(TokenInterface $token, Itinerary $itinerary)
    {
        $user = $this->getBusinessUser($token);

        if (empty($user)) {
            return false;
        }

        if ($user->getUserid() == $itinerary->getUser()->getUserid()) {
            return true;
        }
        $timelineShare = $user->getTimelineShareWith($itinerary->getUser(), $itinerary->getUserAgent());

        if (!$timelineShare) {
            return false;
        }

        return $this->timelineShareVoter->edit($token, $timelineShare);
    }

    protected function getAttributes()
    {
        return [
            'AUTOLOGIN' => [$this, 'autologin'],
            'VIEW' => [$this, 'view'],
            'UPDATE' => [$this, 'update'],
            'EDIT' => [$this, 'edit'],
        ];
    }

    protected function getClass()
    {
        return '\\AwardWallet\\MainBundle\\Entity\\Itinerary';
    }
}
