<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class GrabFingerprintsController
{
    /**
     * @Security("is_granted('ROLE_STAFF')")
     * @Route("/manager/grab-fingerprints")
     */
    public function grabAction(Environment $twig)
    {
        return new Response($twig->render('@AwardWalletMain/Manager/grabFingerprints.html.twig', [
            'title' => 'Grab Browser Fingerprints',
        ]));
    }
}
