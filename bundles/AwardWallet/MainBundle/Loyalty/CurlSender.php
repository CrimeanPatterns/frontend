<?php

namespace AwardWallet\MainBundle\Loyalty;

use Psr\Log\LoggerInterface;

class CurlSender
{
    public const TIMEOUT = 40;

    private $apiUrl;

    private $apiToken;
    /* using loyalty by user accountaccessproxy (not by awardwallet) to hide aa and other settings */
    private $proxyToken;

    /** @var LoggerInterface */
    private $logger;

    public function __construct($apiUrl, $apiToken, LoggerInterface $logger, $proxyToken)
    {
        $this->apiUrl = $apiUrl;
        $this->apiToken = $apiToken;
        $this->logger = $logger;
        $this->proxyToken = $proxyToken;
    }

    /**
     * @param null $jsonData
     * @param bool $isAwDefaultUser
     * @param int $timeout
     * @return CurlSenderResult|bool
     */
    public function call($method, $jsonData = null, $isAwDefaultUser = true, $timeout = self::TIMEOUT)
    {
        $token = $isAwDefaultUser ? $this->apiToken : $this->proxyToken;
        $curlResult = new CurlSenderResult();

        $url = $this->apiUrl . $method;
        $headers = [
            sprintf('X-Authentication: %s', $token),
            'Content-Type: application/json',
        ];
        $query = curl_init($url);

        if (!$query) {
            return $curlResult->setCode(0)->setError('Can not init curl query');
        }

        curl_setopt($query, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($query, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($query, CURLOPT_HEADER, false);
        curl_setopt($query, CURLOPT_FAILONERROR, false);
        curl_setopt($query, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($query, CURLOPT_RETURNTRANSFER, true);

        if (isset($jsonData)) {
            curl_setopt($query, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($query, CURLOPT_POSTFIELDS, $jsonData);
            $headers[] = 'Content-Length: ' . strlen($jsonData);
        }
        curl_setopt($query, CURLOPT_HTTPHEADER, $headers);
        //        curl_setopt($query, CURLOPT_COOKIE, "XDEBUG_SESSION=loyalty");
        $response = curl_exec($query);
        $code = curl_getinfo($query, CURLINFO_HTTP_CODE);
        $error = curl_error($query);

        if ($response === false || $code != '200') {
            $skipCritical = false;

            if ($method === '/v1/search' && is_string($response) && strpos($response, 'The currency you requested (USD) is not supported') !== false) {
                $skipCritical = true;
            }

            if (!$skipCritical) {
                $this->logger->critical(
                    "Loyalty curl failed, method $method, http code: $code, network error: " . curl_errno($query) . ' ' . curl_error($query),
                    ['loyaltyResponse' => $response]
                );
            }
        }

        curl_close($query);

        $curlResult->setCode($code)
                   ->setError($error)
                   ->setResponse($response);

        return $curlResult;
    }
}
