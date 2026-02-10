<?php

namespace AwardWallet\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;

abstract class EntryPointUtils
{
    public static function getLogContext(Credentials $credentials, array $mixin = []): array
    {
        $context = [
            'aw_server_module' => 'login_form_authenticator',
            'ip' => $credentials->getRequest()->getClientIp(),
        ];

        $failedStep = $credentials->getFailedStep();

        if ($failedStep !== null) {
            $context['failed_step'] = $failedStep;
        }

        if ($credentials->hasUser()) {
            $user = $credentials->getUser();
            $context['userid'] = $user->getUserid() ?? 0;
            $context['userlogin'] = $user->getLogin();
            $context['IsStaff'] = $user->hasRole('ROLE_STAFF');
        }

        if (
            \is_string($login = $credentials->getStepData()->getLogin())
            && StringUtils::isNotEmpty($login)
        ) {
            $context['userlogin'] = $login;
        }

        if ($mixin) {
            $context = array_merge($context, $mixin);
        }

        return $context;
    }
}
