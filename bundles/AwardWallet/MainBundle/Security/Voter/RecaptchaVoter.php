<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\AuthenticationEntryPointHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

class RecaptchaVoter extends AbstractVoter
{
    public const RECAPTCHA_WITHOUT_CHINA = 'RECAPTCHA_WITHOUT_CHINA';
    public const RECAPTCHA_WITH_CHINA = 'RECAPTCHA_WITH_CHINA';

    public function isValidRecaptcha(bool $checkChina)
    {
        if (!$this->container->getParameter('recaptcha_enabled')) {
            return null;
        }

        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getCurrentRequest())
        ) {
            [$recaptchaKey, $recaptchaValidator] = $this->getRecaptchaParams();
            $clientProvidedRecaptcha = $request->headers->get('X-RECAPTCHA');
            $result =
                StringUtils::isNotEmpty($clientProvidedRecaptcha)
                && $recaptchaValidator->validate($clientProvidedRecaptcha, $request->getClientIp(), $checkChina);

            if (false === $result) {
                $request->attributes->set(AuthenticationEntryPointHandler::REQUEST_ATTRIBUTE_REDIRECT_TO_LOGIN, false);
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->addListener(
                    KernelEvents::RESPONSE,
                    function (FilterResponseEvent $event) use ($recaptchaKey) {
                        if ($event->isMasterRequest()) {
                            $headers = $event->getResponse()->headers;
                            $headers->set('X-RECAPTCHA-FAILED', 'true');
                            $headers->set('X-RECAPTCHA-KEY', $recaptchaKey);
                            $headers->set('Content-type', 'application/json');
                            $event->getResponse()->setContent(\json_encode('RECAPTCHA failed'));
                        }
                    },
                    0
                );
            }

            return $result;
        }

        return null;
    }

    protected function getAttributes()
    {
        return [
            self::RECAPTCHA_WITH_CHINA => function () { return $this->isValidRecaptcha(true); },
            self::RECAPTCHA_WITHOUT_CHINA => function () { return $this->isValidRecaptcha(false); },
        ];
    }

    protected function getRecaptchaParams(): array
    {
        $isNativeMobileApp = $this->container->get(ApiVersioningService::class)->supports(MobileVersions::NATIVE_APP);
        $siteVoter = $this->container->get(SiteVoter::class);
        $isAndroidV2Key = lazy(function () use ($siteVoter, $isNativeMobileApp) {
            return
                $isNativeMobileApp
                && $siteVoter->isMobileAppAndroid(new AnonymousToken('runtime', 'runtime', []), null);
        });
        $isIosNotARobotKey = lazy(function () use ($siteVoter, $isNativeMobileApp) {
            return
                $isNativeMobileApp
                && $siteVoter->isMobileAppIos(new AnonymousToken('runtime', 'runtime', []), null);
        });

        if ($isAndroidV2Key()) {
            return [$this->container->getParameter('recaptcha_v2_android_site_key'), $this->container->get('aw.recaptcha_validator.v2_android')];
        } elseif ($isIosNotARobotKey()) {
            return [$this->container->getParameter('recaptcha_v2_not_a_robot_site_key'), $this->container->get('aw.recaptcha_validator.v2_not_a_robot')];
        } else {
            return [$this->container->getParameter('recaptcha_site_key'), $this->container->get('aw.recaptcha_validator')];
        }
    }
}
