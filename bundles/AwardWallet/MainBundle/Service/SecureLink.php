<?php

namespace AwardWallet\MainBundle\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SecureLink
{
    private RouterInterface $router;

    private string $scheme;

    private string $host;

    private string $businessHost;

    private string $imgKey;

    private string $redirectKey;

    public function __construct(
        RouterInterface $router,
        string $channel,
        string $host,
        string $businessHost,
        string $imageProxyKey,
        string $redirectOutKey
    ) {
        $this->router = $router;
        $this->scheme = $channel;
        $this->host = $host;
        $this->businessHost = $businessHost;
        $this->imgKey = $imageProxyKey;
        $this->redirectKey = $redirectOutKey;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getBusinessHost()
    {
        return $this->businessHost;
    }

    public function getSchemeAndHttpHost($isBusiness = false)
    {
        return "{$this->scheme}://" . ($isBusiness ? $this->businessHost : $this->host);
    }

    /**
     * @return string
     */
    public function getImgKey()
    {
        return $this->imgKey;
    }

    /**
     * @param string $imgKey
     * @return SecureLink
     */
    public function setImgKey($imgKey)
    {
        $this->imgKey = $imgKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getRedirectKey()
    {
        return $this->redirectKey;
    }

    /**
     * @param string $redirectKey
     * @return SecureLink
     */
    public function setRedirectKey($redirectKey)
    {
        $this->redirectKey = $redirectKey;

        return $this;
    }

    public function protectImgUrl($url, $schemeAndHttpHost = null, $isBusiness = false)
    {
        return (!is_null($schemeAndHttpHost) ? $schemeAndHttpHost : $this->getSchemeAndHttpHost($isBusiness))
            . "/imageProxy.php?"
            . "url=" . urlencode($url) . "&"
            . "hash=" . urlencode($this->getHash($url, $this->getImgKey()));
    }

    public function checkImgUrlHash($url, $hash)
    {
        return $this->checkHash($url, $hash, $this->getImgKey());
    }

    public function protectUrl($url, $schemeAndHttpHost = null, $isBusiness = false, $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return (!is_null($schemeAndHttpHost) ? $schemeAndHttpHost : $this->getSchemeAndHttpHost($isBusiness))
            . $this->router->generate("aw_out", [
                "url" => $url,
                "hash" => $this->getHash($url, $this->getRedirectKey()),
            ], $referenceType);
    }

    public function checkUrlHash($url, $hash)
    {
        return $this->checkHash($url, $hash, $this->getRedirectKey());
    }

    public function getUnsubscribeCode($email, $isBusiness = false)
    {
        $key = $isBusiness ? SECRET_KEY_EMAIL_UNSUBSCRIBE_BUSINESS_V2 : SECRET_KEY_EMAIL_UNSUBSCRIBE_PERSONAL;

        return $this->getHash($email, $key);
    }

    public function protectUnsubscribeUrl($email, $schemeAndHttpHost = null, $isBusiness = false)
    {
        return (!is_null($schemeAndHttpHost) ? $schemeAndHttpHost : $this->getSchemeAndHttpHost($isBusiness))
            . $this->router->generate("aw_profile_unsubscribe", [
                "email" => $email,
                "code" => $this->getUnsubscribeCode($email, $isBusiness),
            ]);
    }

    public function checkUnsubscribeHash($email, $hash, $isBusiness = false)
    {
        $key = $isBusiness ? SECRET_KEY_EMAIL_UNSUBSCRIBE_BUSINESS_V2 : SECRET_KEY_EMAIL_UNSUBSCRIBE_PERSONAL;

        return $hash === md5($email . $key) || $this->checkHash($email, $hash, $key);
    }

    public function checkUnsubscribeOldBusinessHash($email, $hash)
    {
        $key = SECRET_KEY_EMAIL_UNSUBSCRIBE_BUSINESS;

        return $hash === md5($email . $key) || $this->checkHash($email, $hash, $key);
    }

    public function checkHash($data, $hash, $key)
    {
        return $this->getHash($data, $key) === $hash;
    }

    public function getHash($data, $key)
    {
        $ctx = hash_init("sha256", HASH_HMAC, $key);
        hash_update($ctx, $data);

        return hash_final($ctx);
    }
}
