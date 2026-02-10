<?php

namespace AwardWallet\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MaintenanceController extends AbstractController
{
    /**
     * @Route("/maintenance", name="aw_maintenance")
     */
    public function maintenanceAction()
    {
        return new Response($this->render('@AwardWalletMain/Maintenance/maintenance.html.twig')->getContent(), 503, ['X-UA-Compatible' => 'IE=8', 'Retry-After' => '60']);
    }
}
