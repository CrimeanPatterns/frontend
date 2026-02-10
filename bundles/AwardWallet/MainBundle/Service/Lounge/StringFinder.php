<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class StringFinder
{
    private \HttpBrowser $http;

    public function __construct(string $string)
    {
        $this->http = new \HttpBrowser('none', new \CurlDriver());
        $this->http->SetBody($string);
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

    public static function create(string $string): self
    {
        return new self($string);
    }
}
