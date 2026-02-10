<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Reauthenticator
{
    private const AUTH_TTL = 5 * 60; // 5 min
    private const SESSION_KEY = 'reauth/<action>/base';
    private const SESSION_REAUTH = 'result';
    private const SESSION_IP = 'ip';

    /**
     * @var ReauthenticatorInterface[]
     */
    private $reauthenticators;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var AntiBruteforceLockerService
     */
    private $ipLocker;

    /**
     * @var AntiBruteforceLockerService
     */
    private $loginLocker;

    /**
     * @var TimeCommunicator
     */
    private $timeCommunicator;

    /**
     * @var LoggerInterface
     */
    private $logger;
    private BinaryLoggerFactory $check;

    public function __construct(
        iterable $reauthenticators,
        SessionInterface $session,
        AntiBruteforceLockerService $ipLocker,
        AntiBruteforceLockerService $loginLocker,
        TimeCommunicator $timeCommunicator,
        LoggerInterface $logger
    ) {
        $this->reauthenticators = $reauthenticators;
        $this->session = $session;
        $this->ipLocker = $ipLocker;
        $this->loginLocker = $loginLocker;
        $this->timeCommunicator = $timeCommunicator;
        $this->logger = (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('reauthenticator: ')
            ->pushContext([Context::SERVER_MODULE_KEY => 'reauthenticator'])
            ->withTypedContext();
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo();
    }

    public function start(AuthenticatedUser $authUser, string $action, Environment $environment): ReauthResponse
    {
        $this->logger->info('starting');

        if ($this->isReauthenticated($action, $environment->getIp())) {
            $response = ReauthResponse::authorized();
            $this->logger->info('start', [
                'action' => $action,
                'authUser' => $authUser,
                'response' => $response,
            ]);

            return $response;
        }

        try {
            $response = $this
                ->getSupportedReauthenticator($authUser, $action)
                ->start($authUser, $action, $environment);
            $this->logger->info('start result', [
                'action' => $action,
                'authUser' => $authUser,
                'response' => $response,
            ]);

            return $response;
        } catch (\InvalidArgumentException $e) {
            $this->logger->warn(sprintf('start fail: %s', $e->getMessage()), [
                'action' => $action,
                'authUser' => $authUser,
            ]);

            throw $e;
        }
    }

    public function verify(AuthenticatedUser $authUser, ReauthRequest $request, Environment $environment): ResultResponse
    {
        $this->logger->info('verifying request');
        $action = $request->getAction();

        if ($this->isReauthenticated($action, $environment->getIp())) {
            $response = ResultResponse::create(true);
            $this->logger->info('verification result', [
                'action' => $action,
                'authUser' => $authUser,
                'result' => $response,
            ]);

            return $response;
        }

        try {
            if ($request->haveIntent()) {
                return $this->verifyIntent($authUser, $request, $environment);
            } else {
                return $this->verifyInput($authUser, $request, $environment);
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->warn(sprintf('verify fail: %s', $e->getMessage()), array_merge([
                'action' => $action,
                'authUser' => $authUser,
            ], $request->haveIntent() ? ['intent' => $request->getIntent()] : []));

            throw $e;
        }
    }

    public function isReauthenticated(string $action, string $ip): bool
    {
        $reauthedKey = $this->getReauthKey($action);
        $reauthedIpKey = $this->getReauthIpKey($action);

        $result =
            $this->check->that('reauthed and reauthed ip keys')->are('exist in session')
                ->on($this->session->has($reauthedKey) && $this->session->has($reauthedIpKey))
            && $this->check->that('reauthed ip')->is('equal to current IP')
                ->on($this->session->get($reauthedIpKey) === $ip)
            && $this->check->that('reauthed key date')->haveNot('expired')
                ->on($this->session->get($reauthedKey, 0) + self::AUTH_TTL > $this->timeCommunicator->getCurrentTime());

        return $this->check->that("action {$action}")->is('reauthenticated')->on($result);
    }

    public function reset(string $action)
    {
        $this->logger->info('resetting state');
        $this->session->remove($this->getReauthKey($action));
        $this->session->remove($this->getReauthIpKey($action));

        foreach ($this->reauthenticators as $reauthenticator) {
            $reauthenticator->reset($action);
        }
    }

    private function verifyInput(AuthenticatedUser $authUser, ReauthRequest $request, Environment $environment): ResultResponse
    {
        $this->logger->info('verifying input');
        $action = $request->getAction();
        $user = $authUser->getEntity();
        $ip = $environment->getIp();

        if (
            $this->check->that('ip locker')->has('error')
                ->on(StringUtils::isNotEmpty($error = $this->ipLocker->checkForLockout($ip)))
            || $this->check->that('login locker')->has('error')
                ->on(StringUtils::isNotEmpty($error = $this->loginLocker->checkForLockout($user->getLogin())))
        ) {
            $response = ResultResponse::create(false, $error, false);
            $this->logger->info('input verification lockout', [
                'action' => $action,
                'authUser' => $authUser,
                'result' => $response,
            ]);

            return $response;
        }

        $response = $this
            ->getSupportedReauthenticator($authUser, $action)
            ->verify($authUser, $request, $environment);

        if ($response->success) {
            $this->session->set($this->getReauthKey($action), $this->timeCommunicator->getCurrentTime());
            $this->session->set($this->getReauthIpKey($action), $environment->getIp());
            $this->loginLocker->unlock($user->getLogin());
        }
        $this->logger->info('input verification result', [
            'action' => $action,
            'authUser' => $authUser,
            'result' => $response,
        ]);

        return $response;
    }

    private function verifyIntent(AuthenticatedUser $authUser, ReauthRequest $request, Environment $environment): ResultResponse
    {
        $this->logger->info('verifying intent');
        $action = $request->getAction();
        $response = $this
            ->getSupportedReauthenticator($authUser, $action)
            ->verify($authUser, $request, $environment);

        $this->logger->info('intent verification result', [
            'action' => $action,
            'authUser' => $authUser,
            'intent' => $request->getIntent(),
            'result' => $response,
        ]);

        return $response;
    }

    private function getSupportedReauthenticator(AuthenticatedUser $authUser, string $action): ReauthenticatorInterface
    {
        $this->logger->info('selecting supporting reauthenticator');

        foreach ($this->reauthenticators as $reauthenticator) {
            if (
                $this->check->that('reauthenticator ' . \get_class($reauthenticator))->does('support user')
                ->on($reauthenticator->support($authUser))
            ) {
                return $reauthenticator;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unable to authenticate for the "%s" action', $action));
    }

    private function getReauthKey(string $action): string
    {
        return $this->translateKey(self::SESSION_REAUTH, $action);
    }

    private function getReauthIpKey(string $action): string
    {
        return $this->translateKey(self::SESSION_IP, $action);
    }

    private function translateKey(string $pattern, string $action)
    {
        return strtr(sprintf('%s/%s', self::SESSION_KEY, $pattern), ['<action>' => $action]);
    }
}
