<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\Strings\Strings;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class StateFactory
{
    private const SESSION_CSRF_KEY = 'oauth_csrf';
    private const CACHE_STATE_PREFIX = 'oauth_state_';

    /**
     * @var AwTokenStorage
     */
    private $tokenStorage;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var string
     */
    private $businessHost;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var string
     */
    private $host;
    private \Memcached $memcached;

    public function __construct(
        AwTokenStorage $tokenStorage,
        SerializerInterface $serializer,
        string $businessHost,
        string $host,
        LoggerInterface $logger,
        SessionInterface $session,
        ValidatorInterface $validator,
        \Memcached $memcached
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->serializer = $serializer;
        $this->businessHost = $businessHost;
        $this->logger = $logger;
        $this->session = $session;
        $this->validator = $validator;
        $this->host = preg_replace('#:\d+$#', '', $host);
        $this->memcached = $memcached;
    }

    public function createState(
        string $type,
        ?int $agentId,
        bool $mailboxAccess,
        bool $profileAccess,
        string $action,
        Request $request
    ): string {
        $sessionCsrf = bin2hex(random_bytes(10));
        $this->saveCsrfKey($request->getSession(), $sessionCsrf);
        $state = new State(
            $type,
            $this->tokenStorage->getBusinessUser() ? $this->tokenStorage->getBusinessUser()->getUserid() : null,
            $agentId,
            $request->query->has('mobile') ? 'mobile' : 'desktop',
            $request->getHost(),
            $mailboxAccess,
            $profileAccess,
            $action,
            $sessionCsrf,
            array_diff_key(array_filter($request->query->all(), fn ($value) => is_string($value) && preg_match('#^\w+$#ims', $value)), ["action" => false, "mailboxAccess" => false, "profileAccess" => false, "error" => false]),
            $request->query->get('rememberMe') === 'true'
        );
        $serializedState = $this->serializer->serialize($state, 'json');
        $this->logger->info("state created: $serializedState");

        // state is too long for url param, so we will save it to session
        $stateCode = bin2hex(random_bytes(20));
        // why so long?
        $this->memcached->set(self::CACHE_STATE_PREFIX . $stateCode, $serializedState, 86400);

        return $stateCode;
    }

    public function decodeState(string $stateCode): State
    {
        $this->logger->info("deserializing state: {$stateCode}");

        // html encoding from yahoo
        if (strpos($stateCode, '&quot;') !== false) {
            $stateCode = html_entity_decode($stateCode);
        }

        $serializedState = $this->memcached->get(self::CACHE_STATE_PREFIX . $stateCode);

        if ($serializedState === false) {
            throw new ExpiredStateException("no state found in cache for " . Strings::cutInMiddle($stateCode, 4));
        }

        /** @var State $state */
        try {
            $state = $this->serializer->deserialize($serializedState, State::class, 'json');
        } catch (RuntimeException $exception) {
            throw new InvalidStateException("failed to deserialize state, error: " . $exception->getMessage() . ", data: " . $stateCode);
        }

        $errors = $this->validator->validate($state);

        if (count($errors) > 0) {
            throw new InvalidStateException("invalid state: {$serializedState}, errors: " . (string) $errors);
        }

        if ($state->getHost() && $state->getHost() !== $this->businessHost && $state->getHost() !== $this->host) {
            throw new InvalidStateException("invalid host in state: {$serializedState}");
        }

        if (!self::isValidAction($state->getAction())) {
            throw new InvalidStateException("invalid action in state: {$serializedState}");
        }

        if (
            ($state->getAction() === OAuthAction::MAILBOX)
            && (
                !($user = $this->tokenStorage->getBusinessUser())
                || ($state->getUserId() !== $user->getUserid())
            )
        ) {
            throw new InvalidStateException("state from another user: {$stateCode}");
        }

        return $state;
    }

    public static function isValidAction(?string $action): bool
    {
        return in_array($action, [OAuthAction::MAILBOX, OAuthAction::LOGIN, OAuthAction::REGISTER]);
    }

    public function isValidCsrf(State $state): bool
    {
        $sessionCsrf = $this->session->get(self::SESSION_CSRF_KEY);

        if (!\is_array($sessionCsrf)) {
            $sessionCsrf = [$sessionCsrf];
        }

        return
            it($sessionCsrf)
            ->any(function ($csrf) use ($state) { return $csrf === $state->getCsrf(); });
    }

    protected function saveCsrfKey(SessionInterface $session, string $newCsrf): void
    {
        $toSave = [];

        if ($session->has(self::SESSION_CSRF_KEY)) {
            $oldCsrf = $session->get(self::SESSION_CSRF_KEY);

            if (\is_array($oldCsrf)) {
                $toSave = $oldCsrf;
            } else {
                $toSave = [$oldCsrf];
            }
        }

        $toSave[] = $newCsrf;
        $session->set(self::SESSION_CSRF_KEY, $toSave);
    }
}
