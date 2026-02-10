<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Subaccount;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SubAccountVoter extends AbstractVoter
{
    /**
     * @var AccountVoter
     */
    private $accountVoter;

    public function __construct(ContainerInterface $container, AccountVoter $accountVoter)
    {
        parent::__construct($container);

        $this->accountVoter = $accountVoter;
    }

    public function edit(TokenInterface $token, Subaccount $subaccount)
    {
        return $this->accountVoter->edit($token, $subaccount->getAccountid());
    }

    public function delete(TokenInterface $token, Subaccount $subaccount)
    {
        return $this->accountVoter->delete($token, $subaccount->getAccountid());
    }

    protected function getClass()
    {
        return Subaccount::class;
    }

    protected function getAttributes()
    {
        return [
            'EDIT' => [$this, 'edit'],
            'DELETE' => [$this, 'delete'],
        ];
    }
}
