<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use AwardWallet\MainBundle\Command\CreditCards\BuildCardMatcherReportCommand;
use AwardWallet\MainBundle\Service\CreditCards\Schema\CardLink;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CardMatcherRowsController
{
    /**
     * @IsGranted("ROLE_MANAGE_CARDMATCHERREPORT")
     * @Route("/manager/credit-cards/card-matcher-rows/{id}", name="aw_manager_card_matcher_rows", requirements={"id"="^\d+$"})
     */
    public function showRows(
        int $id,
        Connection $connection,
        RouterInterface $router,
        Environment $twig,
        Request $request,
        CardLink $cardLink
    ): Response {
        $row = $connection->fetchAssoc("select p.DisplayName, r.Name, r.Rows 
        from CardMatcherReport r join Provider p on r.ProviderID = p.ProviderID 
        where CardMatcherReportID = ?", [$id]);
        $title = "Matched cards $id";

        if ($row === false) {
            $content = "Record {$id} not found. Outdated link?";
        } else {
            $title .= ", {$row['DisplayName']}, {$row['Name']}";
            $content = $this->renderRows(
                $row['Rows'],
                $request->query->get("detected", null),
                $router->generate("aw_account_list"),
                $cardLink
            );
        }

        return new Response($twig->render("@AwardWalletMain/Manager/layout.html.twig", ["content" => $content, "title" => $title]));
    }

    private function renderRows(string $json, ?bool $detected, string $listUrl, CardLink $cardLink): string
    {
        $rows = json_decode($json, true);

        if ($detected !== null) {
            $rows = it($rows)
                ->filter(fn (array $row) => ($row['CreditCardID'] !== null) === $detected)
                ->toArray()
            ;
        }

        return
            "<table class='detailsTable'>
                <thead>
                    <tr>
                        <td>Display Name</td>
                        <td>AccountID</td>
                        <td>Credit Card</td>
                        <td>Source</td>
                    </tr>
                </thead>"
            . it($rows)
                ->map(fn (array $row) => array_merge($row, [
                    "Source" => BuildCardMatcherReportCommand::SOURCE_NAMES[$row["Source"]],
                    "CreditCardID" => $row['CreditCardID'] ? $cardLink->format($row['CreditCardID']) : "",
                    "AccountID" => "<a target='_blank' href='/manager/impersonate?UserID={$row['UserID']}&Goto=" . urlencode($listUrl . "?account=" . $row['AccountID']) . "'>{$row['AccountID']}</a>",
                ]))
                ->map(fn (array $row) => "
                    <tr> 
                        <td>{$row['DisplayName']}</td>
                        <td>{$row['AccountID']}</td>
                        <td>{$row['CreditCardID']}</td>
                        <td>{$row['Source']}</td>
                    </tr>")
                ->joinToString("\n")
            . "</table>";
    }
}
