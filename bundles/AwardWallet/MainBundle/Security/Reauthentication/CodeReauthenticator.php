<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CodeReauthenticator implements ReauthenticatorInterface
{
    public const INPUT_TYPE = 'text';
    public const INTENT_RESEND = 'resend';

    private const CONTEXT = 'code';
    private const SESSION_KEY = 'reauth/<action>/security-code';
    private const SESSION_REAUTH_CODE_KEY = 'code';
    private const SESSION_REAUTH_CODE_GEN_KEY = 'generated';
    private const SESSION_REAUTH_CODE_SENT_TS_KEY = 'sent';
    private const CODE_TTL = 5 * 60; // 5 min
    private const CODE_DELAY_SENDING = 30; // sec

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CodeSenderInterface[]
     */
    private $senders;

    /**
     * @var TimeCommunicator
     */
    private $timeCommunicator;
    private LoggerInterface $logger;
    private BinaryLoggerFactory $check;

    public function __construct(
        SessionInterface $session,
        TranslatorInterface $translator,
        iterable $senders,
        TimeCommunicator $timeCommunicator,
        LoggerInterface $logger
    ) {
        $this->session = $session;
        $this->translator = $translator;
        $this->senders = $senders;
        $this->timeCommunicator = $timeCommunicator;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('code: ')
            ->pushContext([Context::SERVER_MODULE_KEY => 'code_reauthenticator']);
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo();
    }

    public function start(AuthenticatedUser $authUser, string $action, Environment $environment): ReauthResponse
    {
        $this->checkSupport($authUser);

        if (
            $this->check->that('code')->isNot('valid')
            ->on(!$this->isValidCode($action))
        ) {
            $this->sendCode($authUser, $action, $environment, true);
        }

        return $this->ask($authUser, $action);
    }

    public function verify(AuthenticatedUser $authUser, ReauthRequest $request, Environment $environment): ResultResponse
    {
        $checkThat = $this->check;
        $this->checkSupport($authUser);

        if ($request->getContext() !== self::CONTEXT) {
            throw new \InvalidArgumentException(sprintf('Wrong context "%s"', $request->getContext()));
        }

        if ($request->haveIntent() && $request->getIntent() !== self::INTENT_RESEND) {
            throw new \InvalidArgumentException(sprintf('Unsupported intent "%s"', $request->getIntent()));
        }

        $action = $request->getAction();

        if (
            $checkThat('request')->has('intent')
            ->on($request->haveIntent())
        ) {
            $error = $this->sendCode($authUser, $action, $environment, !$this->isValidCode($action));

            return ResultResponse::create(is_null($error), $error);
        } else {
            if (
                $checkThat('provided code')->isNot('valid')->positiveToWarning()
                ->on(!$this->isValidCode($action, $request->getInput()))
            ) {
                $lang = $authUser->getEntity()->getLanguage();

                if (
                    $checkThat('code')->is('expired')
                    ->on($this->isExpiredCode($action))
                ) {
                    $this->sendCode($authUser, $action, $environment, true);

                    return ResultResponse::create(
                        false,
                        $this->translator->trans(
                            /** @Desc("The code that you provided has expired.") */
                            'expired-code',
                            [],
                            'validators',
                            $lang
                        )
                    );
                }

                return ResultResponse::create(
                    false,
                    $this->translator->trans(
                        /** @Desc("The code that you provided is invalid.") */
                        'invalid-code',
                        [],
                        'validators',
                        $authUser->getEntity()->getLanguage()
                    )
                );
            }

            return ResultResponse::create(true);
        }
    }

    public function reset(string $action)
    {
        $this->session->remove($this->getCodeKey($action));
        $this->session->remove($this->getCodeGeneratedKey($action));
        $this->session->remove($this->getCodeSentTsKey($action));

        foreach ($this->senders as $sender) {
            $this->session->remove($this->getCodeSentToKey($action, $sender));
        }
    }

    public function support(AuthenticatedUser $authUser): bool
    {
        return true;
    }

    private function sendCode(AuthenticatedUser $authUser, string $action, Environment $environment, bool $generateCode): ?string
    {
        $checkThat = $this->check;
        $codeKey = $this->getCodeKey($action);
        $codeGenKey = $this->getCodeGeneratedKey($action);
        $codeSentTsKey = $this->getCodeSentTsKey($action);
        $currentTs = $this->timeCommunicator->getCurrentTime();

        if ($generateCode) {
            $this->logger->info('new code will be generated');
            $this->session->set($codeKey, $this->generateCode());
            $this->session->set($codeGenKey, $currentTs);
            $this->session->remove($codeSentTsKey);

            foreach ($this->senders as $sender) {
                $this->session->remove($this->getCodeSentToKey($action, $sender));
            }
        }

        if (
            $checkThat('session')->hasNot('code key')
            ->on(!$this->session->has($codeKey))
        ) {
            throw new \RuntimeException('The security code was not found');
        }

        if (
            !$generateCode
            && $checkThat('session')->has('code sent timestamp key')
                ->on($this->session->has($codeSentTsKey))
            && $checkThat('next attempt time')->hasNot('come')
                ->on(($nextAttemptTs = $this->session->get($codeSentTsKey, 0) + self::CODE_DELAY_SENDING) > $currentTs)
        ) {
            $left = $nextAttemptTs - $currentTs;

            return $this->translator->trans(
                /** @Desc("Please wait %left% second before resending the security code|Please wait %left% seconds before resending the security code") */
                'frequent-sending-code',
                [
                    '%count%' => $left,
                    '%left%' => $left,
                ],
                'validators',
                $authUser->getEntity()->getLanguage()
            );
        }

        $code = $this->session->get($codeKey);
        $sent = false;

        foreach ($this->senders as $sender) {
            $result = $sender->send($authUser, $code, $environment);
            $this->session->set($this->getCodeSentToKey($action, $sender), $result);
            $sent = $sent || $result->success;
        }

        if (!$sent) {
            throw new \RuntimeException('Security code notifications were not sent to the user');
        }

        $this->session->set($codeSentTsKey, $currentTs);

        return null;
    }

    private function generateCode(): string
    {
        return StringHandler::getRandomString(ord('0'), ord('9'), 6);
    }

    private function ask(AuthenticatedUser $authUser, string $action): ReauthResponse
    {
        $lang = $authUser->getEntity()->getLanguage();
        $sentTo = [];

        foreach ($this->senders as $sender) {
            /** @var SendReport $report */
            if (
                ($report = $this->session->get($this->getCodeSentToKey($action, $sender)))
                && $report->success
                && !empty($report->recepient)
            ) {
                $sentTo[] = $report->recepient;
            }
        }

        if (count($sentTo) === 0) {
            throw new \RuntimeException('Security code notifications were not sent to the user');
        }

        $or = sprintf(' %s ', $this->translator->trans('or', [], 'messages', $lang));

        if (count($sentTo) <= 2) {
            $devices = implode($or, $sentTo);
        } else {
            $devices = implode(', ', array_slice($sentTo, 0, -1))
                . $or . array_slice($sentTo, -1)[0];
        }
        $inputTitle = $this->translator->trans(
            /** @Desc("Please enter the code that was just sent to %devices%:") */
            'security-code-sent-to',
            [
                '%devices%' => $devices,
            ],
            'messages',
            $lang
        );

        return ReauthResponse::ask(
            $this->translator->trans('confirm-identity', [], 'messages', $lang),
            $inputTitle,
            self::INPUT_TYPE,
            self::CONTEXT
        )->withResendFeature();
    }

    private function getCodeKey(string $action): string
    {
        return $this->translateKey(self::SESSION_REAUTH_CODE_KEY, $action);
    }

    private function getCodeGeneratedKey(string $action): string
    {
        return $this->translateKey(self::SESSION_REAUTH_CODE_GEN_KEY, $action);
    }

    private function getCodeSentTsKey(string $action): string
    {
        return $this->translateKey(self::SESSION_REAUTH_CODE_SENT_TS_KEY, $action);
    }

    private function getCodeSentToKey(string $action, CodeSenderInterface $codeSender): string
    {
        $class = explode('\\', get_class($codeSender));
        $className = str_replace('CodeSender', '', array_pop($class));

        return $this->translateKey($className, $action);
    }

    private function translateKey(string $pattern, string $action)
    {
        return strtr(sprintf('%s/%s', self::SESSION_KEY, $pattern), ['<action>' => $action]);
    }

    private function isValidCode(string $action, ?string $code = null): bool
    {
        $checkThat = $this->check;
        $codeKey = $this->getCodeKey($action);
        $sent = false;

        foreach ($this->senders as $sender) {
            /** @var SendReport $report */
            $report = $this->session->get($this->getCodeSentToKey($action, $sender));
            $sent = $sent || ($report && $report->success);
        }

        return
            $checkThat('code')->was('sent')
                ->on($sent)
            && $checkThat('code')->isNot('expired')
                ->on(!$this->isExpiredCode($action))
            && (
                $checkThat('code')->is('null')
                    ->on(is_null($code))
                || $checkThat('code')->is('equal to code from session')
                    ->on($code === $this->session->get($codeKey))
            );
    }

    private function isExpiredCode(string $action): bool
    {
        $checkThat = $this->check;
        $codeKey = $this->getCodeKey($action);
        $codeGenKey = $this->getCodeGeneratedKey($action);

        return !(
            $checkThat('session')->has('code')
                ->on($this->session->has($codeKey))
            && $checkThat('session')->has('code gen date')
                ->on($this->session->has($codeGenKey))
            && $checkThat('code gen date')->haveNot('expired')
                ->on($this->session->get($codeGenKey, 0) + self::CODE_TTL > $this->timeCommunicator->getCurrentTime())
        );
    }

    private function checkSupport(AuthenticatedUser $authUser)
    {
        if (!$this->support($authUser)) {
            throw new \InvalidArgumentException('"Code" method of authentication is not available');
        }
    }
}
