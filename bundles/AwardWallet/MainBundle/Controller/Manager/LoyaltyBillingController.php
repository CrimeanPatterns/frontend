<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Loyalty\Stats\Calculator;
use Aws\S3\S3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class LoyaltyBillingController extends AbstractController
{
    private S3Client $s3Client;
    private Environment $twig;

    public function __construct(S3Client $s3Client, Environment $twig)
    {
        $this->s3Client = $s3Client;
        $this->twig = $twig;
    }

    /**
     * @Route("/manager/loyalty-billing-report")
     */
    public function billingReportAction(): Response
    {
        return new Response($this->twig->render(
            '@AwardWalletMain/Manager/LoyaltyAdmin/billingReport.html.twig',
            [
                'report' => $this->s3Client->getObject([
                    'Bucket' => 'aw-frontend-data',
                    'Key' => Calculator::REPORT_NAME,
                ])['Body'],
            ]
        ));
    }
}
