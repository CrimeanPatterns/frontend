<?php

namespace AwardWallet\MainBundle\Security\RememberMe;

use AwardWallet\MainBundle\FrameworkExtension\Error\ErrorUtils;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\Service\FriendsOfLoggerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\RememberMe\AbstractRememberMeServices;

/**
 * Inspired by PersistentTokenBasedRememberMeServices (Symfony, Spring) with modifications
 * Token value is not stored in plain text, instead we store hash, so DB compromising won't lead to theft of all active sessions.
 */
class RememberMeServices extends AbstractRememberMeServices
{
    use FriendsOfLoggerTrait;

    public const REQUEST_ATTR_TOKEN_ID = 'RememberMeTokenID';

    /** @var TokenProviderInterface */
    private $tokenProvider;

    /**
     * Constructor.
     *
     * @param string $secret - [$currentKey:$oldKey] can't introduce new parameter, because firewall will crearte instance of this class with this signature
     * @param string $providerKey
     */
    public function __construct(array $userProviders, $secret, $providerKey, array $options = [], ?LoggerInterface $logger = null)
    {
        if ($logger) {
            $logger = $this->makeContextAwareLogger($logger);
        }

        parent::__construct($userProviders, $secret, $providerKey, $options, $logger);
    }

    /**
     * Sets the token provider.
     */
    public function setTokenProvider(TokenProviderInterface $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
    }

    public function onLoginSuccess(Request $request, Response $response, TokenInterface $token)
    {
        $user = $token->getUser();
        $cookieValue = $this->createNewToken(get_class($user), $user->getUsername(), $request->getClientIp(), $request->headers->get('User-Agent'));

        $this->setCookie(
            $response,
            $this->options['name'],
            $cookieValue,
            $expires = (time() + $this->options['lifetime'])
        );

        if ($this->logger) {
            $this->logger->info('Login Success. Remember-me cookie set in response.', [
                'raw_cookie_len' => \strlen($cookieValue),
                'expiration_ts' => $expires,
            ]);
        }

        $this->setCookie($response, 'Log', $user->getUsername(), $expires);
        $this->setCookie($response, 'PasswordSaved', "1", $expires);
        $this->setCookie($response, 'SavePwd', "1", $expires);
    }

    protected function cancelCookie(Request $request)
    {
        // Delete cookie on the client
        parent::cancelCookie($request);

        // Delete cookie from the tokenProvider
        if (null !== ($cookie = $request->cookies->get($this->options['name']))
            && count($parts = $this->decodeCookie($cookie)) === 4
        ) {
            [$class, $series, $tokenValue, $hash] = $parts;
            $this->tokenProvider->deleteTokenBySeries($series);

            if ($this->logger) {
                $this->logger->info('Remember-me cookie canceled and removed from DB by token provider.', [
                    'token_parts' => self::logSeriesAndTokenSafe($series, $tokenValue),
                ]);
            }
        }
    }

    protected function decodeCookie($rawCookie)
    {
        $decodedCookieParts = parent::decodeCookie($rawCookie);

        $cookiePartsCount = \count($decodedCookieParts);

        if (
            (4 !== $cookiePartsCount)
            || (\strlen($rawCookie) <= 100)
        ) {
            if ($this->logger) {
                $this->logger->info("Decode cookie. The cookie is invalid. Provided cookie parts: {$cookiePartsCount}, expected 4.", [
                    'raw_cookie_len' => \strlen($rawCookie),
                    // first 46 chars specify base64_encode'd Usr::class FQCN
                    'raw_cookie_prefix' => \substr($rawCookie, 0, 100),
                ]);
            }
        }

        return $decodedCookieParts;
    }

