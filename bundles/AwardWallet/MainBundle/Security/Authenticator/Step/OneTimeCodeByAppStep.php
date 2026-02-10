<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OneTimeCodeByAppStep extends AbstractStep
{
    public const ID = 'one_time_code_by_app';
    /**
     * @var TwoFactorAuthenticationService
     */
    protected $twoFactorAuthenticationService;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var \Memcached
     */
    protected $cache;

    private PasswordDecryptor $passwordDecryptor;

    public function __construct(
        TwoFactorAuthenticationService $twoFactorAuthenticationService,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        \Memcached $cache,
        PasswordDecryptor $passwordDecryptor
    ) {
        $this->twoFactorAuthenticationService = $twoFactorAuthenticationService;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->cache = $cache;
        $this->passwordDecryptor = $passwordDecryptor;
    }

    protected function supports(Credentials $credentials): bool
    {
        $user = $credentials->getUser();

        if ($user->twoFactorAllowed() && $user->enabled2Factor()) {
            $this->logger->info('2factor protection by app is enabled', $this->getLogContext($credentials));

            return true;
        }

        $this->logger->info('2factor protection by app is disabled', $this->getLogContext($credentials));

        return false;
    }

    protected function doCheck(Credentials $credentials): void
    {
        $providedCode = $credentials->getStepData()->getOtcAppCode();
        $user = $credentials->getUser();

        $authSecret = $this->passwordDecryptor->decrypt($user->getGoogleAuthSecret());
        $providedCode = trim(substr($providedCode, 0, 1000), "\r\n\t *");

        if ('' === $providedCode) {
            $this->logger->warning('App-OTC is empty', $this->getLogContext($credentials));
            $this->throwRequiredException($requiredMessage = $this->translator->trans(
                /** @Desc("One-time code required") */
                'error.auth.two-factor.code-required'
            ));
        }

        $lastSuccessCode = $this->cache->get('otc_success_user_' . $user->getUserid());

        if (false !== $lastSuccessCode && (int) $providedCode === $lastSuccessCode) {
            $this->logger->warning('Possible OTC replay attack on User: ' . $user->getLogin() . ', UserID: ' . $user->getUserid(), $this->getLogContext($credentials));
            $this->throwInvalidOTC();
        }

        $validCode = $this->twoFactorAuthenticationService->checkCode($authSecret, $providedCode);

        $this->logger->info("otc validation", ["code" => $providedCode, "valid" => $validCode, 'UserID' => $user->getUserid(), "IsStaff" => $user->hasRole('ROLE_STAFF')]);

        if (!$validCode) {
            $this->logger->warning('App-OTC is invalid', $this->getLogContext($credentials));
            $this->throwInvalidOTC();
        }

        // store success code
        $this->logger->info("App-OTC is valid", $this->getLogContext($credentials));
        $this->cache->set('otc_success_user_' . $user->getUserid(), (int) $providedCode, 30);
    }

    protected function throwInvalidOTC()
    {
        $invalidMessage = $this->translator->trans(/** @Desc("The presented one-time code is invalid") */ 'error.auth.two-factor.invalid-code');
        $this->throwErrorException($invalidMessage);
    }
}
