<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/awardBooking")
 */
class OldBookingController extends AbstractController
{
    /**
     * @Route("/edit\.php", name="aw_booking_oldbooking_index")
     */
    public function indexAction(Request $request, RouterInterface $router)
    {
        $params = [];

        if ($request->get('ref')) {
            $params['ref'] = $request->get('ref');
        }

        return $this->redirect(
            $router->generate('aw_booking_add_index', $params), 301
        );
    }
}