    protected function processAutoLoginCookie(array $cookieParts, Request $request)
    {
        if (count($cookieParts) !== 4) {
            throw new AuthenticationException('The cookie is invalid.');
        }

        [$class, $series, $tokenValue, $hash] = $cookieParts;

        if ($this->logger) {
            $this->logger->info('Token was provided', [
                'token_parts' => self::logSeriesAndTokenSafe($series, $tokenValue),
            ]);
        }

        // check hash first to prevent DB bruteforce
        $keys = explode(":", $this->getSecret());
        $matched = false;
        $needNewHash = false;

        foreach ($keys as $n => $key) {
            if (true === hash_equals($this->generateCookieHash($class, $series, $tokenValue, $key), $hash)) {
                $matched = true;
                $needNewHash = $n > 0;

                break;
            }
        }

        if (!$matched) {
            throw new AuthenticationException('The cookie is invalid.');
        }

        /** @var AwPersistentToken $persistentToken */
        $persistentToken = $this->tokenProvider->loadTokenBySeries($series);

        if (!$persistentToken instanceof AwPersistentToken) {
            // TODO: Ain't expired?
            throw new AuthenticationException('The cookie is invalid.');
        }
        $persistentToken->setClass($class);

        /*
         * Disabled because of token race issues: request1 and request2 with a same cookie started simultaneously.
         * request2 triggered token refresh before request1 processed by server, so request1 processing will result to authentication error.
         */
        //        if (true !== StringUtils::equals($persistentToken->getTokenValue(), $this->generateTokenHash($tokenValue))) {
        //            $this->logger->critical('Remember-me-cookie inconsistency. User: ' . $persistentToken->getUsername());
        //            throw new AuthenticationException('The cookie is invalid');
        //        }

        if ($persistentToken->getLastUsed()->getTimestamp() + $this->options['lifetime'] < time()) {
            throw new AuthenticationException('The cookie has expired.');
        }

        /*
         * disabled for the same reasons as in the above comment
         */
        //        $series = $persistentToken->getSeries();
        //        $tokenValue = $this->generateEncodedRandomData();
        //        $this->tokenProvider->updateToken($series, $this->generateTokenHash($tokenValue), new \DateTime());

        if ($request->cookies->has($this->options['name'])) {
            if ($needNewHash) {
                $value = $this->createNewToken($persistentToken->getClass(), $persistentToken->getUsername(), $request->getClientIp(), $request->headers->get('User-Agent'));
            } else {
                $value = $request->cookies->get($this->options['name']);
            }

            // This block is needed to update the cookie expiration time when 'lifetime' option is increased
            $request->attributes->set(
                self::COOKIE_ATTR_NAME,
                AwCookieFactory::createLax(
                    $this->options['name'],
                    $value,
                    $expires = ($persistentToken->getLastUsed()->getTimestamp() + $this->options['lifetime']),
                    $this->options['path'],
                    $this->options['domain'],
                    $this->options['secure'],
                    $this->options['httponly']
                )
            );

            if ($this->logger) {
                $this->logger->info('Process autologin cookie. Remember-me cookie set in request attribute.', [
                    'raw_cookie_len' => \strlen($value),
                    'expiration_ts' => $expires,
                ]);
            }
        }

        $result = $this->getUserProvider($persistentToken->getClass())->loadUserByUsername($persistentToken->getUsername());

        if (!empty($result)) {
            $request->attributes->set(self::REQUEST_ATTR_TOKEN_ID, $persistentToken->getTokenId());
        }

        return $result;
    }

    /**
     * Generates a hash for the cookie to ensure it is not being tempered with.
     *
     * @param string $class User class
     * @param string $series Token series
     * @param string $tokenValue Token value
     * @return string
     */
    protected function generateCookieHash($class, $series, $tokenValue, $key)
    {
        return hash_hmac('sha256', $class . $series . $tokenValue, $key);
    }

    protected function generateTokenHash($tokenValue)
    {
        return hash_hmac('sha256', $tokenValue, explode(":", $this->getSecret())[0]);
    }

    protected function generateEncodedRandomData($length = 64)
    {
        return base64_encode(random_bytes($length));
    }

    protected function retryOnDuplicate(callable $task, $maxRetries = 3)
    {
        $lastException = null;

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $task();

                return;
            } catch (\Exception $e) {
                $lastException = $e;

                if (
                    ($previousException = $e->getPrevious())
                    && $previousException instanceof \PDOException
                    && $previousException->getCode() == '23000'
                ) {
                    continue;
                } else {
                    break;
                }
            }
        }

        throw $lastException;
    }

    protected function onLoginFail(Request $request, ?\Exception $exception = null)
    {
        parent::onLoginFail($request, $exception);

        if (!$exception) {
            return;
        }

        if ($this->logger) {
            $logEntry = ErrorUtils::makeLogEntry($exception);
            $this->logger->info($logEntry->getMessage(), $logEntry->getContext());
        }
    }

    private function createNewToken($class, $userName, $ip = '', $userAgent = null)
    {
        $tokenValue = null;

        $this->retryOnDuplicate(function () use (&$series, &$tokenValue, $userName, $class, $ip, $userAgent) {
            $series = $this->generateEncodedRandomData();
            $tokenValue = $this->generateEncodedRandomData();

            $this->tokenProvider->createNewToken(
                new AwPersistentToken(
                    $class,
                    $userName,
                    $series,
                    $this->generateTokenHash($tokenValue),
                    new \DateTime(),
                    $ip,
                    $userAgent,
                    null
                )
            );
        });

        $cookieValue = $this->encodeCookie([
            $class,
            $series,
            $tokenValue,
            $this->generateCookieHash($class, $series, $tokenValue, explode(":", $this->getSecret())[0]),
        ]);

        if ($this->logger) {
            $this->logger->info('New token created', [
                'token_parts' => self::logSeriesAndTokenSafe($series, $tokenValue),
            ]);
        }

        return $cookieValue;
    }

    /**
     * @return array{series: string, token: string}
     */
    private static function logSeriesAndTokenSafe(string $series, string $tokenValue): array
    {
        $result = [
            'series' => $series,
            'token' => $tokenValue,
        ];

        foreach ($result as $name => $value) {
            $result[$name] = \substr(\hash('sha256', $value), 0, 8);
        }

        return $result;
    }

    private function setCookie(Response $response, $name, $value, $expires)
    {
        $response->headers->setCookie(
            AwCookieFactory::createLax(
                $name,
                $value,
                $expires,
                $this->options['path'],
                $this->options['domain'],
                $this->options['secure'],
                $this->options['httponly']
            )
        );
    }
}
