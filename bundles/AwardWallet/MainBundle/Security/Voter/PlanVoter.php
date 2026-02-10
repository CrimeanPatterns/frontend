<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Plan;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PlanVoter extends AbstractVoter
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

    public function edit(TokenInterface $token, Plan $plan)
    {
        $user = $this->getBusinessUser($token);

        if (empty($user)) {
            return false;
        }

        if ($user->getUserid() == $plan->getUser()->getUserid()) {
            return true;
        }
        $timelineShare = $user->getTimelineShareWith($plan->getUser(), $plan->getUserAgent());

        if (!$timelineShare) {
            return false;
        }

        return $this->timelineShareVoter->edit($token, $timelineShare);
    }

    protected function getAttributes()
    {
        return [
            'EDIT' => [$this, 'edit'],
        ];
    }

    protected function getClass()
    {
        return '\\AwardWallet\\MainBundle\\Entity\\Plan';
    }
}
