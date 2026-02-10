<?php

namespace AwardWallet\MainBundle\Service\ConnectionManager;

use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Sitead;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AnonymousInviteAcceptor
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function acceptInviteByAnonymousUser(Invites $invite): void
    {
        $this->session->set('invId', $invite->getInvitesid());
        $this->session->set('inviterId', $invite->getInviterid()->getUserid());
        $this->session->set('InviteCode', $invite->getCode());
        $this->session->set('ref', Sitead::REF_INVITE_OPTION);
    }
}
