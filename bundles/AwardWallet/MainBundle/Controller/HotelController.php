<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/hotel")
 */
class HotelController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/address_by_name/{name}")
     * @return string|null
     * TODO троттлить
     */
    public function findHotelAddressAction($name, GoogleGeo $googleGeo, AwTokenStorageInterface $tokenStorage, SerializerInterface $jmsSerializer)
    {
        $places = $googleGeo->textSearch($name, [
            'type' => 'lodging',
            'language' => $tokenStorage->getUser()->getLanguage(),
        ]);

        return new Response($jmsSerializer->serialize($places, 'json'), Response::HTTP_OK, ['Content-type' => 'application/json']);
    }
}
