<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Query\AirportQuery;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AirportController.
 *
 * @Route("/airport")
 */
class AirportController extends AbstractController
{
    use JsonTrait;

    public const MAX_FIND_RESULTS = 10;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/find", name="awm_find_airport")
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     */
    public function findAction(Request $request, AirportQuery $airportQuery, SerializerInterface $serializer)
    {
        $query = \is_string($query0 = $request->request->get('query')) ? $query0 : '';

        if (strlen($query) < 2) {
            return $this->jsonResponse([]);
        }
        $airports = $airportQuery->findAircodeByQuery($query, self::MAX_FIND_RESULTS);

        return new Response($serializer->serialize($airports, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}
