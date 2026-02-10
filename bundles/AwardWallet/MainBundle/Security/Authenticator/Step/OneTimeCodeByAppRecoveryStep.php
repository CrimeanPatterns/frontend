<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OneTimeCodeByAppRecoveryStep extends AbstractStep
{
    public const ID = 'one_time_code_app_recovery';
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

    private PasswordDecryptor $passwordDecryptor;

    public function __construct(
        TwoFactorAuthenticationService $twoFactorAuthenticationService,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        PasswordDecryptor $passwordDecryptor
    ) {
        $this->twoFactorAuthenticationService = $twoFactorAuthenticationService;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->passwordDecryptor = $passwordDecryptor;
    }

    protected function supports(Credentials $credentials): bool
    {
        $user = $credentials->getUser();
        $otcRecoveryIsNeeded =
            $user->twoFactorAllowed()
            && $user->enabled2Factor()
            && StringUtils::isNotEmpty($credentials->getStepData()->getOtcRecoveryCode());

        if ($otcRecoveryIsNeeded) {
            $this->logger->info('2factor protection by app is enabled and recovery code is provided', $this->getLogContext($credentials));

            return true;
        }

        $this->logger->info('2factor protection by app is disabled or recovery code is not provided or empty', $this->getLogContext($credentials));

        return false;
    }

    protected function doCheck(Credentials $credentials): void
    {
        $user = $credentials->getUser();
        $providedCode = $credentials->getStepData()->getOtcRecoveryCode();

        $recoveryCode = $this->passwordDecryptor->decrypt($user->getGoogleAuthRecoveryCode());

        if (!hash_equals($recoveryCode, $providedCode)) {
            $this->logger->warning('Recovery code is invalid', $this->getLogContext($credentials));
            $this->throwErrorException($this->translator->trans(/** @Desc("The presented recovery code is invalid") */
                'error.auth.two-factor.invalid-recovery-code'));
        }

        $this->logger->info("Recovery code is valid, disabling otc by recovery code", $this->getLogContext($credentials));
        $this->twoFactorAuthenticationService->cancelTwoFactor($user);
    }
}
