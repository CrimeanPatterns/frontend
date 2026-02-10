<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Manager\Exception\MalformedLocalPasswordsException;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LocalPasswordsManager
{
    public const ATTR_NAME = '_local_passwords_manager_active';

    public const COOKIE_LIFETIME = SECONDS_PER_DAY * 30 * 12 * 5;
    public const COOKIE_RENEW_OFFSET = SECONDS_PER_DAY * 30 * 12;

    public const COOKIE_MANAGED = 1;
    public const COOKIE_UNMANAGED = 2;
    private const DATA_FORMAT_JSON = 'json';

    /**
     * @var array
     */
    private $passwords;

    /**
     * @var Usr
     */
    private $user;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var array
     */
    private $managedCookies;

    /**
     * @var array
     */
    private $unsaved;
    /**
     * @var bool
     */
    private $loaded = false;
    /**
     * @var string
     */
    private $localPasswordsKey;
    /**
     * @var string
     */
    private $localPasswordsKeyOld;
    private BinaryLoggerFactory $check;
    private S3Client $s3Client;

    public function __construct(
        RequestStack $requestStack,
        AwTokenStorageInterface $tokenStorage,
        KernelInterface $kernel,
        LoggerInterface $logger,
        S3Client $s3Client,
        $localPasswordsKey,
        $localPasswordsKeyOld
    ) {
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('local passwords manager: ');
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo();
        $this->kernel = $kernel;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->localPasswordsKey = $localPasswordsKey;
        $this->localPasswordsKeyOld = $localPasswordsKeyOld;

        $this->unsaved = [];
        $this->passwords = [];
        $this->managedCookies = [];
        $this->s3Client = $s3Client;
    }

    /**
     * @internal
     */
    public function save(Response $response)
    {
        $this->logger->info('saving passwords to cookies');

        if (
            $this->check->that('manager')->hasNot('unsaved passwords')
            ->on(!$this->isUnsaved())
        ) {
            return;
        }

        if (
            $this->check->that('manager')->hasNot('existing passwords')
            ->on(!$this->passwords)
        ) {
            $this->drop();

            return;
        }

        $data = $this->passwords;
        $data['UserID'] = $this->user->getUserid();

        $data['expiration'] = $expiration = time() + self::COOKIE_LIFETIME;

        $encoded = $this->encode($data);

        $savedParts = [];
        $parts = str_split($encoded, 1024);

        foreach ($parts as $partId => $part) {
            $cookieName = 'APv2-' . $partId;
            $savedParts[$cookieName] = self::COOKIE_MANAGED;
            $response->headers->setCookie(AwCookieFactory::createLax($cookieName, $part, $expiration, '/', null, $this->kernel->getEnvironment() == 'prod', true));
        }

        $this->managedCookies = array_merge(
            array_fill_keys(array_keys($this->managedCookies), self::COOKIE_UNMANAGED),
            $savedParts
        );
    }

    public function encode(array $data)
    {
        $serialized = sprintf('%s:%s', self::DATA_FORMAT_JSON, json_encode($data));
        $encoded = AESEncode($serialized, $this->localPasswordsKey);
        $decoded = AESDecode($encoded, $this->localPasswordsKey);

        if ($serialized !== $decoded) {
            $this->logger->critical(
                "Incorrect decoding local password\n" .
                "UserID: " . $this->user->getUserid() . "\n" .
                "Source length: " . strlen($serialized) . "\n" .
                "Decoded length: " . strlen($decoded)
            );

            return '';
        }
        $encoded = base64_encode($encoded);

        return $encoded;
    }

    /**
     * @param bool|true $getUserId
     * @return array
     * @throws MalformedLocalPasswordsException
     */
    public function decode($data, $getUserId = true)
    {
        $uid = ($this->tokenStorage->getToken() instanceof TokenInterface
            && $this->tokenStorage->getToken()->getUser() instanceof Usr)
            ? $this->tokenStorage->getToken()->getUser()->getUserid()
            : null;
        $this->logger->pushContext(['userId' => $uid]);

        try {
            if (false === ($data = base64_decode($data, true))) {
                $this->logger->info('Base64 Decode Error');

                throw new MalformedLocalPasswordsException('Base64 Decode Error');
            }
            $this->logger->info("decoded passwords length: " . strlen($data));

            foreach ([$this->localPasswordsKey, $this->localPasswordsKeyOld, "%local_passwords_key%"] as $key) {
                try {
                    $decoded = AESDecode($data, $key);

                    if ($decoded === false) {
                        $this->logger->info('failed to decode');

                        throw new MalformedLocalPasswordsException('failed to decode');
                    }

                    $isJsonFormat = false;
                    $prefixJson = self::DATA_FORMAT_JSON . ':';

                    if (strpos($decoded, $prefixJson) === 0) {
                        $isJsonFormat = true;
                        $pairs = json_decode(substr($decoded, strlen($prefixJson)), true);
                    } else {
                        $pairs = explode(',', $decoded);
                        $pairs = $this->fixCommasInPasswords($pairs);
                    }

                    $this->logger->info("decoded passwords pairs: " . count($pairs));

                    $passwords = [];
                    $userId = null;
                    $expirationDate = null;

                    foreach ($pairs as $k => $pair) {
                        if (empty($pair)) {
                            $this->logger->warning("empty pair, some bug in accountBackupPasswordsAction");

                            continue;
                        }

                        if ($isJsonFormat) {
                            $ar = [$k, $pair];
                        } else {
                            $ar = explode(':', $pair);
                        }

                        if (count($ar) == 2) {
                            [$pairName, $pairValue] = $ar;

                            if (preg_match('/^\d+$/', $pairName)) {
                                $passwords[$pairName] = $pairValue;
                            } elseif (preg_match('/^a(\d+)$/', $pairName, $matches)) {
                                $passwords[$matches[1]] = $pairValue;
                            } elseif ('UserID' === $pairName) {
                                $userId = (int) $pairValue;
                            } elseif ('expiration' === $pairName) {
                                $expirationDate = (int) $pairValue;
                            } else {
                                $this->logger->info('Unknown key-value pair format');

                                throw new MalformedLocalPasswordsException('Unknown key-value pair format: ' . $pair);
                            }
                        } else {
                            $tempFile = "passwords-" . bin2hex(random_bytes(10));
                            $this->s3Client->upload('awardwallet-logs', $tempFile, $data);
                            $this->logger->warning("passwords were saved to s3 awardwallet-logs/$tempFile");

                            $tempFile = "decoded-" . bin2hex(random_bytes(10));
                            $this->s3Client->upload('awardwallet-logs', $tempFile, $decoded);
                            $this->logger->warning("decoded were saved to s3 awardwallet-logs/$tempFile");

                            $this->logger->critical("passwords decode error");

                            throw new MalformedLocalPasswordsException('Error key-value pair: ' . $pair);
                        }
                    }

                    $exception = null;

                    if (
                        (null === $expirationDate) // old format w/o stored expiration date
                        || ($expirationDate - self::COOKIE_RENEW_OFFSET <= time()) // it is time to renew cookies
                        || ($key != $this->localPasswordsKey) // not current passwords key
                    ) {
                        $this->logger->info('setUnsaved');
                        $this->setUnsaved();
                    }

                    break;
                } catch (MalformedLocalPasswordsException $e) {
                    $exception = $e;

                    continue;
                }
            }

            if (!empty($exception)) {
                throw $exception;
            }

            if ($getUserId) {
                return [$passwords, $userId];
            } else {
                return $passwords;
            }
        } finally {
            $this->logger->popContext();
        }
    }

    /**
     * @internal
     */
    public function clearUnmanagedCookies(Response $response)
    {
        foreach (array_keys($this->managedCookies, self::COOKIE_UNMANAGED) as $cookieName) {
            $response->headers->clearCookie($cookieName, "/");
        }
    }

    /**
     * @param int $accountId
     * @return string
     */
    public function getPassword($accountId)
    {
        $this->activate();

        if (empty($this->passwords[(int) $accountId])) {
            return '';
        }

        return base64_decode($this->passwords[(int) $accountId]);
    }

    /**
     * @param int $accountId
     * @return bool
     */
    public function hasPassword($accountId)
    {
        $this->activate();

        return array_key_exists((int) $accountId, $this->passwords);
    }

    /**
     * @param int $accountId
     * @param string $value
     */
    public function setPassword($accountId, $value)
    {
        $this->activate();
        $accountId = (int) $accountId;

        if (!$this->hasPassword($accountId) // password did not exist before
            || ($this->hasPassword($accountId) && 0 !== strcmp($value, $this->getPassword($accountId)))) { // password changed
            $this->passwords[$accountId] = base64_encode($value);
            $this->setUnsaved($accountId);
        }
    }

    /**
     * @param int $accountId
     */
    public function removePassword($accountId)
    {
        $this->activate();
        $accountId = (int) $accountId;

        if ($this->hasPassword($accountId)) {
            unset($this->passwords[$accountId]);
            $this->setUnsaved($accountId);
        }
    }

    /**
     * @param int $accountId
     * @return bool
     * @internal
     */
    public function isUnsaved($accountId = null)
    {
        if (!isset($accountId)) {
            return !empty($this->unsaved);
        } else {
            return isset($this->unsaved[(int) $accountId]);
        }
    }

    /**
     * clears loaded state.
     */
    public function clear()
    {
        $this->unsaved = [];
        $this->passwords = [];
        $this->managedCookies = [];

        $this->loaded = false;
    }

    /**
     * @throws Exception\MalformedLocalPasswordsException
     */
    private function load()
    {
        $this->logger->debug('loading local passwords');

        try {
            // TODO: cookie storage format versioning
            $request = $this->requestStack->getCurrentRequest();

            if (($token = $this->tokenStorage->getToken()) instanceof TokenInterface) {
                if (!($this->user = $token->getUser()) instanceof Usr) {
                    return;
                }
            }

            if (
                !isset($request)
                || $request->attributes->has(self::ATTR_NAME)
            ) {
                return;
            }

            $request->attributes->set(self::ATTR_NAME, true);

            $parts = [];

            foreach ($request->cookies as $cookieName => $cookieValue) {
                if (preg_match('/APv2-(\d+)/', $cookieName, $matches)) {
                    if (isset($matches[1])) {
                        $partId = (int) $matches[1];
                    } else {
                        $partId = 0;
                    }
                    $this->managedCookies[$cookieName] = self::COOKIE_MANAGED;
                    $parts[$partId] = $cookieValue;
                }
            }

            if (!$parts) {
                return;
            }

            ksort($parts);

            // check order
            if (count($ids = array_keys($parts)) !== max($ids) + 1) {
                throw new MalformedLocalPasswordsException('some cookie parts are missing');
            }

            $data = implode('', $parts);

            [$localPasswords, $userId] = $this->decode($data);

            if ($this->user->getUserid() !== $userId) {
                throw new MalformedLocalPasswordsException('Local passwords belongs to another user ' . $userId . ', expected user ' . $this->user->getUserid());
            }

            if (!$localPasswords) {
                throw new MalformedLocalPasswordsException();
            }
            $this->passwords = $localPasswords;
        } catch (MalformedLocalPasswordsException $e) {
            $this->logger->warning("malformed local passwords: " . $e->getMessage());
            $this->drop();
        }
        $this->loaded = true;
    }

    private function activate()
    {
        if (!$this->loaded) {
            $this->load();
        }
    }

    private function drop()
    {
        $this->logger->info('dropping managed cookies');
        $this->passwords = [];

        foreach ($this->managedCookies as $cookieName => $cookieState) {
            $this->managedCookies[$cookieName] = self::COOKIE_UNMANAGED;
        }
        $this->setUnsaved();
    }

    /**
     * @param int $accountId
     */
    private function setUnsaved($accountId = null)
    {
        if (!isset($accountId)) {
            $this->unsaved['_'] = true;
        } else {
            $this->unsaved[(int) $accountId] = true;
        }
    }

    private function fixCommasInPasswords(array $pairs): array
    {
        $last = null;
        $result = [];

        foreach ($pairs as $pair) {
            if ($last !== null && !preg_match('#^(a?\d+|UserID|expiration):#', $pair)) {
                $this->logger->info("correcting broken password");
                $last .= ",$pair";

                continue;
            }

            if ($last !== null) {
                $result[] = $last;
            }

            $last = $pair;
        }

        $result[] = $last;

        return $result;
    }
}
