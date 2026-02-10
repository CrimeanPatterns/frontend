<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use AwardWallet\MainBundle\Service\CreditCards\Schema\ProviderOptions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class LastDetectedCardsController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_CREDITCARD')")
     * @Route("/manager/credit-cards/last-detected-cards", name="aw_manage_last_detected_cards")
     */
    public function __invoke(Environment $twig, RouterInterface $router, ProviderOptions $providerOptions)
    {
        ob_start();

        $list = new class($router, $providerOptions) extends \TBaseList {
            private RouterInterface $router;

            public function __construct(RouterInterface $router, ProviderOptions $providerOptions)
            {
                parent::__construct(
                    "dummy",
                    [
                        "ProviderID" => [
                            "Type" => "integer",
                            "Options" => $providerOptions->getOptions(),
                        ],
                        "CleanName" => [
                            "Type" => "string",
                        ],
                        "Accounts" => [
                            "Type" => "integer",
                            "Sort" => "Accounts DESC",
                        ],
                        "Example" => [
                            "Type" => "string",
                        ],
                        "CreditCardID" => [
                            "Type" => "integer",
                            "Caption" => "Matched Credit Card",
                            "Options" => SQLToArray("select CreditCardID, Name from CreditCard order by ProviderID, Name", "CreditCardID", "Name"),
                        ],
                        "AccountID" => [
                            "Type" => "integer",
                            "Caption" => "Sample Account",
                        ],
                    ]
                );
                $this->ReadOnly = true;
                $this->Limit = 5000;
                $this->PageSize = 2000;
                $this->PageSizes = ["500" => "500", "1000" => "1000", "2000" => "2000"];
                $this->ShowFilters = true;
                $this->SQL = "
                select 
                    a.ProviderID as ProviderID,
                    trim(
                        REGEXP_REPLACE(
                        REGEXP_REPLACE(
                        REGEXP_REPLACE(
                        REGEXP_REPLACE(convert(dc.DisplayName using utf8mb4), 
                        convert('\\\\(?[-\\\\.]*\\\\d+\\\\)?' using utf8mb4), ''),
                        convert('  +' using utf8mb4), ''), 
                        convert('[®℠]|ending in|ending with|\\\\.\\\\.+|xx+' using utf8mb4), ''), 
                        convert('[–\\\\-\\\\*] *$' using utf8mb4), '')
                    ) as CleanName, 
                    count(dc.AccountID) as Accounts,
                    max(dc.AccountID) as AccountID, 
                    max(dc.CreditCardID) as CreditCardID,
                    max(dc.DisplayName) as Example 
                from 
                    DetectedCard dc 
                    join Account a on dc.AccountID = a.AccountID 
                where 
                    a.SuccessCheckDate > adddate(now(), -1) 
                group by
                    ProviderID,
                    CleanName
                ";
                $this->SQL = "select * from ({$this->SQL}) a where 1 = 1 [Filters]";
                $this->DefaultSort = "Accounts";

                $this->router = $router;
            }

            public function FormatFields($output = "html")
            {
                parent::FormatFields($output);

                $this->Query->Fields["CleanName"] = "<a target='_blank' href=\"" . $this->router->generate('aw_credit_card_matcher_test', ["CardName" => $this->OriginalFields["CleanName"], "Provider" => $this->OriginalFields["ProviderID"]]) . "\">{$this->OriginalFields["CleanName"]}</a>";

                $this->Query->Fields["AccountID"] = "<a target='_blank' href=\"" . $this->router->generate('aw_manager_impersonate_account', ["account" => $this->OriginalFields["AccountID"]]) . "\">{$this->OriginalFields["AccountID"]}</a>";

                if (!empty($this->Query->Fields["CreditCardID"])) {
                    $this->Query->Fields["CreditCardID"] = "<a target='_blank' href=\"" . $this->router->generate('aw_manager_edit', ["ID" => $this->OriginalFields["CreditCardID"], "Schema" => "CreditCard"]) . "\">{$this->Query->Fields["CreditCardID"]}</a>";
                }
            }
        };

        ob_start();
        $list->Draw();
        $html = ob_get_clean();

        return new Response($twig->render('@AwardWalletMain/Manager/CreditCards/lastDetectedCards.html.twig', ["html" => $html]));
    }
}
