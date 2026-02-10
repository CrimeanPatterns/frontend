<?php

namespace AwardWallet\WidgetBundle\Widget\Classes;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\WidgetBundle\Widget\Exceptions\AuthenticationRequiredException;

trait UserWidgetTrait
{
    /** @var Usr|null */
    private $_currentUser;

    /**
     * @return Usr
     * @throws \AwardWallet\WidgetBundle\Widget\Exceptions\AuthenticationRequiredException
     */
    public function getCurrentUser()
    {
        if (empty($this->_currentUser)) {
            /** @var \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token */
            $token = $this->container->get('security.token_storage')->getToken();

            if (empty($token) || !$token->isAuthenticated()) {
                throw new AuthenticationRequiredException();
            }

            /** @var Usr $user */
            $user = $token->getUser();

            if (!($user instanceof Usr)) {
                throw new AuthenticationRequiredException();
            }

            $this->_currentUser = $user;
        }

        return $this->_currentUser;
    }
}
