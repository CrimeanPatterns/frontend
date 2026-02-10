<?php

namespace AwardWallet\MainBundle\Globals\Cart;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartUserSource
{
    public const SESSION_PAYER_INFO = "CartUser_PayerInfo";
    /** @var AwTokenStorageInterface */
    private $tokenStorage;

    /** @var SessionInterface */
    private $session;

    /** @var UsrRepository */
    private $userRepo;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        SessionInterface $session,
        UsrRepository $userRepo
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
        $this->userRepo = $userRepo;
    }

    public function getCartOwner(): ?Usr
    {
        $userInfo = $this->getCartUserInfo();

        if ($userInfo !== null) {
            return $this->userRepo->find($userInfo->getCartOwnerId());
        }

        return $this->tokenStorage->getBusinessUser();
    }

    public function getPayer(): ?Usr
    {
        $userInfo = $this->getCartUserInfo();

        if ($userInfo !== null) {
            return $this->userRepo->find($userInfo->getPayerId());
        }

        return $this->tokenStorage->getUser();
    }

    public function setUser(CartUserInfo $cartUserInfo): self
    {
        $this->session->set(self::SESSION_PAYER_INFO, $cartUserInfo);

        return $this;
    }

    public function clearUser(): self
    {
        $this->session->remove(self::SESSION_PAYER_INFO);

        return $this;
    }

    private function getCartUserInfo(): ?CartUserInfo
    {
        /** @var CartUserInfo $result */
        $result = $this->session->get(self::SESSION_PAYER_INFO);

        if ($result !== null && $result->isAnonymousOnly() && $this->tokenStorage->getUser() !== null) {
            return null;
        }

        return $result;
    }
}
