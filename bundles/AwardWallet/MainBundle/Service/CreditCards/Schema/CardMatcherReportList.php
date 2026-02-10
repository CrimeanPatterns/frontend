<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use Symfony\Component\Routing\RouterInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CardMatcherReportList extends \TBaseList
{
    private RouterInterface $router;
    private CardLink $cardLink;

    public function __construct(
        string $table,
        array $fields,
        RouterInterface $router,
        CardLink $cardLink
    ) {
        parent::__construct($table, $fields);
        $this->router = $router;
        $this->cardLink = $cardLink;
        $this->Fields["MatchedCreditCards"]["AllowFilters"] = false;
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        if ($output === 'html') {
            $this->Query->Fields["ParseCount"] = "<a href=\"" . $this->router->generate("aw_manager_card_matcher_rows", ["id" => $this->OriginalFields["CardMatcherReportID"]]) . "\" target=\"_blank\">{$this->Query->Fields["ParseCount"]}</a>";
            $this->Query->Fields["MatchCount"] = "<a href=\"" . $this->router->generate("aw_manager_card_matcher_rows", ["id" => $this->OriginalFields["CardMatcherReportID"], "detected" => 1]) . "\" target=\"_blank\">{$this->Query->Fields["MatchCount"]}</a>";
            $this->Query->Fields["Undetected"] = "<a href=\"" . $this->router->generate("aw_manager_card_matcher_rows", ["id" => $this->OriginalFields["CardMatcherReportID"], "detected" => 0]) . "\" target=\"_blank\">{$this->Query->Fields["Undetected"]}</a>";
            $this->Query->Fields["MatchedCreditCards"] = it(json_decode($this->OriginalFields["MatchedCreditCards"], true))
                ->mapIndexed(fn (int $matches, int $creditCardId) => $this->cardLink->format($creditCardId) . ": $matches")
                ->joinToString("<br/>\n");
        }
    }
}
