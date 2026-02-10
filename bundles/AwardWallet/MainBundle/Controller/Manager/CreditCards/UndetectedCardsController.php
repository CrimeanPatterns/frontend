<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use AwardWallet\MainBundle\Service\CreditCards\Schema\ProviderOptions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class UndetectedCardsController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_CREDITCARD')")
     * @Route("/manager/credit-cards/undetected-cards", name="aw_manage_undetected_cards")
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
                        "CreditCardID" => [
                            "Type" => "integer",
                            "Options" => SQLToArray("select CreditCardID, Name from CreditCard order by ProviderID, Name", "CreditCardID", "Name"),
                        ],
                        "UserID" => [
                            "Type" => "integer",
                        ],
                        "AccountID" => [
                            "Type" => "integer",
                        ],
                        "DetectedCards" => [
                            "Type" => "string",
                        ],
                        "LastSeenDate" => [
                            "Type" => "date",
                            "Sort" => "LastSeenDate DESC",
                        ],
                    ]
                );
                $this->ReadOnly = true;
                $this->Limit = 1000;
                $this->PageSize = 500;
                $this->PageSizes = ["100" => "100", "500" => "500", "1000" => "1000"];
                $this->ShowFilters = true;
                $this->SQL = "
                select 
                    cc.ProviderID,
                    ucc.CreditCardID, 
                    ucc.UserID,
                    a.AccountID,
                    CONCAT(
                      '[',
                      GROUP_CONCAT(
                        JSON_OBJECT(
                          'Name', dc2.DisplayName,
                          'CreditCardID', dc2.CreditCardID
                        )
                      ),
                      ']'
                    ) as DetectedCards,
                    ucc.LastSeenDate
                from 
                    UserCreditCard ucc 
                    join CreditCard cc on ucc.CreditCardID = cc.CreditCardID
                    join Account a on cc.ProviderID = a.ProviderID and ucc.UserID = a.UserID
                    join (
                        select
                            UserID,
                            QsCreditCardID,
                            min(ProcessDate) as ProcessDate
                        from
                            QsTransaction
                        where
                            Approvals > 0
                        group by
                            UserID,
                            QsCreditCardID                        
                    ) qst on a.UserID = qst.UserID and qst.QsCreditCardID = ucc.CreditCardID
                    left join DetectedCard dc on a.AccountID = dc.AccountID and ucc.CreditCardID = dc.CreditCardID
                    left join Account a2 on ucc.UserID = a2.UserID and cc.ProviderID = a2.ProviderID
                    left join DetectedCard dc2 on dc2.AccountID = a2.AccountID
                where 
                    ucc.DetectedViaQS = 1 
                    and DetectedViaBank = 0 
                    and dc.DetectedCardID is null 
                    and a.SuccessCheckDate > ucc.LastSeenDate 
                    and ucc.DetectedViaCobrand = 0 
                    and ucc.DetectedViaEmail = 0
                    and a.State = " . ACCOUNT_ENABLED . "
                    and a.SuccessCheckDate > qst.ProcessDate 
                group by
                    ProviderID,
                    CreditCardID,
                    UserID,
                    AccountID
                ";
                $this->SQL = "select * from ({$this->SQL}) a where 1 = 1 [Filters]";
                $this->DefaultSort = "LastSeenDate";

                $this->router = $router;
            }

            public function FormatFields($output = "html")
            {
                parent::FormatFields($output);

                $this->Query->Fields["UserID"] = "<a href=\"" . $this->router->generate('aw_manager_impersonate', ["UserID" => $this->OriginalFields["UserID"]]) . "\" target='_blank'>{$this->OriginalFields["UserID"]}</a>";
                $this->Query->Fields["AccountID"] = "<a href=\"" . $this->router->generate('aw_manager_impersonate', ["UserID" => $this->OriginalFields["UserID"], "Goto" => $this->router->generate("aw_account_list", ["account" => $this->OriginalFields["AccountID"]])]) . "\" target='_blank'>{$this->OriginalFields["AccountID"]}</a>";
                $this->Query->Fields["CreditCardID"] = "<a href=\"" . $this->router->generate('aw_manager_edit', ["ID" => $this->OriginalFields['CreditCardID'], "Schema" => "CreditCard"]) . "\" target='_blank'>{$this->Query->Fields["CreditCardID"]} - {$this->OriginalFields["CreditCardID"]}</a>";

                $this->Query->Fields["DetectedCards"] = implode("<br/>", array_map(function (array $card) {
                    $result = $card['Name'];

                    if ($card['CreditCardID']) {
                        $result .= " - " . $card['CreditCardID'];
                        $result = "<a href=\"" . $this->router->generate('aw_manager_edit', ["ID" => $card['CreditCardID'], "Schema" => "CreditCard"]) . "\" target=\"_blank\">{$result}</a>";
                    }

                    return $result;
                }, json_decode($this->OriginalFields["DetectedCards"], true) ?? []));
            }
        };

        ob_start();
        $list->Draw();
        $html = ob_get_clean();

        return new Response($twig->render('@AwardWalletMain/Manager/CreditCards/undetectedCards.html.twig', ["html" => $html]));
    }
}
