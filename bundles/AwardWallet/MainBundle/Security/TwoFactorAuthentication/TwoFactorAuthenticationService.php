<?php

namespace AwardWallet\MainBundle\Security\TwoFactorAuthentication;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\TwoFactorAuth;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Security\SessionListener;
use AwardWallet\MainBundle\Service\TwoFactorAuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Google\Authenticator\GoogleAuthenticator;
use RandomLib\Factory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TwoFactorAuthenticationService
{
    public const RECOVERY_KEY_CHARSET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    public const LOOK_ALIKE_CHARSET = '1ilI0oO';
    public const SESSION_CHECKPOINT_PREFIX = 'two_factor_auth_checkpoint';
    public const BYPASS_EMAIL_OTC_GROUP = "Bypass security code";

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Memcached
     */
    private $cache;

    /**
     * container here to avoid cyclic dependency from aw.email.mailer.
     *
     * @var ContainerInterface
     */
    private $container;

    private $localPasswordsKey;
    private $localPasswordsKeyOld;

    /**
     * @var SessionInterface
     */
    private $session;

    private PasswordEncryptor $passwordEncryptor;
    private SessionListener $sessionListener;

    private TwoFactorAuthChecker $twoFactorAuthChecker;

    public function __construct(
        \Memcached $cache,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        $localPasswordsKey,
        $localPasswordsKeyOld,
        SessionInterface $session,
        PasswordEncryptor $passwordEncryptor,
        SessionListener $sessionListener,
        TwoFactorAuthChecker $twoFactorAuthChecker
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->container = $container;
        $this->localPasswordsKey = $localPasswordsKey;
        $this->localPasswordsKeyOld = $localPasswordsKeyOld;
        $this->session = $session;
        $this->passwordEncryptor = $passwordEncryptor;
        $this->sessionListener = $sessionListener;
        $this->twoFactorAuthChecker = $twoFactorAuthChecker;
    }

    public function dontAskQuestions(Usr $user)
    {
        $user->setLastlogondatetime(clone $user->getChangePasswordDate());
        $user->getLastlogondatetime()->modify("+1 second");
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function addAuthKeyCookie(Request $request, Response $response, Usr $user)
    {
        $authKey = $this->getAuthKey($request);

        $authKey['u' . $user->getUserid()] = time();

        if (!isset($authKey['Noise'])) {
            $authKey["Noise"] = StringHandler::getRandomCode(20);
        }

        $code = base64_encode(AESEncode(json_encode($authKey), $this->localPasswordsKey));
        $response->headers->setCookie(AwCookieFactory::createLax("AuthKey", $code, time() + DateTimeUtils::SECONDS_PER_DAY * 365 * 10, "/login_check", null, $this->container->getParameter("requires_channel") == 'https', true));
        $response->headers->setCookie(AwCookieFactory::createLax("AuthKey", $code, time() + DateTimeUtils::SECONDS_PER_DAY * 365 * 10, "/m/api/login_check", null, $this->container->getParameter("requires_channel") == 'https', true));
    }

    /**
     * Generates two-factor recovery key: gYnt-7eLp-g75E-M69V-wSHq-wUqa-72mP-YSMT.
     *
     * @return string|null
     * @throws \Exception
     * @throws \RuntimeException
     */
    public function generateRecoveryCode()
    {
        $set = str_replace(str_split(self::LOOK_ALIKE_CHARSET), '', self::RECOVERY_KEY_CHARSET);
        $key = null;

        for ($i = 1; $i <= 3; $i++) {
            try {
                $generator = (new Factory())->getMediumStrengthGenerator();
                $key = implode('-', str_split($generator->generateString(32, $set), 4));

                break;
            } catch (\RuntimeException $e) {
                if (3 == $i) {
                    throw $e;
                }

                continue;
            }
        }

        return $key;
    }

    public function generateSecret()
    {
        return $this->getAuthenticator()->generateSecret();
    }

    public function generateOtpImage(Usr $user, $secret, $host, $issuer, $imageFormat)
    {
        $otpUrl = $this->getAuthenticator()->getOtpUrl($issuer, $user->getLogin(), $host, $secret);
        $qrCodeGenerator = new QrCode($otpUrl);
        $qrCodeGenerator->setSize(200);

        return $qrCodeGenerator->get($imageFormat);
    }

    /**
     * @return string
     * @throws TwoFactorAuthenticationException
     */
    public function storeCheckpoint(Usr $user, $secret, $code)
    {
        $this->twoFactorShouldBeDisabled($user);

        $code = preg_replace('/[^0-9]/', '', substr($code, 0, 20));

        if (null === $secret || null === $code || !$this->checkCode($secret, $code)) {
            throw new TwoFactorAuthenticationException($this->translator->trans(/** @Desc("Incorrect code. Please make sure you typed the code correctly and that the times on your devices are synchronized.") */ 'error.auth.two-factor.invalid-setup-code'));
        }

        try {
            $recoveryCode = $this->generateRecoveryCode();

            if (null === $recoveryCode) {
                throw new \RuntimeException();
            }
        } catch (\RuntimeException $e) {
            throw new TwoFactorAuthenticationException($this->translator->trans('error.auth.two-factor.invalid-setup-code'));
        }

        $this->cache->set($this->getCacheKey($user, self::SESSION_CHECKPOINT_PREFIX . '_' . $secret), [$secret, $recoveryCode], SECONDS_PER_HOUR);

        return $recoveryCode;
    }

    public function checkCode($secret, $code)
    {
        return $this->getAuthenticator()->checkCode($secret, $code);
    }

    /**
     * @throws TwoFactorAuthenticationException
     */
    public function saveTwoFactorCredentials(Usr $user, $secret)
    {
        $this->twoFactorShouldBeDisabled($user);

        [$_, $recoveryCode] = $this->loadCheckpoint($user, $secret);
        $user->setGoogleAuthSecret($this->passwordEncryptor->encrypt($secret));
        $user->setGoogleAuthRecoveryCode($this->passwordEncryptor->encrypt($recoveryCode));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->sessionListener->invalidateUserSessionsButCurrent($user->getId(), $this->session->getId());

        // refs #16074
        $this->twoFactorAuthChecker->resetCache($user);

        $this->notify($user, false);
    }

    public function cancelTwoFactor(Usr $user)
    {
        $this->doCancelTwoFactor($user);
        $this->notify($user, true);
    }

    public function isTwoFactorAuthSet(Usr $user)
    {
        return $user->enabled2Factor();
    }

    public function getAuthenticator()
    {
        return new GoogleAuthenticator();
    }

    /**
     * Loads checkpoint data from session.
     *
     * @return array[secret, recoveryCode] saved checkpoint tuple
     * @throws TwoFactorAuthenticationException
     */
    private function loadCheckpoint(Usr $usr, $secret)
    {
        $checkpoint = $this->cache->get($this->getCacheKey($usr, self::SESSION_CHECKPOINT_PREFIX . '_' . $secret));

        if (false === $checkpoint) {
            throw new TwoFactorAuthenticationException($this->translator->trans('error.auth.two-factor.invalid-setup-code'));
        }

        return $checkpoint;
    }

    private function doCancelTwoFactor(Usr $user)
    {
        $user->setGoogleAuthSecret(null);
        $user->setGoogleAuthRecoveryCode(null);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // refs #16074
        $this->twoFactorAuthChecker->resetCache($user);
    }

    private function notify(Usr $user, $isDisabled)
    {
        $template = new TwoFactorAuth($user);
        $template->disabled = $isDisabled;

        $mailer = $this->getMailer();
        $message = $mailer->getMessageByTemplate($template);
        $mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
    }

    private function twoFactorShouldBeDisabled(Usr $user)
    {
        if ($this->isTwoFactorAuthSet($user)) {
            throw new TwoFactorAuthenticationException($this->translator->trans(/** @Desc("AwardWallet two-factor authentication already enabled") */ 'error.auth.two-factor.already-turned-on'));
        }
    }

    private function getAuthKey(Request $request)
    {
        if ($request->attributes->has('AuthKey')) {
            return $request->attributes->get('AuthKey');
        }

        $authKey = $request->cookies->get("AuthKey");

        if (!empty($authKey)) {
            $decoded = @json_decode(@AESDecode(@base64_decode($authKey), $this->localPasswordsKey), true);

            if (empty($decoded)) {
                $decoded = @json_decode(@AESDecode(@base64_decode($authKey), "%local_passwords_key%"), true);
            }

            if (empty($decoded)) {
                $decoded = @json_decode(@AESDecode(@base64_decode($authKey), $this->localPasswordsKeyOld), true);
            }
            $authKey = $decoded;
        }

        // convert v1 format to v2
        if (!empty($authKey['UserID'])) {
            $authKey = [
                "u" . $authKey['UserID'] => $authKey['Time'],
                'Noise' => $authKey['Noise'],
            ];
        }

        if (empty($authKey)) {
            $authKey = [];
        }
        $request->attributes->set('AuthKey', $authKey);

        return $authKey;
    }

    private function getMailer()
    {
        return $this->container->get('aw.email.mailer');
    }

    private function getCacheKey(Usr $usr, $prefix)
    {
        return $prefix . '_' . $usr->getUserid();
    }
}
