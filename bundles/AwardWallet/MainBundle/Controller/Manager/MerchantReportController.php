<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Globals\Utils\OutputBufferingUtils;
use AwardWallet\MainBundle\Service\OldUI;
use AwardWallet\MainBundle\Worker\AsyncProcess\AsyncControllerAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class MerchantReportController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_MERCHANT')")
     * @Route("/manager/merchant-report", name="aw_manager_merchant_report")
     */
    public function reportAction(ParameterRepository $parameterRepository, OldUI $oldUI, RouterInterface $router, Request $request, AsyncControllerAction $asyncControllerAction)
    {
        $response = $asyncControllerAction->renderProgress($request, "Merchant Report");

        if ($response !== null) {
            return $response;
        }

        $list = new class($router) extends \TBaseList {
            /**
             * @var RouterInterface
             */
            private $router;

            public function __construct(RouterInterface $router)
            {
                $this->router = $router;

                $fields = [
                    "MerchantID" => ["Caption" => "Merchant ID", "Type" => "integer", "FilterField" => "mr.MerchantID"],
                    "MerchantName" => ["Type" => "string", "FilterField" => "m.Name"],
                    "Transactions" => [
                        "Caption" => "Total Transactions",
                        "Type" => "string",
                        "FilterField" => "mr.Transactions",
                        "Sort" => "mr.Transactions DESC",
                    ],
                    "ShoppingCategoryID" => [
                        "Type" => "integer",
                        "Caption" => "Category ID",
                        "FilterField" => "mr.ShoppingCategoryID",
                    ],
                    "CategoryName" => ["Type" => "string", "Caption" => "Category", "FilterField" => "mr.ShoppingCategoryID"],
                    "CreditCardID" => ["Type" => "integer", "Caption" => "Card ID", "FilterField" => "mr.CreditCardID"],
                    "CardName" => ["Type" => "string", "Caption" => "Card", "FilterField" => "cc.Name"],
                    "ExpectedMultiplier" => ["Type" => "float", "FilterField" => "ccscg.Multiplier"],
                    "MissedTransactions" => ["Type" => "integer", "FilterField" => "(mr.Transactions - mr.ExpectedMultiplierTransactions)"],
                ];

                parent::__construct("MerchantReport", $fields, "Transactions");
            }

            public function FormatFields($output = "html")
            {
                parent::FormatFields($output);

                $this->Query->Fields['MissedTransactions'] = "<a target='_blank' href=\"" . $this->router->generate("aw_manager_transaction_list", ["ShoppingCategoryID" => $this->OriginalFields['ShoppingCategoryID'], "CreditCardID" => $this->OriginalFields['CreditCardID'], "MerchantID" => $this->OriginalFields['MerchantID'], "IsExpectedMultiplier" => 0, "Sort1" => "Multiplier", "SortOrder" => "Normal"]) . "\">{$this->Query->Fields['MissedTransactions']}</a>";
            }
        };

        $currentVersion = (int) $parameterRepository->getParam(ParameterRepository::MERCHANT_REPORT_VERSION);
        $list->SQL = "
        SELECT 
               mr.MerchantID, 
               mr.Transactions,
               m.Name as MerchantName,
               mr.CreditCardID,
               cc.Name as CardName,
               mr.ShoppingCategoryID,
               sc.Name as CategoryName, 
               scg.Name as GroupName, 
               ccscg.Multiplier as ExpectedMultiplier,
               (mr.Transactions - mr.ExpectedMultiplierTransactions) as MissedTransactions 
        FROM 
            MerchantReport mr
            JOIN Merchant m on mr.MerchantID = m.MerchantID
            JOIN CreditCard cc ON mr.CreditCardID = cc.CreditCardID
            JOIN Provider p ON cc.ProviderID = p.ProviderID 
            JOIN ShoppingCategory sc ON mr.ShoppingCategoryID = sc.ShoppingCategoryID
            JOIN ShoppingCategoryGroup scg ON sc.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
            JOIN CreditCardShoppingCategoryGroup ccscg ON 
                ccscg.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
                AND ccscg.CreditCardID = cc.CreditCardID
                AND (ccscg.StartDate is null or (ccscg.StartDate <= NOW() and ccscg.EndDate > NOW()))
        WHERE 
            mr.Version = {$currentVersion}
        ";
        $list->ReadOnly = true;
        $list->Limit = 500;
        $list->PageSize = 500;
        $list->ShowFilters = true;
        $html = OutputBufferingUtils::captureOutput(fn () => $list->Draw());

        return new Response($html);
    }
}
