<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Security\Utils;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AccountVoter extends AbstractVoter
{
    /**
     * @var SessionVoter
     */
    private $sessionVoter;
    /**
     * @var SiteVoter
     */
    private $siteVoter;
    private ProviderVoter $providerVoter;

    public function __construct(
        ContainerInterface $container,
        SessionVoter $sessionVoter,
        ProviderVoter $providerVoter,
        SiteVoter $siteVoter
    ) {
        parent::__construct($container);

        $this->sessionVoter = $sessionVoter;
        $this->siteVoter = $siteVoter;
        $this->providerVoter = $providerVoter;
    }

    public function readPassword(TokenInterface $token, Account $account)
    {
        return $this->fullRights($token, $account);
    }

    public function readNumber(TokenInterface $token, Account $account)
    {
        return $this->fullRights($token, $account, [
            ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER,
            ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL,
            ACCESS_READ_BALANCE_AND_STATUS, ACCESS_READ_NUMBER,
        ]);
    }

    public function readBalance(TokenInterface $token, Account $account)
    {
        return $this->fullRights($token, $account, [
            ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER,
            ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL,
            ACCESS_READ_BALANCE_AND_STATUS,
        ]);
    }

    public function readExtProperties(TokenInterface $token, Account $account)
    {
        return $this->fullRights($token, $account, [
            ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER,
            ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL,
        ]);
    }

    public function readHistory(TokenInterface $token, Account $account)
    {
        return $this->readExtProperties($token, $account)
               && $this->siteVoter->isAwPlus($token, null);
    }

    public function readTransactions(TokenInterface $token, Account $account): bool
    {
        return $this->fullRights($token, $account, [
            ACCESS_WRITE, ACCESS_ADMIN, ACCESS_READ_ALL,
        ]);
    }

    public function edit(TokenInterface $token, Account $account)
    {
        return $this->readPassword($token, $account);
    }

    public function save(TokenInterface $token, Account $account)
    {
        return $this->edit($token, $account);
    }

    public function delete(TokenInterface $token, Account $account)
    {
        if (Utils::tokenHasRole($token, 'ROLE_IMPERSONATED')) {
            $this->siteVoter->setIsImpersonationSandboxEscaped(true);

            return false;
        }

        return $this->readPassword($token, $account);
    }

    public function autologin(TokenInterface $token, Account $account)
    {
        $provider = $account->getProviderid();
        $user = $this->getBusinessUser($token);

        return $this->readPassword($token, $account)
            && isset($provider) && $provider instanceof Provider
            && $provider->canAutologin($user)
            && $this->impersonatorHasAccess($token, $account)
            && ($provider->getAutologin() !== AUTOLOGIN_EXTENSION || $this->canAutologinWithExtension($token, $account))
            && !$account->isDisableClientPasswordAccess();
    }

    public function autologinWithExtension(TokenInterface $token, Account $account)
    {
        $provider = $account->getProviderid();
        $user = $this->getBusinessUser($token);

        return $this->readPassword($token, $account)
            && isset($provider) && $provider instanceof Provider
            && $provider->canAutologin($user)
            && $this->impersonatorHasAccess($token, $account)
            && $this->canAutologinWithExtension($token, $account)
            && !$account->isDisableClientPasswordAccess()
            && $provider->isAutologinV3();
    }

    public function update(TokenInterface $token, Account $account)
    {
        $provider = $account->getProviderid();
        $provider = (isset($provider) && $provider instanceof Provider) ? $provider : false;
        $user = $this->getBusinessUser($token);

        return $this->fullRights($token, $account, [
            ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER,
        ])
        && (
            $account->canCheck($user)
            && $provider
            && (
                ($provider->getState() !== PROVIDER_CHECKING_EXTENSION_ONLY)
                || $this->canUpdateInClientV3($token, $account)
                || $this->canUpdateInClient($token, $account)
            )
        );
    }

    // we allow to update southwest for group update
    public function updateGroup(TokenInterface $token, Account $account)
    {
        $provider = $account->getProviderid();
        $provider = (isset($provider) && $provider instanceof Provider) ? $provider : false;
        $user = $this->getBusinessUser($token);

        return $this->fullRights($token, $account, [
            ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER,
        ])
        && (
            $account->canCheck($user)
            && $provider
            && (
                ($provider->getState() !== PROVIDER_CHECKING_EXTENSION_ONLY)
                || $this->updateClientV3($token, $account)
                || $this->updateClient($token, $account)
            )
        );
    }

    public function updateItinerary(TokenInterface $token, Account $account)
    {
        $provider = $account->getProviderid();
        $provider = (isset($provider) && $provider instanceof Provider) ? $provider : false;
        $user = $this->getBusinessUser($token);

        return
            $this->fullRights($token, $account, [
                ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER,
            ])
            && $user->getAutogatherplans()
            && $provider
            && $provider->userHasAccess($user)
            && $provider->getCancheckitinerary()
            && ($provider->getCancheckconfirmation() !== CAN_CHECK_CONFIRMATION_YES_EXTENSION || $this->canUseBrowserExtensionForUpdate($token, $account));
    }

    public function updateClientV3(TokenInterface $token, Account $account)
    {
        return
            $this->canUpdateInClientV3($token, $account)
            && $this->update($token, $account)
        ;
    }

    public function updateClient(TokenInterface $token, Account $account)
    {
        return
            $this->canUpdateInClient($token, $account)
            && $this->update($token, $account)
        ;
    }

    public function redirect(TokenInterface $token, Account $account)
    {
        $provider = ($account->getProviderid() instanceof Provider) ? $account->getProviderid() : null;

        return null !== $provider
            && !empty($provider->getLoginurl())
            && $this->readNumber($token, $account)
            && (
                ACCOUNT_DISABLED === $provider->getAutologin()
                || $account->isDisableClientPasswordAccess()
            );
    }

    public function usePasswordInExtension(TokenInterface $token, Account $account)
    {
        return
            $this->updateClient($token, $account)
            || $this->updateClientV3($token, $account)
            || ($this->autologin($token, $account) && $account->getProviderid()->canAutologinWithExtension($this->getBusinessUser($token)))
            || ($this->updateItinerary($token, $account) && in_array($account->getProviderid()->getCancheckconfirmation(), [CAN_CHECK_CONFIRMATION_YES_EXTENSION, CAN_CHECK_CONFIRMATION_YES_EXTENSION_AND_SERVER]))
        ;
    }

    protected function getAttributes()
    {
        return [
            'READ_PASSWORD' => [$this, 'readPassword'],
            'READ_NUMBER' => [$this, 'readNumber'],
            'READ_BALANCE' => [$this, 'readBalance'],
            'READ_EXTPROP' => [$this, 'readExtProperties'],
            'READ_HISTORY' => [$this, 'readHistory'],
            'READ_TRANSACTIONS' => [$this, 'readTransactions'],
            'EDIT' => [$this, 'edit'],
            'SAVE' => [$this, 'save'],
            'DELETE' => [$this, 'delete'],
            'AUTOLOGIN' => [$this, 'autologin'],
            'AUTOLOGIN_WITH_EXTENSION' => [$this, 'autologinWithExtension'],
            'UPDATE' => [$this, 'update'],
            'UPDATE_GROUP' => [$this, 'updateGroup'],
            'UPDATE_ITINERARY' => [$this, 'updateItinerary'],
            'UPDATE_CLIENT' => [$this, 'updateClient'],
            'UPDATE_CLIENT_V3' => [$this, 'updateClientV3'],
            'REDIRECT' => [$this, 'redirect'],
            'USE_PASSWORD_IN_EXTENSION' => [$this, "usePasswordInExtension"],
        ];
    }

    protected function getClass()
    {
        return '\\AwardWallet\\MainBundle\\Entity\\Account';
    }

    private function accountInPasswordVault(TokenInterface $token, Account $account)
    {
        $pvRep = $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Passwordvault::class);

        $result = $pvRep->hasAccessScalar($account->getAccountid(), Utils::getImpersonator($token));

        if (!$result) {
            $this->siteVoter->setIsImpersonationSandboxEscaped(true);
        }

        return $result;
    }

    private function impersonatorHasAccess(TokenInterface $token, Account $account)
    {
        return !Utils::tokenHasRole($token, "ROLE_IMPERSONATED") || $this->accountInPasswordVault($token, $account);
    }

    private function fullRights(TokenInterface $token, Account $account, $rights = [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY])
    {
        $user = $this->getBusinessUser($token);

        if (empty($user)) {
            return false;
        }

        if ($account->getState() === ACCOUNT_IGNORED) {
            return false;
        }

        if (
            ($provider = $account->getProviderid())
            && !$provider->userHasAccess($user)
        ) {
            return false;
        }

        if ($user->getId() === $account->getUser()->getId()) {
            return true;
        }

        /** @var Collection<int, Useragent> $useragent */
        $useragents = $account->getUseragentByUser($user);

        if (!sizeof($useragents)) {
            return false;
        }
        /** @var Useragent $useragent */
        $useragent = $useragents->first();
        $reverseUserAgent = $useragent->getClientid()->getConnectionWith($user);

        if ($this->isBusiness() && !$this->container->get(BusinessVoter::class)->businessAccounts($token)) {
            return false;
        }

        return in_array($useragent->getAccesslevel(), $rights) && $reverseUserAgent && $reverseUserAgent->isApproved();
    }

    private function canUseBrowserExtensionForAutologin(TokenInterface $token, Account $account): bool
    {
        $provider = $account->getProviderid();

        return null !== $provider
            && $this->readPassword($token, $account)
            && $this->impersonatorHasAccess($token, $account)
            && !$account->isDisableExtension()
            && !$account->isDisableClientPasswordAccess()
        ;
    }

    private function canUseBrowserExtensionForUpdate(TokenInterface $token, Account $account): bool
    {
        $provider = $account->getProviderid();

        return null !== $provider
            && $this->readPassword($token, $account)
            && $this->impersonatorHasAccess($token, $account)
            && !$account->isDisableExtension()
        ;
    }

    private function canUpdateInClientV3(TokenInterface $token, Account $account)
    {
        $provider = $account->getProviderid();

        return
            $this->canUseBrowserExtensionForUpdate($token, $account)
            && $this->providerVoter->canCheckByBrowserExtV3($token, $provider)
        ;
    }

    private function canUpdateInClient(TokenInterface $token, Account $account)
    {
        $provider = $account->getProviderid();

        return
            $this->canUseBrowserExtensionForUpdate($token, $account)
            && $this->sessionVoter->canCheckByBrowserExt($token, $provider)
        ;
    }

    private function canAutologinWithExtension(TokenInterface $token, Account $account): bool
    {
        return
            $this->canUseBrowserExtensionForAutologin($token, $account)
            && $account->getProviderid()->canAutologinWithExtension($this->getBusinessUser($token));
    }
}
