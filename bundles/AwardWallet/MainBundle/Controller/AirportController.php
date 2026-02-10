<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Query\AirportQuery;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AirportController.
 *
 * @Route("/airport")
 */
class AirportController extends AbstractController
{
    public const MAX_FIND_RESULTS = 10;

    /**
     * @Route("/find/{query}", name="find_airport", options={"expose"=true})
     */
    public function findAction(
        $query,
        TranslatorInterface $translator,
        AirportQuery $airportQuery,
        Serializer $jmsSerializer
    ) {
        if (strlen($query) < 2) {
            throw new BadRequestHttpException($translator->trans('minlength', ['%limit%' => 2, '%count%' => 2], 'validators'));
        }
        $airports = $airportQuery->findAircodeByQuery($query, self::MAX_FIND_RESULTS);

        return new Response(
            $jmsSerializer->serialize($airports, 'json'),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
