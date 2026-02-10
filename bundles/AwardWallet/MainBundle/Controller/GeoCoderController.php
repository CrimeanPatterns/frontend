<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Service\ReverseGeoCoder;
use JMS\Serializer\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/geo_coder")
 */
class GeoCoderController extends AbstractController
{
    /**
     * @Route("/query/{query}", name="geo_coder_query", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function queryAction(
        string $query,
        GoogleGeo $geoCoder,
        Serializer $jmsSerializer
    ) {
        $geoTag = $geoCoder->findGeoTagEntity(urldecode($query), null, 0, true);

        return new Response($jmsSerializer->serialize($geoTag, 'json'));
    }

    /**
     * @Route("/reverse_query/{lat}/{lng}", name="geo_coder_reverse_query", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function reverseQueryAction(
        float $lat,
        float $lng,
        ReverseGeoCoder $reverseGeoCoder,
        Serializer $jmsSerializer
    ) {
        $geoTag = $reverseGeoCoder->reverseQuery($lat, $lng);

        return new Response($jmsSerializer->serialize($geoTag, 'json'));
    }
}
