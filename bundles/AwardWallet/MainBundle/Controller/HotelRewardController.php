<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\Listeners\CustomHeadersListener;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch\HotelProvider;
use AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch\HotelSearch;
use AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch\PlaceParser;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class HotelRewardController extends AbstractController
{
    private AntiBruteforceLockerService $antiBruteforceLockerService;

    public function __construct(
        AntiBruteforceLockerService $antiBruteforceLockerService
    ) {
        $this->antiBruteforceLockerService = $antiBruteforceLockerService;
    }

    /**
     * @Route("/hotel-reward-search", name="aw_hotelreward_index", options={"expose"=true}, defaults={"_canonical"="aw_hotelreward_index", "_withoutLocale"=true})
     * @Route("/{_locale}/hotel-reward-search", name="aw_hotelreward_index_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_hotelreward_index", "_withoutLocale"=true})
     * @Route("/hotel-reward-search/place/{placeName}", name="aw_hotelreward_index_place", options={"expose"=true}, defaults={"_canonical"="aw_hotelreward_index", "_withoutLocale"=true})
     */
    public function indexAction(
        Environment $twigEnv,
        HotelProvider $hotelProvider,
        SerializerInterface $serializer,
        Request $request,
        PageVisitLogger $pageVisitLogger
    ) {
        $twigEnv->addGlobal('webpack', true);

        $response = new Response($twigEnv->render('@AwardWalletMain/HotelReward/index.html.twig', [
            'primaryHotelList' => $serializer->serialize($hotelProvider->getAll(5), 'json'),
        ]));

        if (!empty($request->query->all())) {
            $response->headers->set('X-Robots-Tag', CustomHeadersListener::XROBOTSTAG_NOINDEX);
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_AWARD_HOTEL_RESEARCH_TOOL);

        return $response;
    }

    /**
     * @Route("/hotel-reward-search/geo/{query}", name="aw_hotelreward_geo", options={"expose"=true})
     */
    public function searchPlaceAction(
        Request $request,
        SerializerInterface $serializer,
        string $query = 'New York'
    ) {
        $response = $this->forward('AwardWallet\MainBundle\Controller\GooglePlacesController::geoCodeAction', [
            'request' => $request,
            'query' => $query,
        ]);

        if (!$response->isOk()) {
            return new JsonResponse([]);
        }

        $data = json_decode($response->getContent(), true);
        $remove = ['formatted_address' => ['Asia', 'Europe']];

        foreach ($data as $index => $item) {
            foreach ($remove as $key => $values) {
                foreach ($values as $name) {
                    if ($item[$key] === $name) {
                        unset($data[$index]);
                    }
                }
            }
        }
        $data = array_values($data);

        $mergeAvailable = function ($key, $list) use ($query, &$data) {
            foreach ($list as $id => $name) {
                if (false === stripos($name, $query)) {
                    continue;
                }
                $data[] = [
                    'place_id' => $key . 'Id_' . $id,
                    'formatted_address' => $name,
                    'extend' => [
                        $key . 'Id' => $id,
                    ],
                ];
            }
        };

        $mergeAvailable('continent', HotelSearch::AVAILABLE_CONTINENT);
        $mergeAvailable('region', HotelSearch::AVAILABLE_REGION);

        return new Response(
            $serializer->serialize($data, 'json'),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }

    /**
     * @Route("/hotel-reward-search/hotels", name="aw_hotelreward_place", options={"expose"=true})
     */
    public function placeAction(
        Request $request,
        HotelSearch $hotelSearch,
        PlaceParser $placeParser,
        SerializerInterface $serializer
    ): Response {
        $error = $this->antiBruteforceLockerService->checkForLockout($request->getClientIp());

        if (!empty($error)) {
            return new JsonResponse($error, Response::HTTP_TOO_MANY_REQUESTS);
        }

        $place = $request->query->get('place');

        if (!empty($place['place_id']) && ('place' === $place['place_id'] || array_key_exists('extend', $place))) {
            foreach (HotelSearch::AVAILABLE_CONTINENT as $continentId => $continent) {
                if ($continent === $place['value']) {
                    $place['extend'] = ['continentId' => $continentId];
                }
            }

            foreach (HotelSearch::AVAILABLE_REGION as $regionId => $region) {
                if ($region === $place['value']) {
                    $place['extend'] = ['regionId' => $regionId];
                }
            }
        }

        if (!empty($place['extend'])) {
            unset($place['place_id'], $place['value']);

            if (!empty($place['extend']['continentId'])) {
                $place['extend']['continentId'] = (int) $place['extend']['continentId'];
            }

            if (!empty($place['extend']['regionId'])) {
                $place['extend']['regionId'] = (int) $place['extend']['regionId'];
            }
        }

        if (!empty($place['place_id'])) {
            $placeParserResult = $placeParser->getByPlaceId($place['place_id']);
        }

        if (empty($placeParserResult) && !empty($place['value'])) {
            $placeParserResult = $placeParser->getByPlaceName($place['value']);
        }

        if (empty($placeParserResult) && !empty($place['extend']['continentId'])) {
            $hotels = $hotelSearch->searchByContinentId([(int) $place['extend']['continentId']]);

            if (!empty($hotels)) {
                $result = [
                    'placeId' => 'continent' . $place['extend']['continentId'],
                    'hotels' => $hotels,
                ];
            }
        }

        if (empty($placeParserResult) && !empty($place['extend']['regionId'])) {
            $hotels = $hotelSearch->searchByRegionId($place['extend']['regionId']);

            if (!empty($hotels)) {
                $result = [
                    'placeId' => 'region' . $place['extend']['regionId'],
                    'hotels' => $hotels,
                ];
            }
        }

        if (!empty($result)) {
            return new Response(
                $serializer->serialize($result, 'json'),
                Response::HTTP_OK,
                ['content-type' => 'application/json']
            );
        }

        if (empty($placeParserResult)) {
            return new JsonResponse(['success' => false]);
        }

        $hotels = $hotelSearch->getByPlace($placeParserResult, $place['fragmentName'] ?? '');

        if (empty($hotels)) {
            return new JsonResponse(['notFound' => true]);
        }

        $result = [
            'placeId' => $placeParserResult->getPlaceId(),
            'hotels' => $hotels,
        ];

        return new Response(
            $serializer->serialize($result, 'json'),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
