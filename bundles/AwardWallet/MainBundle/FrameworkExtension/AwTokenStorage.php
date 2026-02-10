<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AwTokenStorage implements AwTokenStorageInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var UsrRepository
     */
    private $userRep;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var bool
     */
    private $businessCached;
    /**
     * @var Usr
     */
    private $businessUser;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker, EntityManagerInterface $entityManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->entityManager = $entityManager;
        $this->userRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
    }

    public function getUser()
    {
        if (null === $this->getToken()) {
            return null;
        }

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $result = $this->getToken()->getUser();

            if ($this->authorizationChecker->isGranted('ROLE_AWPLUS')) {
                $result->forceAwPlus();
            }

            return $result;
        }

        return null;
    }

    public function getBusiness()
    {
        $user = $this->getUser();

        if ($user && $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA') && !$user->isBusiness()) {
            return $this->userRep->getBusinessByUser($user) ?: false;
        }

        return false;
    }

    public function getBusinessUser()
    {
        $user = $this->getUser();

        if ($user && $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            if ($this->businessCached) {
                $user = $this->businessUser;
            } else {
                $user = $this->userRep->getBusinessByUser($user);
            }
        }

        return $user;
    }

    public function setBusinessUser($user)
    {
        $this->businessUser = $user;
        $this->businessCached = true;
    }

    public function getToken()
    {
        return $this->tokenStorage->getToken();
    }

    public function setToken(?TokenInterface $token = null)
    {
        $this->tokenStorage->setToken($token);
    }

    public function clearBusinessUser(): void
    {
        $this->businessCached = false;
        $this->businessUser = null;
    }
}
