<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Service\FlightSearch\PlaceQuery;
use AwardWallet\MainBundle\Service\FlightSearch\SearchProcess;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class FlightSearchToolController extends AbstractController
{
    /**
     * @Route("/flight-search", name="aw_flight_search", options={"expose"=true})
     * @Security("is_granted('ROLE_STAFF')")
     * @Template("@AwardWalletMain/FlightSearch/index.html.twig")
     */
    public function indexAction(
        Request $request,
        SerializerInterface $serializer,
        SearchProcess $searchProcess,
        Environment $twig
    ) {
        // disabled because of too slow sql query, searching milevalue by airport countries,
        // we should denormalize milevlaue, add depcountry, arrcountry and index
        return new Response('disabled, not ready yet');

        $twig->addGlobal('webpack', true);

        $paramsProcess = $searchProcess->createParamsProcess(
            $request->query->get('from', ''),
            $request->query->get('to', ''),
            $request->query->get('type', ''),
            $request->query->get('class', '')
        );

        return [
            'data' => $serializer->serialize($searchProcess->process($paramsProcess), 'json'),
        ];
    }

    /**
     * @Route("/flight-search-place", name="aw_flight_search_place", options={"expose"=true})
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function placeSearchAction(
        Request $request,
        PlaceQuery $placeQuery
    ): JsonResponse {
        $query = $request->query->get('query');

        if (false !== strpos($query, ',')) {
            $query = explode(',', $query)[0];
        }
        $result = $placeQuery->byAll($query);

        return new JsonResponse($result);
    }
}
