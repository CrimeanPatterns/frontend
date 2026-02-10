<?php

namespace Codeception\Module;

use AwardWallet\Common\Tests\HttpCache;
use AwardWallet\Common\Tests\TestPathExtractor;

class GoogleApiMock extends \Codeception\Module
{
    public const MOCK_FILE = __DIR__ . '/../_data/GoogleApiRequests.json';

    public function _initialize()
    {
        parent::_initialize();

        global $onGoogleGeoXmlRequest;
        $onGoogleGeoXmlRequest = [\Codeception\Module\GoogleApiMock::class, "onGoogleXmlRequest"];

        HttpCache::load(self::MOCK_FILE);
    }

    public static function onGoogleXmlRequest($request)
    {
        $index = "geoxml:" . $request;

        if (isset(HttpCache::$mockedResponses[$index])) {
            return new \SimpleXMLElement(HttpCache::$mockedResponses[$index]["response"]);
        }

        if (
            function_exists('getSymfonyContainer')
            && getSymfonyContainer()->hasParameter('google_api_key')
            && strlen(getSymfonyContainer()->getParameter('google_api_key')) > 15
        ) {
            $baseUrl = "https://maps.googleapis.com/maps/api/geocode/xml?key=" . urlencode(getSymfonyContainer()->getParameter('google_api_key')) . "&";
        } else {
            $baseUrl = "http://maps.googleapis.com/maps/api/geocode/xml?";
        }

        $result = SendGoogleGeoRequest($baseUrl . $request);

        if (!empty($result)) {
            HttpCache::$mockedResponses[$index] = ["response" => $result->asXML(), "version" => 1, "file" => TestPathExtractor::getFileAndLine()];
        }

        return $result;
    }
}
