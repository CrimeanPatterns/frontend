<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker;

class UrlSigner
{
    /**
     * @var string
     */
    private $salt;

    public function __construct(string $salt)
    {
        $this->salt = $salt;
    }

    public function getSign(string $url): string
    {
        $url = $this->correctUnencodedQueryStringParams($url);

        return sha1($url . $this->salt);
    }

    private function correctUnencodedQueryStringParams(string $url): string
    {
        // ?gdpr=$[%FT_GDPR%] -> ?gdpr=%24%5B%25FT_GDPR%25%5D
        // because browser will correct this params, so they should match with click controller
        $parts = parse_url($url);

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
            $qs = http_build_query($params);
            $url = substr($url, 0, strpos($url, '?')) . $qs;

            if (!empty($parts['fragment'])) {
                $url .= '#' . $parts['fragment'];
            }
        }

        return $url;
    }
}
