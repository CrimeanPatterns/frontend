<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\Common\Geo\Google\GeoCodeParameters;
use AwardWallet\Common\Geo\Google\GeoTag;
use AwardWallet\Common\Geo\Google\GoogleApi;
use AwardWallet\Common\Geo\Google\GoogleRequestFailedException;
use AwardWallet\Common\Geo\Google\PlaceAutocompleteParameters;
use AwardWallet\Common\Geo\Google\PlaceDetailsParameters;
use AwardWallet\Common\Geo\Google\PlaceTextSearchParameters;
use AwardWallet\Common\Geo\Google\Prediction;
use AwardWallet\MainBundle\Entity\Query\AirportQuery;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/google")
 */
class GooglePlacesController extends AbstractController
{
    private GoogleApi $googleApi;

    private SerializerInterface $serializer;

    private AntiBruteforceLockerService $securityAntibruteforceGoogleRequest;

    private bool $isDev;

    public function __construct(
        GoogleApi $googleApi,
        SerializerInterface $serializer,
        AntiBruteforceLockerService $securityAntibruteforceGoogleRequest,
        string $env
    ) {
        $this->googleApi = $googleApi;
        $this->serializer = $serializer;
        $this->securityAntibruteforceGoogleRequest = $securityAntibruteforceGoogleRequest;
        $this->isDev = $env !== 'prod';
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/hotels/{query}", name="google_hotels", options={"expose"=true})
     */
    public function hotelsAction(Request $request, string $query)
    {
        $error = $this->checkLockout($request);

        if (!empty($error)) {
            return $this->json($error, Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $this->placesSearchByType($query, 'lodging');
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/car_rentals/{query}", name="google_car_rentals", options={"expose"=true})
     */
    public function carRentalsAction(Request $request, string $query)
    {
        $error = $this->checkLockout($request);

        if (!empty($error)) {
            return $this->json($error, Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $this->placesSearchByType($query, 'car_rental');
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/bus_stations/{query}", name="google_bus_stations", options={"expose"=true})
     */
    public function busStationsAction(Request $request, string $query)
    {
        $error = $this->checkLockout($request);

        if (!empty($error)) {
            return $this->json($error, Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $this->placesSearchByType($query, 'bus_station');
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/train_station/{query}", name="google_train_station", options={"expose"=true})
     */
    public function trainStationsAction(Request $request, string $query)
    {
        $error = $this->checkLockout($request);

        if (!empty($error)) {
            return $this->json($error, Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $this->placesSearchByType($query, 'train_station');
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/transit_station/{query}", name="google_transit_station", options={"expose"=true})
     */
    public function transitStationsAction(Request $request, string $query)
    {
        $error = $this->checkLockout($request);

        if (!empty($error)) {
            return $this->json($error, Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $this->placesSearchByType($query, 'transit_station');
    }

    /**
     * @Route("/geo_code/{query}", name="google_geo_code", options={"expose"=true})
     */
    public function geoCodeAction(Request $request, string $query)
    {
        $error = $this->checkLockout($request);

        if (!empty($error)) {
            return $this->json($error, Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (empty($query)) {
            return $this->json([]);
        }
        $parameters = GeoCodeParameters::makeFromAddress(urldecode($query));
        /** @var Usr $user */
        $user = $this->getUser();

        if ($user instanceof UserInterface) {
            $parameters->setLanguage($user->getLanguage());
        }

        try {
            $geoCodeResponse = $this->googleApi->geoCode($parameters);
        } catch (GoogleRequestFailedException $e) {
            if ($this->isDev) {
                return $this->json([]);
            }

            throw $e;
        }

        return new Response(
            $this->serializer->serialize($geoCodeResponse->getResults(), 'json'),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/geo_code_airports/{query}", name="google_geo_code_airports", options={"expose"=true})
     */
    public function geoCodeWithAirportsAction(
        Request $request,
        string $query,
        AirportQuery $airportQuery
    ) {
        $error = $this->checkLockout($request);

        if (!empty($error)) {
            return $this->json($error, Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (empty($query)) {
            return $this->json([]);
        }

        /** @var Usr $user */
        $user = $this->getUser();
        $parameters = GeoCodeParameters::makeFromAddress(urldecode($query));
        $parameters->setLanguage($user->getLanguage());

        try {
            $geoCodeResponse = $this->googleApi->geoCode($parameters);
        } catch (GoogleRequestFailedException $e) {
            if ($this->isDev) {
                return $this->json([]);
            }

            throw $e;
        }

        /** @var Aircode[] $airports */
        $airports = $airportQuery->findAircodeByQuery(urldecode($query));

        $resultSet = array_map(function (Aircode $airport) {
            return ['formatted_address' => $airport->getFormattedName(), 'lat' => $airport->getLat(), 'lng' => $airport->getLng()];
        }, $airports);
        $resultSet += array_map(function (GeoTag $tag) {
            $location = $tag->getGeometry()->getLocation();

            return ['formatted_address' => $tag->getFormattedAddress(), 'lat' => $location->getLat(), 'lng' => $location->getLng()];
        }, $geoCodeResponse->getResults());
        $resultSet = array_slice($resultSet, 0, 10);

        return $this->json($resultSet);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/place_details/{placeId}", name="google_place_details", options={"expose"=true})
     */
    public function placeDetailsAction(Request $request, string $placeId): Response
    {
        $error = $this->checkLockout($request);

        if (!empty($error)) {
            return $this->json($error, Response::HTTP_TOO_MANY_REQUESTS);
        }
        $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);

        try {
            $details = $this->googleApi->placeDetails($parameters);
        } catch (GoogleRequestFailedException $e) {
            if ($this->isDev) {
                return $this->json([]);
            }

            throw $e;
        }

        return new Response(
            $this->serializer->serialize($details->getResult(), 'json'),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/location_autocomplete/{query}", name="google_location_autocomplete", options={"expose"=true}, requirements={"query"=".+"})
     */
    public function locationAutocompleteAction(
        Request $request,
        AwTokenStorageInterface $tokenStorage,
        string $query
    ): Response {
        $error = $this->checkLockout($request);

        if (!empty($error)) {
            return $this->json($error, Response::HTTP_TOO_MANY_REQUESTS);
        }

        $query = trim($query);

        if (empty($query) || strlen($query) < 2) {
            return $this->json([]);
        }

        $user = $tokenStorage->getUser();
        $parameters = PlaceAutocompleteParameters::makeFromInput(urldecode($query));
        $parameters->setLanguage($user->getLanguage());
        $parameters->setTypes(['geocode']);

        try {
            $placesResponse = $this->googleApi->placeAutocomplete($parameters);
        } catch (GoogleRequestFailedException $e) {
            if ($this->isDev) {
                return $this->json([]);
            }

            throw $e;
        }

        return new Response(
            $this->serializer->serialize(
                array_values(array_filter(
                    $placesResponse->getPredictions(),
                    function (Prediction $prediction) {
                        // administrative_area_level_1 is a state, we don't need it in the autocomplete
                        return !in_array('administrative_area_level_1', $prediction->getTypes(), true);
                    }
                )),
                'json'
            ),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );
    }

    private function placesSearchByType(string $query, string $type): Response
    {
        /** @var Usr $user */
        $user = $this->getUser();
        $parameters = PlaceTextSearchParameters::makeFromQuery(urldecode($query));
        $parameters->setType($type);
        $parameters->setLanguage($user->getLanguage());

        try {
            $placesResponse = $this->googleApi->placeTextSearch($parameters);
        } catch (GoogleRequestFailedException $e) {
            if ($this->isDev) {
                return $this->json([]);
            }

            throw $e;
        }

        return new Response(
            $this->serializer->serialize($placesResponse->getResults(), 'json'),
            Response::HTTP_OK,
            ['Content-type' => 'application/json']
        );
    }

    private function checkLockout(Request $request): ?string
    {
        return $this->securityAntibruteforceGoogleRequest->checkForLockout($request->getClientIp());
    }
}
