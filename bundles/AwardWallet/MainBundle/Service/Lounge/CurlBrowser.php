<?php

namespace AwardWallet\MainBundle\Service\Lounge;

class CurlBrowser
{
    private Logger $logger;

    private Proxy $proxy;

    private ?\HttpBrowser $http;

    public function __construct(Logger $logger, Proxy $proxy)
    {
        $this->logger = $logger;
        $this->proxy = $proxy;
        $this->http = new \HttpBrowser('none', new \CurlDriver());
    }

    public function setProxy(string $proxy): self
    {
        $this->http->SetProxy($proxy);

        return $this;
    }

    public function resetProxyList(): self
    {
        $this->proxy->reset();

        return $this;
    }

    public function setDefaultHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->http->setDefaultHeader($name, $value);
        }

        return $this;
    }

    public function json(string $url, string $method = 'get', $data = null, array $headers = []): ?array
    {
        $result = $this->sendRequest($method, $url, $data, array_merge([
            'Accept' => 'application/json, text/plain, */*',
            'X-Requested-With' => 'XMLHttpRequest',
        ], $headers));
        $context = [
            'method' => strtoupper($method),
            'url' => $url,
            'httpCode' => $this->http->Response['code'] ?? 0,
        ];

        if (!is_string($response = $this->getResponse())) {
            throw new HttpException('Empty json response', $context);
        }

        if (!$result) {
            return null;
        }

        $jsonResponse = json_decode($response, true);

        if (!is_array($jsonResponse)) {
            throw new HttpException(sprintf('Unknown json: %s', var_export($response, true)), $context);
        }

        return $jsonResponse;
    }

    public function get(string $url, array $headers = []): bool
    {
        return $this->sendRequest('get', $url, null, array_merge([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ], $headers));
    }

    public function post(string $url, $data = null, array $headers = []): bool
    {
        return $this->sendRequest('post', $url, $data, array_merge([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ], $headers));
    }

    public function getResponse(): ?string
    {
        return $this->http->Response['body'];
    }

    public function findSingleXpath(string $xpath, ?string $regexp = null): ?string
    {
        return $this->http->FindSingleNode($xpath, null, true, $regexp);
    }

    public function findXpath(string $xpath): array
    {
        return $this->http->FindNodes($xpath);
    }

    public function findPreg(string $regexp): ?string
    {
        return $this->http->FindPreg($regexp);
    }

    private function sendRequest(string $method, string $url, $data, array $headers): bool
    {
        return $this->proxy->useProxy(function () use ($method, $url, $data, $headers) {
            $context = [
                'method' => strtoupper($method),
                'url' => $url,
            ];
            $this->logger->info('send request', $context);

            switch (strtolower($method)) {
                case 'get':
                    $this->http->GetURL($url, $headers);

                    break;

                case 'post':
                    $this->http->PostURL($url, $data, $headers);

                    break;

                default:
                    throw new \InvalidArgumentException(sprintf('Unknown method "%s"', $method));
            }

            $httpCode = $this->http->Response['code'];
            $context['httpCode'] = $httpCode;

            if ($this->needChangeProxy()) {
                $this->logger->info('try change proxy', $context);

                return Proxy::CHANGE_PROXY;
            }

            if (empty($this->http->Response['body'])) {
                throw new HttpException(sprintf('Empty response. Error message: %s', $http->Response['errorMessage'] ?? 'unknown'), $context);
            }

            if ($httpCode >= 400) {
                throw new HttpException('Http error', $context);
            }

            if ($httpCode != 200) {
                $this->logger->error('http error', $context);

                return false;
            }

            return true;
        }, $this);
    }

    private function needChangeProxy(): bool
    {
        return in_array($this->http->Response['code'], [403, 503, 0])
            || stripos($this->http->Error, 'Operation timed out after') !== false
            || (
                $this->http->Response['code'] != 200
                && (
                    (
                        $this->http->Response['errorCode'] == 56
                        && stripos($this->http->Response['errorMessage'], 'proxy')
                    ) || $this->http->Response['errorCode'] == 7
                )
            );
    }
}
