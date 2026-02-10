<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use AwardWallet\Common\Monolog\Handler\ArrayHandler;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
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

class MerchantMatcherController
{
    private Connection $connection;

    private Logger $logger;

    private MerchantMatcher $merchantMatcher;

    public function __construct(Connection $connection, Logger $logger, MerchantMatcher $merchantMatcher)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->merchantMatcher = $merchantMatcher;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_MERCHANT')")
     * @Route("/manager/credit-cards/merchant-matcher-test", name="aw_merchant_matcher_test")
     */
    public function testAction(FormFactoryInterface $formFactory, Request $request, Environment $twig)
    {
        $builder = $formFactory->createBuilder();
        $builder->add('Description', TextType::class, ["required" => false, 'allow_urls' => true]);
        $builder->add('ShoppingCategoryID', TextType::class, ["required" => false, 'label' => 'ShoppingCategoryID']);
        $builder->add('HistoryUUID', TextType::class, ["required" => false, 'label' => 'HistoryUUID']);
        $builder->add('Check', SubmitType::class);

        $form = $builder->getForm();
        $form->handleRequest($request);
        $logs = new ArrayHandler();
        $logs->setFormatter(new LineFormatter());

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->logger->pushHandler($logs);

            try {
                $this->findMerchant($data);
            } finally {
                $this->logger->popHandler();
            }
        }

        return new Response($twig->render("@AwardWalletMain/Manager/CreditCards/merchantMatcherTest.html.twig", [
            "form" => $form->createView(),
            "logs" => $logs->getRecords(),
        ]));
    }

    private function loadAccountHistoryRow(string $uuid): ?array
    {
        return $this->connection->fetchAssociative("select Description, ShoppingCategoryID from AccountHistory where UUID = ?", [$uuid]) ?: null;
    }

    private function findMerchant(array $data): void
    {
        if ($data['HistoryUUID'] !== null) {
            $historyRow = $this->loadAccountHistoryRow($data['HistoryUUID']);

            if ($historyRow === null) {
                $this->logger->warning("history row {$data['HistoryUUID']} not found");

                return;
            }

            $data = array_merge($data, $historyRow);
        }

        $catInfo = $this->connection->executeQuery("select sc.Name as CatName, sc.ShoppingCategoryGroupID, scg.Name as GroupName
        from ShoppingCategory sc left join ShoppingCategoryGroup scg on sc.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
        where sc.ShoppingCategoryID = ?", [$data['ShoppingCategoryID']])->fetchAssociative();

        if ($catInfo === false) {
            $catInfo = ['CatName' => 'None', 'ShoppingCategoryGroupID' => '', 'GroupName' => 'None'];
        }

        $this->logger->info("searching merchant for: {$data['Description']}, ShoppingCategoryID: {$data['ShoppingCategoryID']} {$catInfo['CatName']}, group: {$catInfo['ShoppingCategoryGroupID']} {$catInfo['GroupName']}");

        $merchantId = $this->merchantMatcher->identify(
            $data['Description'],
            $data['ShoppingCategoryID'],
            true,
            true,
            false,
            false
        );
        $this->logger->info(
            "identified as merchant: "
            . (MerchantMatcher::VIRTUAL_MERCHANT_ID === $merchantId ? '(SHOULD BE INSERTED WHEN PROCESSED)' : $merchantId)
            . ' '
            . $this->connection->executeQuery("select Name from Merchant where MerchantID = ?", [$merchantId])->fetchOne()
        );
    }
}
