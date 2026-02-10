<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Security\PasswordChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordReauthenticator implements ReauthenticatorInterface
{
    public const INPUT_TYPE = 'password';
    private const CONTEXT = 'password';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PasswordChecker
     */
    private $passwordChecker;
    private LoggerInterface $logger;
    private BinaryLoggerFactory $check;

    public function __construct(TranslatorInterface $translator, PasswordChecker $passwordChecker, LoggerInterface $logger)
    {
        $this->translator = $translator;
        $this->passwordChecker = $passwordChecker;
        $this->logger = (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('password: ')
            ->pushContext([Context::SERVER_MODULE_KEY => 'password_reauthenticator']);
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo();
    }

    public function start(AuthenticatedUser $authUser, string $action, Environment $environment): ReauthResponse
    {
        $this->checkSupport($authUser);
        $user = $authUser->getEntity();

        return ReauthResponse::ask(
            $this->translator->trans(/** @Desc("Confirm your identity") */ 'confirm-identity', [], 'messages', $user->getLanguage()),
            $this->translator->trans(/** @Desc("Please provide your AwardWallet password:") */ 'provide-aw-password', [], 'messages', $user->getLanguage()),
            self::INPUT_TYPE,
            self::CONTEXT
        );
    }

    public function verify(AuthenticatedUser $authUser, ReauthRequest $request, Environment $environment): ResultResponse
    {
        $this->checkSupport($authUser);

        if ($request->getContext() !== self::CONTEXT) {
            throw new \InvalidArgumentException(sprintf('Wrong context "%s"', $request->getContext()));
        }

        if ($request->haveIntent()) {
            throw new \InvalidArgumentException(sprintf('Unsupported intent "%s"', $request->getIntent()));
        }

        $user = $authUser->getEntity();

        try {
            $this->passwordChecker->checkPasswordUnsafe($user, $request->getInput());
            $passwordIsValid = true;

            return ResultResponse::create(true);
        } catch (BadCredentialsException $e) {
            $passwordIsValid = false;

            return ResultResponse::create(
                false,
                $this->translator->trans('invalid.password', [], 'validators', $user->getLanguage())
            );
        } finally {
            $this->check->that('password')->is('valid')->negativeToWarning()
            ->on($passwordIsValid);
        }
    }

    public function reset(string $action)
    {
    }

    public function support(AuthenticatedUser $authUser): bool
    {
        return
            $this->check->that('user')->does('have password set')
            ->on(!empty($authUser->getEntity()->getPassword()));
    }

    private function checkSupport(AuthenticatedUser $authUser)
    {
        if (!$this->support($authUser)) {
            throw new \InvalidArgumentException('"Password" method of authentication is not available');
        }
    }
}
