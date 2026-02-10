<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BlogApi
{
    public const OPTION_EXPIRATION = 'expiration';

    private LoggerInterface $logger;
    private \CurlDriver $curlDriver;
    private \Memcached $memcached;

    private string $blogApiSecret;
    private string $rootDir;

    public function __construct(
        LoggerInterface $logger,
        \HttpDriverInterface $curlDriver,
        \Memcached $memcached,
        string $blogApiSecret,
        string $rootDir
    ) {
        $this->logger = $logger;
        $this->curlDriver = $curlDriver;
        $this->memcached = $memcached;
        $this->blogApiSecret = $blogApiSecret;
        $this->rootDir = $rootDir;
    }

    public function getRestApiData(string $url, array $options = []): ?array
    {
        $cacheKey = 'blog:' . __FUNCTION__ . '_v2_' . sha1($url . json_encode($options));
        $result = $this->memcached->get($cacheKey);

        if (is_array($result)) {
            if (array_key_exists(Constants::BUSY, $result)) {
                return null;
            }

            return $result;
        }

        $fullUrl = false !== strpos($url, 'http')
            ? $url
            : $this->getApiJsonUrl($url);

        if (isset($options['queryData'])) {
            $fullUrl .= (false !== strpos($fullUrl, '?') ? '&' : '?')
                . http_build_query($options['queryData']);
        }

        $method = array_key_exists('method', $options)
            ? $options['method']
            : Request::METHOD_GET;

        $headers = $this->getAuthData();

        if (BlogPost::IS_DEV_TEST) {
            switch ($url) {
                case Constants::API_URL_GET_MENU:
                    $response = (object) [
                        'body' => file_get_contents($this->rootDir . '/../tests/_data/Blog/mainMenu.json'),
                    ];

                    break;

                case Constants::API_URL_GET_ALL_TAGS:
                    $response = (object) [
                        'body' => file_get_contents($this->rootDir . '/../tests/_data/Blog/tags-all.json'),
                    ];

                    break;

                case Constants::API_URL_GET_CREDIT_CARDS:
                    $response = (object) [
                        'body' => file_get_contents($this->rootDir . '/../tests/_data/Blog/cards.json'),
                    ];

                    break;

                default:
                    throw new \RuntimeException('Undefined data');
            }
        } else {
            $response = $this->curlDriver->request(
                new \HttpDriverRequest(
                    $fullUrl,
                    $method,
                    null,
                    $headers,
                    Constants::REQUEST_TIMEOUT
                )
            );
        }

        $result = json_decode($response->body);

        if (null === $result && JSON_ERROR_NONE !== json_last_error()) {
            $this->logger->critical('Error retrieving data from Rest API blog: ' . $response->httpCode . ': ' . Strings::cutInMiddle($response->body, 1024));
            $this->memcached->set($cacheKey, [Constants::BUSY => true], 60 * 15);

            return null;
        }

        if (!BlogPost::IS_DEV_TEST
            && is_object($result)
            && property_exists($result, 'code')
            && Constants::CODE_ACCESS_DENIED === $result->code
        ) {
            $this->logger->critical($result->message);

            return null;
        }

        $expiration = $options[self::OPTION_EXPIRATION] ?? (60 * 60);
        $this->memcached->set($cacheKey, $result, $expiration);

        return (array) $result;
    }

    public function getApiJsonUrl(string $path, array $query = []): string
    {
        $url = Constants::API_URL;

        if (false === strpos($path, '/')) {
            $url .= 'api/';
        }

        $url .= ltrim($path, '/');

        return $url . (empty($query) ? '' : '?' . http_build_query($query));
    }

    public function getAuthData(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode('blog:' . $this->blogApiSecret),
        ];
    }

    public function checkAuth(Request $request): void
    {
        if ($request->getPassword() !== $this->blogApiSecret) {
            throw new AccessDeniedHttpException('Invalid authentication');
        }
    }
}
