<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Service\Charts\QsTransactionChart;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChartsController extends AbstractController
{
    private array $headers = [
        'Content-Type' => 'image/png',
    ];

    /**
     * @Route("/charts/clicks", name="aw_charts_qs_clicks")
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function chartClickAction(QsTransactionChart $chart): Response
    {
        $graph = $chart->getClicksGraph();

        if (empty($graph)) {
            return $this->emptyData();
        }

        return new Response($graph->Stroke(), Response::HTTP_OK, $this->headers);
    }

    /**
     * @Route("/charts/revenue", name="aw_charts_qs_revenue")
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function chartRevenueAction(QsTransactionChart $chart): Response
    {
        $graph = $chart->getRevenueGraph();

        if (empty($graph)) {
            return $this->emptyData();
        }

        return new Response($graph->Stroke(), Response::HTTP_OK, $this->headers);
    }

    /**
     * @Route("/charts/cards", name="aw_charts_qs_cards")
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function chartCardsAction(QsTransactionChart $chart): Response
    {
        $graph = $chart->getCardsGraph();

        if (empty($graph)) {
            return $this->emptyData();
        }

        return new Response($graph->Stroke(), Response::HTTP_OK, $this->headers);
    }

    private function emptyData()
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
