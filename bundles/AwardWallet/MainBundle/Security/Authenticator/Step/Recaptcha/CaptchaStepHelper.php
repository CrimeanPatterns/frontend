<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\Recaptcha;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\EntryPoint\EntryPointUtils;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\ErrorStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\RequiredStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepInterface;
use AwardWallet\MainBundle\Security\Captcha\Provider\CaptchaProviderInterface;
use AwardWallet\MainBundle\Security\SiegeModeDetector;
use Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CaptchaStepHelper
{
    public const WANT_RECAPTCHA_HEADER = 'aw-want-recaptcha';

    private const CLIENT_RESPONSE_TIMEOUT = 60 * 15;
    protected LoggerInterface $logger;
    protected bool $recaptchaEnabled;
    protected SiegeModeDetector $siegeModeDetector;
    private ClockInterface $clock;

    public function __construct(
        LoggerInterface $securityLogger,
        bool $recaptchaEnabled,
        SiegeModeDetector $siegeModeDetector,
        ClockInterface $clock
    ) {
        $this->logger = $securityLogger;
        $this->recaptchaEnabled = $recaptchaEnabled;
        $this->siegeModeDetector = $siegeModeDetector;
        $this->clock = $clock;
    }

    public function onSuccess(StepInterface $step, Request $request, TokenInterface $token, $providerKey): void
    {
        $this->clearStored($step, $request);
    }

    public function supports(StepInterface $step, Credentials $credentials): bool
    {
        $request = $credentials->getRequest();

        if (!$this->recaptchaEnabled && !$request->headers->has(self::WANT_RECAPTCHA_HEADER)) {
            $this->logger->info("Captcha is disabled by flag, abstaining from check", self::getLogContext($step, $credentials));

            return false;
        }

        return
            $this->siegeModeDetector->isUnderSiege()
            || $request->headers->has(self::WANT_RECAPTCHA_HEADER);
    }

    public function doCheck(StepInterface $step, Credentials $credentials, CaptchaProviderInterface $captchaProvider): void
    {
        $request = $credentials->getRequest();
        $stepData = $credentials->getStepData();
        $session = $request->getSession();
        $presentedRecaptcha = $stepData->getRecaptcha();
        $presentedIp = $request->getClientIp();
        $presentedLogin = $stepData->getLogin();

        if (
            $session->has($step->getId())
            && ($unpacked = $this->unpackData($session->get($step->getId())))
        ) {
            [$_, $storedTime, $storedIp, $storedLogin] = $unpacked;

            if ($this->clock->current()->getAsSecondsInt() - $storedTime > self::CLIENT_RESPONSE_TIMEOUT) {
                $this->logger->info("Captcha stored check failed, response expired", self::getLogContext($step, $credentials));
                $this->clearStored($step, $request);
                $this->throwRecaptchaRequired($step, $captchaProvider);
            }

            if ($presentedLogin !== $storedLogin) {
                $this->logger->info("Captcha stored check failed, login differs from presented", self::getLogContext($step, $credentials));
                $this->clearStored($step, $request);
                $this->throwRecaptchaRequired($step, $captchaProvider);
            }

            if ($presentedIp !== $storedIp) {
                $this->logger->warning("Captcha stored check failed, ip differs from presented", self::getLogContext($step, $credentials));
                $this->clearStored($step, $request);
                $this->throwRecaptchaRequired($step, $captchaProvider);
            }

            $this->logger->info("Captcha stored check succeeded", self::getLogContext($step, $credentials));
        } else {
            if (!(
                \is_string($presentedRecaptcha)
                && StringUtils::isNotEmpty($presentedRecaptcha)
            )) {
                $this->logger->info("Captcha required", self::getLogContext($step, $credentials));
                $this->throwRecaptchaRequired($step, $captchaProvider);
            }

            if ($captchaProvider->getValidator()->validate($presentedRecaptcha, $presentedIp, false)) {
                $this->logger->info("Captcha online check succeeded", self::getLogContext($step, $credentials));
                $session->set($step->getId(), $this->packData($presentedRecaptcha, $presentedIp, $presentedLogin));
            } else {
                $this->logger->warning("Captcha online check failed", self::getLogContext($step, $credentials));
                $this->throwRecaptchaError($step, $captchaProvider);
            }
        }
    }

    protected static function getLogContext(StepInterface $step, Credentials $credentials, array $mixin = []): array
    {
        return EntryPointUtils::getLogContext($credentials, \array_merge(
            $mixin,
            ['auth_step' => $step->getId()]
        ));
    }

    protected function throwErrorException(StepInterface $step, string $message = "", $data = null, int $code = 0, ?\Throwable $previous = null): void
    {
        throw new ErrorStepAuthenticationException($step, $data, $message, $code, $previous);
    }

    protected function throwRequiredException(StepInterface $step, string $message = "", $data = null, int $code = 0, ?\Throwable $previous = null): void
    {
        throw new RequiredStepAuthenticationException($step, $data, $message, $code, $previous);
    }

    private function packData(string $clientRecaptcha, ?string $ip, ?string $login): array
    {
        return [
            'client_recaptcha_secret' => $clientRecaptcha,
            'client_recaptcha_time' => $this->clock->current()->getAsSecondsInt(),
            'client_recaptcha_ip' => $ip,
            'client_recaptcha_login' => $login,
        ];
    }

    private function unpackData($data): array
    {
        if (!\is_array($data)) {
            return [];
        }

        $isValid =
            it([
                'client_recaptcha_secret',
                'client_recaptcha_time',
                'client_recaptcha_ip',
                'client_recaptcha_login',
            ])
            ->all(function (string $key) use ($data) {
                return \array_key_exists($key, $data);
            });

        if ($isValid) {
            return [
                $data['client_recaptcha_secret'],
                $data['client_recaptcha_time'],
                $data['client_recaptcha_ip'],
                $data['client_recaptcha_login'],
            ];
        }

        return [];
    }

    private function throwRecaptchaError(StepInterface $step, CaptchaProviderInterface $captchaProvider)
    {
        $this->throwErrorException($step, "invalid_captcha", $captchaProvider);
    }

    private function throwRecaptchaRequired(StepInterface $step, CaptchaProviderInterface $captchaProvider)
    {
        $this->throwRequiredException($step, "invalid_captcha", $captchaProvider);
    }

    private function clearStored(StepInterface $step, Request $request): void
    {
        $request->getSession()->remove($step->getId());
    }
}
