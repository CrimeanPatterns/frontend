<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\Entity\AppleUserInfo;
use AwardWallet\MainBundle\Repository\AppleUserInfoRepository;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\AppleExchangeCodeRequest;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\ExchangeCodeRequest;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppleOAuth extends BaseOAuth
{
    /**
     * @var string
     */
    private $clientId;
    /**
     * @var string
     */
    private $privateKey;
    /**
     * @var string
     */
    private $teamId;
    /**
     * @var string
     */
    private $keyId;
    /**
     * @var AppleUserInfoRepository
     */
    private $userInfoRepository;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        string $clientId,
        string $privateKey,
        string $keyId,
        string $teamId,
        \HttpDriverInterface $httpDriver,
        LoggerInterface $logger,
        AppleUserInfoRepository $userInfoRepository,
        TranslatorInterface $translator
    ) {
        parent::__construct($httpDriver, $logger);
        $this->clientId = $clientId;
        $this->privateKey = $privateKey;
        $this->teamId = $teamId;
        $this->keyId = $keyId;
        $this->userInfoRepository = $userInfoRepository;
        $this->translator = $translator;
    }

    public function getType(): string
    {
        return 'apple';
    }

    public function exchangeCode(ExchangeCodeRequest $exchangeCodeRequest, ?array $rawCallbackData = null): ExchangeCodeResult
    {
        if (!$exchangeCodeRequest instanceof AppleExchangeCodeRequest) {
            throw new \LogicException('Request should be instance of ' . AppleExchangeCodeRequest::class);
        }

        $result = $this->sendExchangeCodeRequest($exchangeCodeRequest->getCode(), $exchangeCodeRequest->getRedirectUrl());

        if ($result === null) {
            return new ExchangeCodeResult(null, null, $this->translator->trans('error.auth.failure'));
        }

        $this->logger->info('got apple oauth response: ' . \json_encode($result));
        [$headb64, $bodyb64, $cryptob64] = \explode('.', $result['id_token']);
        // we do not need to verify tokens, info was just downloaded from apple servers
        $idToken = json_decode(base64_decode($bodyb64), true);

        /** @var AppleUserInfo $appleUserInfo */
        $appleUserInfo = $this->userInfoRepository->findOneBy(['sub' => $idToken['sub']]);
        $this->logger->info("apple user id: " . $idToken['sub'] . ", apple user info found: " . json_encode($appleUserInfo !== null));
        $userMeta = $exchangeCodeRequest->getUserName();

        if ($userMeta) {
            $this->logger->info("this is first time sign in, save user info, it will available only on first request");
            $firstName = $userMeta->getFirstName();
            $lastName = $userMeta->getLastName();

            if ($appleUserInfo === null) {
                if (!isset($idToken['email'])) {
                    $this->logger->notice("apple oauth: email does not exist in apple response.", ['raw_callback_data' => $rawCallbackData]);

                    return $this->createErrorExchangeCodeResult();
                }

                $email = $idToken['email'];
                $appleUserInfo = new AppleUserInfo($idToken['sub'], $firstName, $lastName, $email);
            } else {
                $email = $idToken['email'] ?? $appleUserInfo->getEmail();
                $appleUserInfo->update($firstName, $lastName, $email);
            }
        } else {
            if ($appleUserInfo === null) {
                $this->logger->warning("apple oauth: no user info, and no cache, some bug", ['raw_callback_data' => $rawCallbackData]);

                return $this->createErrorExchangeCodeResult();
            }

            $this->logger->info("no user info, load it from cache");
            $firstName = $appleUserInfo->getFirstName();
            $lastName = $appleUserInfo->getLastName();
            $email = $appleUserInfo->getEmail();
            $appleUserInfo->markAsUsed();
        }

        $this->userInfoRepository->save($appleUserInfo);

        return new ExchangeCodeResult(
            new UserInfo($email, $idToken["sub"], $firstName, $lastName),
            null,
            null
        );
    }

    protected function createErrorExchangeCodeResult(): ExchangeCodeResult
    {
        return new ExchangeCodeResult(null, null, $this->translator->trans(/** @Desc("Unfortunately we couldn't log you in with Apple Sign-In. Please try other authentication methods.") */ 'apple-auth-cache-missing'));
    }

    protected function getBaseConsentUrl(): string
    {
        return "https://appleid.apple.com/auth/authorize";
    }

    protected function getTokenRequestUrl(): string
    {
        return "https://appleid.apple.com/auth/token";
    }

    protected function getScopes(bool $mailboxAccess, bool $profileAccess): array
    {
        $result = [];

        if ($profileAccess) {
            $result[] = "name";
            $result[] = "email";
        }

        return array_unique($result);
    }

    protected function getClientId(): string
    {
        return $this->clientId;
    }

    protected function getClientSecret(): string
    {
        return JWT::encode([
            'iat' => time(),
            'exp' => time() + 300,
            'iss' => $this->teamId,
            'aud' => 'https://appleid.apple.com',
            'sub' => $this->clientId,
        ], $this->privateKey, 'ES256', $this->keyId);
    }

    protected function getExtraConsentUrlParams(): array
    {
        return [
            'response_mode' => 'form_post',
            'nonce' => bin2hex(random_bytes(10)),
        ];
    }
}
