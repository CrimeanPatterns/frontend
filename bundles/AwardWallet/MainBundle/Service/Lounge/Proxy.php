<?php

namespace AwardWallet\MainBundle\Service\Lounge;

class Proxy
{
    public const CHANGE_PROXY = 'change_proxy';

    private array $proxyList;

    private array $usedProxy = [];

    private ?string $currentProxy = null;

    public function __construct(ProxyList $proxyList)
    {
        $this->proxyList = $proxyList->getProxyList();
    }

    public function useProxy(callable $callback, CurlBrowser $browser, int $attempts = 10): bool
    {
        $proxy = $this->getProxy();
        $try = 0;

        do {
            $browser->setProxy($proxy);
            $result = $callback();

            if ($result === self::CHANGE_PROXY) {
                $proxy = $this->getProxy(true);
                $try++;
            } else {
                return $result;
            }
        } while ($try < $attempts);

        throw new NoProxyException('Attempts limit reached');
    }

    public function getProxy(bool $change = false): string
    {
        if (empty($this->currentProxy) || $change) {
            if (!empty($this->currentProxy)) {
                $this->usedProxy[$this->currentProxy] = $this->currentProxy;
            }

            $this->currentProxy = $this->getUnusedProxy($this->usedProxy);

            if (is_null($this->currentProxy)) {
                throw new NoProxyException('No proxies or all used');
            }
        }

        return $this->currentProxy;
    }

    public function reset(): void
    {
        $this->usedProxy = [];
        $this->currentProxy = null;
    }

    private function getUnusedProxy(array $usedProxy): ?string
    {
        $proxies = array_diff($this->proxyList, $usedProxy);
        shuffle($proxies);

        return $proxies[0] ?? null;
    }
}
