<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use Symfony\Component\HttpFoundation\Request;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class BrowserContextFactory
{
    public const MOBILE_APP_FRESH_INSTALL_REQUEST_ATTRIBUTE = '_mobile_app_fresh_install';

    public static function getContext(Request $request): array
    {
        return \array_merge(
            self::getMainContext($request),
            self::getMobileContext($request)
        );
    }

    protected static function getMainContext(Request $request): array
    {
        return [
            'client_ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('user_agent'),
            'is_mobile_browser' => UserAgentUtils::isMobileBrowser($request->headers->get('user_agent')),
        ];
    }

    protected static function getMobileContext(Request $request): array
    {
        return it(call(function () use ($request) {
            $isMobileApp =
                $request->headers->has(MobileHeaders::MOBILE_PLATFORM)
                && StringUtils::isNotEmpty($mobilePlatform = $request->headers->get(MobileHeaders::MOBILE_PLATFORM));

            yield 'is_mobile_app' => $isMobileApp;

            if (!$isMobileApp) {
                return;
            }

            yield 'mobile_platform' => $mobilePlatform;

            if (
                $request->headers->has(MobileHeaders::MOBILE_VERSION)
                && StringUtils::isNotEmpty($version = $request->headers->get(MobileHeaders::MOBILE_VERSION))
            ) {
                yield 'mobile_version' => $version;
            }

            if (
                $request->attributes->has(self::MOBILE_APP_FRESH_INSTALL_REQUEST_ATTRIBUTE)
                && \is_bool($freshInstall = $request->attributes->get(self::MOBILE_APP_FRESH_INSTALL_REQUEST_ATTRIBUTE))
            ) {
                yield 'is_mobile_app_fresh_install' => $freshInstall;
            }
        }))->toArrayWithKeys();
    }
}
