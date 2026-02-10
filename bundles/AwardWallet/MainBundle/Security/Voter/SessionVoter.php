<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SessionVoter extends AbstractVoter
{
    /**
     * @var SiteVoter
     */
    private $siteVoter;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;

    public function __construct(ContainerInterface $container, SiteVoter $siteVoter, ApiVersioningService $apiVersioning)
    {
        parent::__construct($container);

        $this->siteVoter = $siteVoter;
        $this->apiVersioning = $apiVersioning;
    }

    public function isFirstTime(TokenInterface $token)
    {
        $user = $this->getBusinessUser($token);

        return !empty($user) && $user->getLogoncount() == 0;
    }

    public function canCheckByBrowserExt(?TokenInterface $token = null, ?Provider $provider = null)
    {
        return
            $this->canCheckByDesktopExt($token, $provider)
            || $this->canCheckByMobileExt($token, $provider);
    }

    public function canCheckByMobileExt(?TokenInterface $token = null, ?Provider $provider = null)
    {
        return
            $provider && $provider->isCheckinmobilebrowser()
            && $this->siteVoter->isMobile()
            && $this->siteVoter->isMobileApp();
    }

    public function canCheckByDesktopExt(?TokenInterface $token = null, ?Provider $provider = null)
    {
        if (!$provider) {
            return false;
        }

        if (!(
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getCurrentRequest())
        )) {
            return false;
        }

        $request = $this->container->get('request_stack')->getMasterRequest();

        return
            in_array($provider->getCheckinbrowser(), [CHECK_IN_MIXED, CHECK_IN_CLIENT])
            && ($request->cookies->get('SB') !== 'false')
            && !$this->siteVoter->isMobile();
    }

    public function canCheckFirstTime(TokenInterface $token, ?Provider $provider = null)
    {
        return $this->isFirstTime($token) && $this->canCheckByBrowserExt($token, $provider);
    }

    protected function getAttributes()
    {
        return [
            'SESSION_CAN_CHECK_FIRST_TIME' => [$this, 'canCheckFirstTime'],
        ];
    }

    protected function getClass()
    {
        return null;
    }
}
