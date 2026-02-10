<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\AccountList\Classes\AbstractResolver;
use AwardWallet\MainBundle\Manager\AccountList\Classes\AccessResolverInterface;
use AwardWallet\MainBundle\Manager\AccountList\Classes\ConverterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class AccessResolver.
 *
 * @property Converter[] $items
 */
class AccessResolver extends AbstractResolver implements AccessResolverInterface
{
    /**
     * @var Usr
     */
    private $user;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        TokenStorageInterface $tokenStorage
    ) {
        $this->tokenStorage = $tokenStorage;

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
        $beta = false;
        $staff = false;

        if ($this->user) {
            $beta = $this->user->getBetaapproved();
            $staff = $this->user->hasRole('ROLE_STAFF');
        }

        foreach ($this->items as $item) {
            $state = $item->getEntity()->getState();

            if (!($state > 0
                || is_null($state)
                || ($beta && $state == PROVIDER_IN_BETA)
                || ($staff && $state == PROVIDER_TEST)
            )) {
                $item->remove();
            }
        }
        $this->items = [];
    }

    public function setUser(Usr $user)
    {
        $this->user = $user;
    }
}
