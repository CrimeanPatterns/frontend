<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

interface AwTokenStorageInterface extends TokenStorageInterface
{
    /**
     * get current user.
     *
     * @return Usr|null
     */
    public function getUser();

    /**
     * get current business on business domain.
     *
     * @return Usr|bool
     */
    public function getBusiness();

    /**
     * get current user on personal domain and business on business domain.
     *
     * @return Usr|bool|null
     */
    public function getBusinessUser();

    /**
     * optimization. used in SessionListener.
     *
     * @param Usr|bool $user
     */
    public function setBusinessUser($user);

    /**
     * Clear business-user specific state.
     */
    public function clearBusinessUser(): void;
}
