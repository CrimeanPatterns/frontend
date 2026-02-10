<?php

namespace AwardWallet\tests\unit\Google;

use AwardWallet\Common\Geo\Google\GeoCodeParameters;
use AwardWallet\Common\Geo\Google\GeoCodeResponse;
use AwardWallet\Common\Geo\Google\GoogleApi;
use AwardWallet\Common\Geo\Google\GoogleRequestFailedException;
use AwardWallet\Common\Geo\Google\GoogleRequestLimitReachedException;
use AwardWallet\Common\Geo\Google\LatLng;
use AwardWallet\Common\Geo\Google\PlaceAutocompleteParameters;
use AwardWallet\Common\Geo\Google\PlaceAutocompleteResponse;
use AwardWallet\Common\Geo\Google\PlaceDetailsParameters;
use AwardWallet\Common\Geo\Google\PlaceDetailsResponse;
use AwardWallet\Common\Geo\Google\PlaceSearchResponse;
use AwardWallet\Common\Geo\Google\PlaceTextSearchParameters;
use AwardWallet\Common\Geo\Google\TimeZoneParameters;
use AwardWallet\Common\Geo\Google\TimeZoneResponse;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use JMS\Serializer\SerializerInterface;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class GoogleApiTest extends Unit
{
    public const TEST_TIMESTAMP = 100000;
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];
    /**
     * @var GoogleApi
     */
    private $googleApi;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Memcached
     */
    private $memcached;

    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;

    public function _before()
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        /** @var SerializerInterface $serializer */
        $this->serializer = $symfony->grabService('jms_serializer');
        /** @var \HttpDriverInterface $httpDriver */
        $this->httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => function (\HttpDriverRequest $request) {
            switch ($request->url) {
                case 'https://maps.googleapis.com/maps/api/place/textsearch/json?query=TEST_QUERY&key=API_KEY':
                    $response = new \HttpDriverResponse(file_get_contents(__DIR__ . '/../../_data/Google/place_text_search_response.json'));
                    $response->httpCode = 200;

                    return $response;

                case 'https://maps.googleapis.com/maps/api/place/details/json?place_id=PLACE_ID&key=API_KEY':
                    $response = new \HttpDriverResponse(file_get_contents(__DIR__ . '/../../_data/Google/place_details.json'));
                    $response->httpCode = 200;

                    return $response;

                case 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input=TEST_INPUT&location=0%2C0&radius=20000000&key=API_KEY':
                    $response = new \HttpDriverResponse(file_get_contents(__DIR__ . '/../../_data/Google/place_autocomplete_response.json'));
                    $response->httpCode = 200;

                    return $response;

                case 'https://maps.googleapis.com/maps/api/timezone/json?location=100.1%2C200.2&timestamp=' . self::TEST_TIMESTAMP . '&key=API_KEY':
                    $response = new \HttpDriverResponse(file_get_contents(__DIR__ . '/../../_data/Google/time_zone_response.json'));
                    $response->httpCode = 200;

                    return $response;

                case 'https://maps.googleapis.com/maps/api/geocode/json?address=TEST_ADDRESS&language=en&key=API_KEY':
                    $response = new \HttpDriverResponse(file_get_contents(__DIR__ . '/../../_data/Google/geocode_query_response.json'));
                    $response->httpCode = 200;

                    return $response;

                default:
                    $this->fail("Unexpected URL $request->url");
            }
        }]);
        /** @var \Memcached memcached */
        $this->memcached = $this->makeEmpty(\Memcached::class);
        $this->googleApi = new GoogleApi($this->httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
    }

    public function testPlaceTextSearch()
    {
        $parameters = PlaceTextSearchParameters::makeFromQuery('TEST_QUERY');
        $placeResponse = $this->googleApi->placeTextSearch($parameters);
        $this->assertEquals('Valentine, NE 69201, USA', $placeResponse->getResults()[0]->getFormattedAddress());
    }

    public function testPlaceTextSearchCacheHit()
    {
        /** @var \Memcached $memcached */
        $memcached = $this->makeEmpty(\Memcached::class, [
            'get' => Stub::atLeastOnce(function () {
                return file_get_contents(__DIR__ . '/../../_data/Google/place_text_search_response.json');
            }),
        ], $this);
        /** @var \HttpDriverInterface $httpDriver */
        $httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => Stub::never()], $this);
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceTextSearchParameters::makeFromQuery('CACHE');
        $placeResponse = $googleApi->placeTextSearch($parameters);
        $this->assertEquals('Valentine, NE 69201, USA', $placeResponse->getResults()[0]->getFormattedAddress());
    }

    public function testPlaceTextSearchConnectionError()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceSearchResponse::class, 'CONNECTION_FAIL');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceTextSearchParameters::makeFromQuery('TEST_QUERY');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeTextSearch($parameters);
    }

    public function testPlaceTextSearchGibberishResponse()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceSearchResponse::class, 'GIBBERISH_RESPONSE');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceTextSearchParameters::makeFromQuery('TEST_QUERY');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeTextSearch($parameters);
    }

    public function testPlaceTextSearchZeroResults()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceSearchResponse::class, 'ZERO_RESULTS');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceTextSearchParameters::makeFromQuery('TEST_QUERY');
        $placeTextSearch = $googleApi->placeTextSearch($parameters);
        $this->assertEmpty($placeTextSearch->getResults());
    }

    public function testPlaceTextSearchOverQueryLimit()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceSearchResponse::class, 'OVER_QUERY_LIMIT');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceTextSearchParameters::makeFromQuery('TEST_QUERY');
        $this->expectException(GoogleRequestLimitReachedException::class);
        $googleApi->placeTextSearch($parameters);
    }

    public function testPlaceTextSearchRequestDenied()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceSearchResponse::class, 'REQUEST_DENIED');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceTextSearchParameters::makeFromQuery('TEST_QUERY');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeTextSearch($parameters);
    }

    public function testPlaceTextSearchInvalidRequest()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceSearchResponse::class, 'INVALID_REQUEST');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceTextSearchParameters::makeFromQuery('TEST_QUERY');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeTextSearch($parameters);
    }

    public function testPlaceDetails()
    {
        $parameters = PlaceDetailsParameters::makeFromPlaceId('PLACE_ID');
        $placeDetailsResponse = $this->googleApi->placeDetails($parameters);
        $this->assertEquals('Valentine, NE 69201, USA', $placeDetailsResponse->getResult()->getFormattedAddress());
    }

    public function testPlaceDetailsCacheHit()
    {
        /** @var \Memcached $memcached */
        $memcached = $this->makeEmpty(\Memcached::class, [
            'get' => Stub::atLeastOnce(function () {
                return file_get_contents(__DIR__ . '/../../_data/Google/place_details.json');
            }),
        ], $this);
        /** @var \HttpDriverInterface $httpDriver */
        $httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => Stub::never()], $this);
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceDetailsParameters::makeFromPlaceId('PLACE_ID');
        $placeDetailsResponse = $googleApi->placeDetails($parameters);
        $this->assertEquals('Valentine, NE 69201, USA', $placeDetailsResponse->getResult()->getFormattedAddress());
    }

    public function testPlaceDetailsConnectionError()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceDetailsResponse::class, 'CONNECTION_FAIL');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceDetailsParameters::makeFromPlaceId('PLACE_ID');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeDetails($parameters);
    }

    public function testPlaceDetailsGibberishResponse()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceDetailsResponse::class, 'GIBBERISH_RESPONSE');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceDetailsParameters::makeFromPlaceId('PLACE_ID');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeDetails($parameters);
    }

    public function testPlaceDetailsUnknownError()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceDetailsResponse::class, 'UNKNOWN_ERROR');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceDetailsParameters::makeFromPlaceId('PLACE_ID');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeDetails($parameters);
    }

    public function testPlaceDetailsZeroResults()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceDetailsResponse::class, 'ZERO_RESULTS');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceDetailsParameters::makeFromPlaceId('PLACE_ID');
        $placeDetailsResponse = $googleApi->placeDetails($parameters);
        $this->assertNull($placeDetailsResponse->getResult());
    }

    public function testPlaceDetailsOverQueryLimit()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceDetailsResponse::class, 'OVER_QUERY_LIMIT');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceDetailsParameters::makeFromPlaceId('PLACE_ID');
        $this->expectException(GoogleRequestLimitReachedException::class);
        $googleApi->placeDetails($parameters);
    }

    public function testPlaceDetailsRequestDenied()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceDetailsResponse::class, 'REQUEST_DENIED');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceDetailsParameters::makeFromPlaceId('PLACE_ID');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeDetails($parameters);
    }

    public function testPlaceDetailsInvalidRequest()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceDetailsResponse::class, 'INVALID_REQUEST');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceDetailsParameters::makeFromPlaceId('PLACE_ID');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeDetails($parameters);
    }

    public function testPlaceAutocomplete()
    {
        $parameters = PlaceAutocompleteParameters::makeFromInput('TEST_INPUT');
        $autocompleteResponse = $this->googleApi->placeAutocomplete($parameters);
        $this->assertEquals('Valentine, NE 69201, USA', $autocompleteResponse->getPredictions()[0]->getDescription());
    }

    public function testPlaceAutocompleteCacheHit()
    {
        /** @var \Memcached $memcached */
        $memcached = $this->makeEmpty(\Memcached::class, [
            'get' => Stub::atLeastOnce(function () {
                return file_get_contents(__DIR__ . '/../../_data/Google/place_autocomplete_response.json');
            }),
        ], $this);
        /** @var \HttpDriverInterface $httpDriver */
        $httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => Stub::never()], $this);
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceAutocompleteParameters::makeFromInput('TEST_INPUT');
        $autocompleteResponse = $googleApi->placeAutocomplete($parameters);
        $this->assertEquals('Valentine, NE 69201, USA', $autocompleteResponse->getPredictions()[0]->getDescription());
    }

    public function testPlaceAutocompleteConnectionError()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceAutocompleteResponse::class, 'CONNECTION_FAIL');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceAutocompleteParameters::makeFromInput('TEST_INPUT');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeAutocomplete($parameters);
    }

    public function testPlaceAutocompleteGibberishResponse()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceAutocompleteResponse::class, 'GIBBERISH_RESPONSE');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceAutocompleteParameters::makeFromInput('TEST_INPUT');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeAutocomplete($parameters);
    }

    public function testPlaceAutocompleteZeroResults()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceAutocompleteResponse::class, 'ZERO_RESULTS');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceAutocompleteParameters::makeFromInput('TEST_INPUT');
        $autocompleteResponse = $googleApi->placeAutocomplete($parameters);
        $this->assertEmpty($autocompleteResponse->getPredictions());
    }

    public function testPlaceAutocompleteOverQueryLimit()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceAutocompleteResponse::class, 'OVER_QUERY_LIMIT');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceAutocompleteParameters::makeFromInput('TEST_INPUT');
        $this->expectException(GoogleRequestLimitReachedException::class);
        $googleApi->placeAutocomplete($parameters);
    }

    public function testPlaceAutocompleteRequestDenied()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceAutocompleteResponse::class, 'REQUEST_DENIED');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceAutocompleteParameters::makeFromInput('TEST_INPUT');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeAutocomplete($parameters);
    }

    public function testPlaceAutocompleteInvalidRequest()
    {
        $httpDriver = $this->getRiggedHttpDriver(PlaceAutocompleteResponse::class, 'INVALID_REQUEST');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = PlaceAutocompleteParameters::makeFromInput('TEST_INPUT');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->placeAutocomplete($parameters);
    }

    public function testTimeZone()
    {
        $parameters = TimeZoneParameters::makeFromLatLng(new LatLng(100.1, 200.2));
        $parameters->setDateTime(new \DateTime(date(\DateTime::ISO8601, self::TEST_TIMESTAMP)));
        $timeZone = $this->googleApi->timeZone($parameters);
        $this->assertEquals('America/Chicago', $timeZone->getTimeZoneId());
    }

    public function testTimeZoneCacheHit()
    {
        /** @var \Memcached $memcached */
        $memcached = $this->makeEmpty(\Memcached::class, [
            'get' => Stub::atLeastOnce(function () {
                return file_get_contents(__DIR__ . '/../../_data/Google/time_zone_response.json');
            }),
        ], $this);
        /** @var \HttpDriverInterface $httpDriver */
        $httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => Stub::never()], $this);
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = TimeZoneParameters::makeFromLatLng(new LatLng(100.1, 200.2));
        $parameters->setDateTime(new \DateTime(date(\DateTime::ISO8601, self::TEST_TIMESTAMP)));
        $timeZone = $googleApi->timeZone($parameters);
        $this->assertEquals('America/Chicago', $timeZone->getTimeZoneId());
    }

    public function testTimeZoneConnectionError()
    {
        $httpDriver = $this->getRiggedHttpDriver(TimeZoneResponse::class, 'CONNECTION_FAIL');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = TimeZoneParameters::makeFromLatLng(new LatLng(100.1, 200.2));
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->timeZone($parameters);
    }

    public function testTimeZoneGibberishResponse()
    {
        $httpDriver = $this->getRiggedHttpDriver(TimeZoneResponse::class, 'GIBBERISH_RESPONSE');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = TimeZoneParameters::makeFromLatLng(new LatLng(100.1, 200.2));
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->timeZone($parameters);
    }

    public function testTimeZoneZeroResults()
    {
        $httpDriver = $this->getRiggedHttpDriver(TimeZoneResponse::class, 'ZERO_RESULTS');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = TimeZoneParameters::makeFromLatLng(new LatLng(100.1, 200.2));
        $timeZoneResponse = $googleApi->timeZone($parameters);
        $this->assertNull($timeZoneResponse->getTimeZoneId());
    }

    public function testTimeZoneOverQueryLimit()
    {
        $httpDriver = $this->getRiggedHttpDriver(TimeZoneResponse::class, 'OVER_QUERY_LIMIT');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = TimeZoneParameters::makeFromLatLng(new LatLng(100.1, 200.2));
        $this->expectException(GoogleRequestLimitReachedException::class);
        $googleApi->timeZone($parameters);
    }

    public function testTimeZoneRequestDenied()
    {
        $httpDriver = $this->getRiggedHttpDriver(TimeZoneResponse::class, 'REQUEST_DENIED');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = TimeZoneParameters::makeFromLatLng(new LatLng(100.1, 200.2));
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->timeZone($parameters);
    }

    public function testTimeZoneInvalidRequest()
    {
        $httpDriver = $this->getRiggedHttpDriver(TimeZoneResponse::class, 'INVALID_REQUEST');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = TimeZoneParameters::makeFromLatLng(new LatLng(100.1, 200.2));
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->timeZone($parameters);
    }

    public function testGeoCode()
    {
        $parameters = GeoCodeParameters::makeFromAddress('TEST_ADDRESS');
        $geoCodeResponse = $this->googleApi->geoCode($parameters);
        $this->assertEquals('Valentine, NE 69201, USA', $geoCodeResponse->getResults()[0]->getFormattedAddress());
    }

    public function testGeoCodeCacheHit()
    {
        /** @var \Memcached $memcached */
        $memcached = $this->makeEmpty(\Memcached::class, [
            'get' => Stub::atLeastOnce(function () {
                return file_get_contents(__DIR__ . '/../../_data/Google/geocode_query_response.json');
            }),
        ], $this);
        /** @var \HttpDriverInterface $httpDriver */
        $httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => Stub::never()], $this);
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = GeoCodeParameters::makeFromAddress('TEST_ADDRESS');
        $geoCodeResponse = $googleApi->geoCode($parameters);
        $this->assertEquals('Valentine, NE 69201, USA', $geoCodeResponse->getResults()[0]->getFormattedAddress());
    }

    public function testGeoCodeConnectionError()
    {
        $httpDriver = $this->getRiggedHttpDriver(GeoCodeResponse::class, 'CONNECTION_FAIL');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = GeoCodeParameters::makeFromAddress('TEST_ADDRESS');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->geoCode($parameters);
    }

    public function testGeoCodeGibberishResponse()
    {
        $httpDriver = $this->getRiggedHttpDriver(GeoCodeResponse::class, 'GIBBERISH_RESPONSE');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = GeoCodeParameters::makeFromAddress('TEST_ADDRESS');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->geoCode($parameters);
    }

    public function testGeoCodeUnknownError()
    {
        $httpDriver = $this->getRiggedHttpDriver(GeoCodeResponse::class, 'UNKNOWN_ERROR');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = GeoCodeParameters::makeFromAddress('TEST_ADDRESS');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->geoCode($parameters);
    }

    public function testGeoCodeZeroResults()
    {
        $httpDriver = $this->getRiggedHttpDriver(GeoCodeResponse::class, 'ZERO_RESULTS');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = GeoCodeParameters::makeFromAddress('TEST_ADDRESS');
        $geoCodeResponse = $googleApi->geoCode($parameters);
        $this->assertEmpty($geoCodeResponse->getResults());
    }

    public function testGeoCodeOverQueryLimit()
    {
        $httpDriver = $this->getRiggedHttpDriver(GeoCodeResponse::class, 'OVER_QUERY_LIMIT');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = GeoCodeParameters::makeFromAddress('TEST_ADDRESS');
        $this->expectException(GoogleRequestLimitReachedException::class);
        $googleApi->geoCode($parameters);
    }

    public function testGeoCodeRequestDenied()
    {
        $httpDriver = $this->getRiggedHttpDriver(GeoCodeResponse::class, 'REQUEST_DENIED');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = GeoCodeParameters::makeFromAddress('TEST_ADDRESS');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->geoCode($parameters);
    }

    public function testGeoCodeInvalidRequest()
    {
        $httpDriver = $this->getRiggedHttpDriver(GeoCodeResponse::class, 'INVALID_REQUEST');
        $googleApi = new GoogleApi($httpDriver, $this->serializer, $this->memcached, 'API_KEY', new NullLogger(), new NullLogger());
        $parameters = GeoCodeParameters::makeFromAddress('TEST_ADDRESS');
        $this->expectException(GoogleRequestFailedException::class);
        $googleApi->geoCode($parameters);
    }

    private function getRiggedHttpDriver(string $responseClass, string $status): \HttpDriverInterface
    {
        if ('CONNECTION_FAIL' === $status) {
            $response = new \HttpDriverResponse('Connection fail');
            $response->httpCode = 0;
            $response->errorCode = 1;
            $response->errorMessage = 'ERROR_MESSAGE';
            /** @var \HttpDriverInterface $httpDriver */
            $httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => $response]);

            return $httpDriver;
        }

        if ('GIBBERISH_RESPONSE' === $status) {
            $response = new \HttpDriverResponse('Gibberish response');
            $response->httpCode = 200;
            /** @var \HttpDriverInterface $httpDriver */
            $httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => $response]);

            return $httpDriver;
        }
        $googleResponse = new $responseClass($status);
        $httpDriverResponse = new \HttpDriverResponse($this->serializer->serialize($googleResponse, 'json'));
        $httpDriverResponse->httpCode = 200;
        /** @var \HttpDriverInterface $httpDriver */
        $httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => $httpDriverResponse]);

        return $httpDriver;
    }
}
