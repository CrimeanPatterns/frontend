<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\Common\DateTimeUtils;
use Psr\Log\LoggerInterface;

class LinkTargetHostResolver
{
    private LoggerInterface $logger;
    private \Memcached $cache;

    public function __construct(LoggerInterface $logger, \Memcached $cache)
    {
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function getTargetHostForLink(string $link, array $knownHosts): ?string
    {
        $cacheKey = "th_" . sha1($link);
        $host = $this->cache->get($cacheKey);

        if (!empty($host)) {
            return $host;
        }

        $options = [
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36',
        ];
        $redirectCount = 0;
        $startTime = microtime(true);

        // we will not try to get known host, because sometimes request to target host is forbidden from aws / russia
        // so, we will do redirects one by one, until we reached redirect to known host or error or success
        do {
            $requestInfo = [CURLINFO_EFFECTIVE_URL, CURLINFO_HTTP_CODE, CURLINFO_REDIRECT_URL];
            $status = curlRequest($link, 10, $options, $requestInfo, $curlErrno);

            if ($status === false || $requestInfo[CURLINFO_HTTP_CODE] < 300 || $requestInfo[CURLINFO_HTTP_CODE] >= 400) {
                break;
            }
            $link = $requestInfo[CURLINFO_REDIRECT_URL];

            if (isset($knownHosts[parse_url($link, PHP_URL_HOST)])) {
                break;
            }
        } while ($redirectCount < 20 && (microtime(true) - $startTime) < 30);

        if ($status === false) {
            $this->logger->warning("Curl request failed (curl errno $curlErrno", $requestInfo);

            return null;
        }

        $host = parse_url($link, PHP_URL_HOST);

        if ($host === false) {
            $this->logger->warning("Failed to parse host from url $link");

            return null;
        }

        $this->cache->set($cacheKey, $host, DateTimeUtils::SECONDS_PER_DAY);

        return $host;
    }
}
