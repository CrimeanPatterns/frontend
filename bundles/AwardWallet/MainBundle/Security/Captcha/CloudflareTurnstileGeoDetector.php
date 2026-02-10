<?php

namespace AwardWallet\MainBundle\Security\Captcha;

use GeoIp2\Database\Reader as GeoIpCountry;
use GeoIp2\Exception\GeoIp2Exception;
use Symfony\Component\HttpFoundation\Request;

class CloudflareTurnstileGeoDetector
{
    private GeoIpCountry $geoIpCountry;

    public function __construct(GeoIpCountry $geoIpCountry)
    {
        $this->geoIpCountry = $geoIpCountry;
    }

    public function detect(Request $request): bool
    {
        $remoteIp = $request->getClientIp();

        try {
            $country = !empty($remoteIp) ? $this->geoIpCountry->country($remoteIp)->country->isoCode : null;
        } catch (GeoIp2Exception $e) {
            $country = null;
        }

        return \in_array(\strtoupper($country ?? ''), ['CN']);
    }
}
