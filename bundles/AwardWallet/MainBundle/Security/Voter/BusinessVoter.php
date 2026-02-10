<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BusinessVoter extends AbstractVoter
{
    private const ACCOUNTS_ACCESS_LEVELS = [ACCESS_ADMIN, ACCESS_BOOKING_ADMINISTRATOR, ACCESS_BOOKING_MANAGER];

    public function businessAccounts(TokenInterface $token): bool
    {
        if (!$this->isBusiness()) {
            return false;
        }

        $business = $this->getBusinessUser($token);

        if ($business === null) {
            return false;
        }

        $user = $token->getUser();

        if (!($user instanceof Usr)) {
            return false;
        }

        $agentRepo = $this->container->get('doctrine')->getRepository(Useragent::class);
        /** @var Useragent $connection */
        $connection = $agentRepo->findOneBy(['agentid' => $user, 'clientid' => $business, 'isapproved' => 1]);

        if ($connection === null) {
            return false;
        }

        /*$reverseConnection = $agentRepo->findOneBy(['agentid' => $business, 'clientid' => $user, 'isapproved' => 1]);
        if ($reverseConnection === null) {
            return false;
        }*/

        if (!in_array($connection->getAccesslevel(), self::ACCOUNTS_ACCESS_LEVELS)) {
            return false;
        }

        return true;
    }

    protected function getAttributes()
    {
        return [
            'BUSINESS_ACCOUNTS' => [$this, "businessAccounts"],
        ];
    }

    protected function getClass()
    {
        return null;
    }
}
