<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig\Replacement;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;

class User extends \Twig_Extension
{
    /** @var AwTokenStorage */
    private $tokenStorage;

    public function __construct(AwTokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getContext(): array
    {
        /** @var Usr $user */
        $user = $this->tokenStorage->getUser();

        if (empty($user)) {
            return [];
        }

        return [
            'UserID' => $user->getUserid(),
            'FirstName' => $user->getFirstname(),
            'LastName' => $user->getLastname(),
            'Email' => $user->getEmail(),
            'Login' => $user->getLogin(),
            'RegistrationIP' => $user->getRegistrationip(),
            'LastLogonIP' => $user->getLastlogonip(),
            'isBusiness' => $user->isBusiness(),
            'RefCode' => $user->getRefcode(),
        ];
    }
}
