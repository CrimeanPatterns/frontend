<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use JMS\Serializer\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AirlineController.
 *
 * @Route("/airline")
 */
class AirlineController extends AbstractController
{
    public const MAX_FIND_RESULTS = 10;

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/find/{query}", name="find_airline", options={"expose"=true})
     */
    public function findAction(
        $query,
        TranslatorInterface $translator,
        AirlineRepository $airlineRepository,
        Serializer $jmsSerializer
    ) {
        if (strlen($query) < 2) {
            throw new BadRequestHttpException($translator->trans('minlength', ['%limit%' => 2, '%count%' => 2], 'validators'));
        }

        $query = $airlineRepository->createQueryBuilder('airline')
            ->where('airline.name LIKE :query')
            ->orWhere('airline.code LIKE :query')
            ->orWhere('airline.icao LIKE :query')
            ->setParameter(':query', '%' . $query . '%')
            ->setMaxResults(self::MAX_FIND_RESULTS)
            ->getQuery();

        return new Response(
            $jmsSerializer->serialize($query->getResult(), 'json'),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
