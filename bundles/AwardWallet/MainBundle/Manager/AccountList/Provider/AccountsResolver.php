<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\AccountList\Classes\AbstractResolver;
use AwardWallet\MainBundle\Manager\AccountList\Classes\ConverterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class AccountsResolver.
 *
 * @property Converter[] $items
 */
class AccountsResolver extends AbstractResolver
{
    /**
     * @var Usr
     */
    private $user;

    /**
     * @var ProviderRepository
     */
    private $providerRep;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var array
     */
    private $stats = [];

    public function __construct(
        ProviderRepository $providerRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->providerRep = $providerRepository;

        $user = $this->tokenStorage->getToken()->getUser();

        if ($user instanceof Usr) {
            $this->user = $user;
        }
    }

    public function add(ConverterInterface $item)
    {
        if ($item instanceof Converter) {
            $this->items[] = $item;
        }
    }

    public function resolve()
    {
        if ($this->user && $this->items) {
            if (!array_key_exists($this->user->getUserid(), $this->stats)) {
                $this->stats[$this->user->getUserid()] = $this->providerRep->getProviderAccountsCount($this->user);
            }
            $stats = $this->stats[$this->user->getUserid()];

            foreach ($this->items as $item) {
                if (array_key_exists($item->getEntity()->getProviderid(), $stats)) {
                    $item->setAccounts($stats[$item->getEntity()->getProviderid()]);
                } else {
                    $item->setAccounts(0);
                }
            }
        }
        $this->items = [];
    }

    public function setUser(Usr $user)
    {
        $this->user = $user;
    }
}
