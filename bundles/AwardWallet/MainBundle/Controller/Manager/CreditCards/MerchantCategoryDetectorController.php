<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use AwardWallet\Common\Monolog\Handler\ArrayHandler;
use AwardWallet\MainBundle\Service\CreditCards\MerchantCategoryDetector;
use Doctrine\DBAL\Connection;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class MerchantCategoryDetectorController
{
    private Connection $connection;

    private Logger $logger;
    private MerchantCategoryDetector $merchantCategoryDetector;

    public function __construct(Connection $connection, Logger $logger, MerchantCategoryDetector $merchantCategoryDetector)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->merchantCategoryDetector = $merchantCategoryDetector;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_MERCHANT')")
     * @Route("/manager/credit-cards/merchant-category-detector-test", name="aw_merchant_category_detector_test")
     */
    public function testAction(FormFactoryInterface $formFactory, Request $request, Environment $twig)
    {
        $builder = $formFactory->createBuilder();
        $builder->add('MerchantID', TextType::class, ['label' => 'MerchantID']);
        $builder->add('Check', SubmitType::class);

        $form = $builder->getForm();
        $form->handleRequest($request);
        $logs = new ArrayHandler();
        $logs->setFormatter(new LineFormatter());

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->logger->pushHandler($logs);

            try {
                $this->detectMerchant($data['MerchantID']);
            } finally {
                $this->logger->popHandler();
            }
        }

        return new Response($twig->render("@AwardWalletMain/Manager/CreditCards/merchantCategoryDetectorTest.html.twig", [
            "form" => $form->createView(),
            "logs" => $logs->getRecords(),
        ]));
    }

    private function detectMerchant(int $merchantId): void
    {
        $transactions = $this->connection->fetchOne("select Transactions from Merchant where MerchantID = ?", [$merchantId]);

        if ($transactions === false) {
            $this->logger->warning("merchant not found");

            return;
        }

        if ($transactions === 0 || $transactions === null) {
            $this->logger->warning("no transactions for this merchant");

            return;
        }

        $this->logger->info("detecting merchant category for: {$merchantId}");
        $this->merchantCategoryDetector->detectCategory($merchantId, $transactions, true);
    }
}
