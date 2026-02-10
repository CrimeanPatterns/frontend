<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Provider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProviderVoter extends AbstractVoter
{
    private SiteVoter $siteVoter;

    public function __construct(ContainerInterface $container, SiteVoter $siteVoter)
    {
        parent::__construct($container);

        $this->siteVoter = $siteVoter;
    }

    public function add(TokenInterface $token, Provider $provider)
    {
        $user = $this->getBusinessUser($token);

        if (!$user) {
            return false;
        }

        if ($this->isBusiness() && !$this->container->get(BusinessVoter::class)->businessAccounts($token)) {
            return false;
        }

        $beta = $user->getBetaapproved();
        $staff = $user->hasRole('ROLE_STAFF');
        $state = $provider->getState();

        if (
            ($state > 0)
            || is_null($state)
            || ($state === PROVIDER_RETAIL)
            || ($beta && $state == PROVIDER_IN_BETA)
            || ($staff && $state == PROVIDER_TEST)
        ) {
            return true;
        }

        return false;
    }

    public function canCheckByBrowserExtV3(TokenInterface $token, Provider $provider)
    {
        return
            $this->canCheckByDesktopExtV3($token, $provider)
            || $this->canCheckByMobileExtV3($token, $provider);
    }

    protected function getAttributes()
    {
        return [
            'ADD' => [$this, 'add'],
            'CAN_CHECK_BY_BROWSEREXT_V3' => [$this, 'canCheckByBrowserExtV3'],
        ];
    }

    protected function getClass()
    {
        return '\\AwardWallet\\MainBundle\\Entity\\Provider';
    }

    private function canCheckByMobileExtV3(TokenInterface $token, Provider $provider)
    {
        return
            $provider->isExtensionV3ParserEnabled()
            && $this->siteVoter->isMobile()
            && $this->siteVoter->isMobileApp();
    }

    private function canCheckByDesktopExtV3(TokenInterface $token, Provider $provider)
    {
        $requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE);

        if (!($requestStack && $requestStack->getCurrentRequest())) {
            return false;
        }

        $request = $this->container->get('request_stack')->getMasterRequest();

        return
            $provider->isExtensionV3ParserEnabled()
            && ($request->cookies->get('SB_V3') !== 'false')
            && !$this->siteVoter->isMobile();
    }
}
