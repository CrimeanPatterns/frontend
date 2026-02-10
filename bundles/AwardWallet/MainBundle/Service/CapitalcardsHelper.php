<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\Engine\capitalcards\APIChecker;
use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CapitalcardsHelper
{
    private bool $rewardsSandbox;

    private string $clientId;

    private string $clientSecret;

    private bool $txSandbox;

    private string $txClientId;

    private string $txClientSecret;

    private ?string $apiHost;

    private string $localPasswordsKey;

    private RouterInterface $router;

    private LoggerInterface $logger;

    public function __construct(
        bool $rewardsSandbox,
        string $clientId,
        string $clientSecret,
        bool $txSandbox,
        string $txClientId,
        string $txClientSecret,
        ?string $apiHost,
        string $localPasswordsKey,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->rewardsSandbox = $rewardsSandbox;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->txSandbox = $txSandbox;
        $this->txClientId = $txClientId;
        $this->txClientSecret = $txClientSecret;
        $this->apiHost = $apiHost;
        $this->localPasswordsKey = $localPasswordsKey;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * @param string $tokenType - "access_token" | "refresh_token"
     */
    public function revokeAccess(bool $rewards, string $tokenType, string $token): void
    {
        $this->apiRequest($rewards, "/oauth2/revoke", ["token" => $token, "token_type_hint" => $tokenType]);
    }

    public function getClientId(bool $rewards): string
    {
        return $rewards ? $this->clientId : $this->txClientId;
    }

    public function getAuthorizeUrl(bool $rewards, string $state): string
    {
        return $this->getBaseHost($rewards) . "/oauth2/authorize?" . http_build_query([
            "client_id" => $rewards ? $this->clientId : $this->txClientId,
            "redirect_uri" => $this->router->generate('aw_auth_capitalcards_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            "scope" => $rewards ? "read_rewards_account_info" : "read_transactions",
            "response_type" => "code",
            "state" => $state,
        ]);
    }

    public function exchangeCode(bool $rewards, string $code): ?array
    {
        $result = $this->apiRequest($rewards, "/oauth2/token", [
            'code' => $code,
            'redirect_uri' => $this->router->generate('aw_auth_capitalcards_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'grant_type' => 'authorization_code',
        ]);

        $tokenInfo = APIChecker::parseTokenInfo(json_decode($result, true), $rewards ? $this->rewardsSandbox : $this->txSandbox);

        if ($tokenInfo === null) {
            $this->logger->error("invalid token: " . $result);
        }

        return $tokenInfo;
    }

    public function revokeAuthInfo(string $authInfo): void
    {
        $decoded = self::decodeSavedAuthInfo($authInfo);

        if (!empty($decoded['rewards'])) {
            $this->revokeEncodedTokens(true, $decoded['rewards']);
        }

        if (!empty($decoded['tx'])) {
            $this->revokeEncodedTokens(false, $decoded['tx']);
        }
    }

    public function revokeEncodedTokens(bool $rewards, string $authInfo): void
    {
        $tokens = json_decode(AESDecode(base64_decode($authInfo), $this->localPasswordsKey), true);

        foreach (['access_token', 'refresh_token'] as $tokenType) {
            if (isset($tokens[$tokenType])) {
                $this->revokeAccess($rewards, $tokenType, $tokens[$tokenType]);
            }
        }
    }

    public static function decodeSavedAuthInfo(?string $authInfo): array
    {
        if (empty($authInfo)) {
            return ["rewards" => null, "tx" => null];
        }

        if (substr($authInfo, 0, 3) === 'v1:') {
            // new format, v1:{"rewards" => "someencodedtokens"|null, "tx" => "someencodedtokens"|null}
            $authInfo = substr($authInfo, 3);

            return json_decode($authInfo, true);
        }

        return ["rewards" => $authInfo, "tx" => null];
    }

    public static function encodeAuthInfo(array $params): string
    {
        return "v1:" . json_encode($params);
    }

    private function getBaseHost(bool $rewards): string
    {
        if ($this->apiHost !== null) {
            return $this->apiHost;
        }

        if ($rewards && $this->rewardsSandbox) {
            return "https://apiit.capitalone.com";
            // return "https://api-sandbox.capitalone.com";
        }

        if (!$rewards && $this->txSandbox) {
            return "https://apiit.capitalone.com";
        }

        return "https://api.capitalone.com";
    }

    private function apiRequest(bool $rewards, string $url, array $params): string
    {
        $url = $this->getBaseHost($rewards) . $url;

        $postParams = array_merge([
            'client_id' => $rewards ? $this->clientId : $this->txClientId,
            'client_secret' => $rewards ? $this->clientSecret : $this->txClientSecret,
        ], $params);

        $options = [
            CURLOPT_POST => true,
            CURLOPT_FAILONERROR => false, // want to see errors
            CURLOPT_POSTFIELDS => http_build_query($postParams),
            CURLOPT_HTTPHEADER => ['User-Agent: awardwallet'],
        ];
        $this->logger->info("capital cards api request", ["url" => $url, "postParams" => array_map(function (string $str) { return Strings::cutInMiddle($str, 6); }, $postParams)]);
        $result = curlRequest($url, 60, $options);
        $this->logger->info("capital cards api response: " . Strings::cutInMiddle((string) $result, 20));

        return $result;
    }
}
