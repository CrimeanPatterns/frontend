<?php

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\OneCard\OnecardSent;

require_once __DIR__ . "/../onecard/common.php";

class OneCardList extends TBaseList
{
    private $selected;
    private $cardNameExpr;
    private $shippingNameExpr;

    public function __construct()
    {
        $intExp = "case co.Code
			when 'US' then 0
			else 1
		end";
        $weightExpr = "case count(oc.OneCardID)
			when 1 then 0.5
			when 2 then 0.6
			when 3 then 1
			when 4 then 1.1
			when 5 then 1.5
			when 6 then 1.6
			when 7 then 2
			when 8 then 2.2
			when 9 then 2.5
			when 10 then 2.7
		end";
        $this->cardNameExpr = "group_concat(concat('<nobr>', oc.FullName, ' - ', oc.TotalMiles, '</nobr>') separator '<br/>')";
        $this->shippingNameExpr = "concat(c.ShipFirstName, ' ', c.ShipLastName)";
        $cartIDsExpr = "group_concat(distinct c.CartID separator ', ')";
        $fields = [
            "CartID" => [
                "Type" => "string",
                "Caption" => "ID",
                "filterWidth" => "20",
                "Sort" => "c.CartID DESC",
                "FilterField" => $cartIDsExpr,
                "FilterType" => "having",
            ],
            "Cards" => [
                "Type" => "integer",
                "Caption" => "# Cards",
                "filterWidth" => "20",
                "FilterField" => "count(oc.OneCardID)",
                "FilterType" => "having",
            ],
            "Weight" => [
                "Type" => "float",
                "filterWidth" => "20",
                "FilterField" => $weightExpr,
                "FilterType" => "having",
            ],
            "CardName" => [
                "Type" => "string",
                "Size" => 80,
                "FilterField" => $this->cardNameExpr,
                "FilterType" => "having",
            ],
            "ShippingName" => [
                "Type" => "string",
                "Size" => 80,
                "filterWidth" => "50",
                "FilterField" => $this->shippingNameExpr,
            ],
            "International" => [
                "Type" => "boolean",
                "Caption" => "Int",
                "FilterField" => $intExp,
            ],
            "State" => [
                "Type" => "integer",
                "Size" => 80,
                "Required" => true,
                "Options" => [
                    ONECARD_STATE_NEW => "New",
                    ONECARD_STATE_PRINTING => "Printing",
                    ONECARD_STATE_PRINTED => "Printed",
                    ONECARD_STATE_BROKEN => "Broken",
                    ONECARD_STATE_DELETED => "Deleted",
                    ONECARD_STATE_REFUNDED => "Refunded",
                ],
            ],
            "OrderDate" => [
                "Type" => "date",
                "filterWidth" => "50",
            ],
            "PrintDate" => [
                "Type" => "date",
                "filterWidth" => "50",
            ],
        ];
        parent::__construct("Cart", $fields, "ShippingName");
        $this->SQL = "select
			$cartIDsExpr as CartID,
			max(c.PayDate) as OrderDate,
			c.ShipFirstName,
			c.ShipLastName,
			c.ShipAddress1,
			c.ShipAddress2,
			c.ShipCity,
			c.ShipZip,
			{$this->shippingNameExpr} as ShippingName,
			co.Name as ShipCountryName,
			co.Code as ShipCountryCode,
			case when co.Code = 'US' then st.Code else st.Name end as ShipStateName,
			st.Code as ShipStateCode,
			$intExp as International,
			max(c.PayDate) as PayDate,
			min(oc.State) as State,
			count(oc.OneCardID) as Cards,
			$weightExpr as Weight,
			{$this->cardNameExpr} as CardName,
			min(oc.PrintDate) as PrintDate
		from
			OneCard oc
			join Cart c on oc.CartID = c.CartID
			join Country co on c.ShipCountryID = co.CountryID
			join State st on c.ShipStateID = st.StateID
		where
			1 = 1
			[Filters]
		group by
			c.ShipFirstName,
			c.ShipLastName,
			c.ShipAddress1,
			c.ShipAddress2,
			c.ShipCity,
			c.ShipZip,
			concat(c.ShipFirstName, ' ', c.ShipLastName),
			co.Name,
			co.Code,
			st.Name,
			st.Code";
        $this->ShowEditors = true;
        $this->ShowFilters = true;
        unset($this->PageSizes["10"]);
        unset($this->PageSizes["20"]);
        unset($this->PageSizes["40"]);
        $this->PageSizes["200"] = 200;
        $this->PageSizes["500"] = 500;
        $this->PageSize = 200;
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        // workaround - mysql truncates long strings when sorting
        if ($this->Query->Fields['Cards'] > 3) {
            $q = new TQuery("select {$this->cardNameExpr} as Name from OneCard oc where CartID in({$this->Query->Fields['CartID']})");
            $this->Query->Fields['CardName'] = $q->Fields['Name'];
        } else {
            $this->Query->Fields['CardName'] = html_entity_decode($this->Query->Fields['CardName']);
        }
    }

    public function GetEditLinks()
    {
        return '';
    }

    public function DrawButtons($closeTable = true)
    {
        echo "<table cellspacing=0 cellpadding=0 border=0 width='100%'><tr><td style='text-align: left; border: none;'>";

        if (!$this->Query->IsEmpty && $this->MultiEdit) {
            echo "<input type=checkbox value=\"1\" onclick=\"selectCheckBoxes( this.form, 'sel', this.checked )\"> Select All ({$this->RowCount})";
            echo "</td><td align='right' style='border: none;'>";
            echo "<input class='button' type=button value=\"Print\" onclick=\"this.form.action.value = 'print'; form.submit();\"> ";
            echo "<input class='button' type=button value=\"Export Addresses\" onclick=\"this.form.action.value = 'addresses'; form.submit();\"> ";
            echo "<input class='button' type=button value=\"Send Email\" onclick=\"this.form.action.value = 'email'; form.submit();\"> ";
            echo "<input class='button' type=button value=\"Mark as printed\" onclick=\"this.form.action.value = 'printed'; form.submit();\"> ";
            echo "<input class='button' type=button value=\"Mark as broken\" onclick=\"this.form.action.value = 'broken'; form.submit();\"> ";
            echo "<input class='button' type=button value=\"Mark as new\" onclick=\"if(window.confirm('Mark as new? Are you sure?')) {this.form.action.value = 'new'; form.submit();}\"> ";
            echo "<input class='button' type=button value=\"Delete\" onclick=\"if(window.confirm('Delete? Are you sure?')) {this.form.action.value = 'delete'; form.submit();}\"> ";
            echo "<input class='button' type=button value=\"Delete and Credit\" onclick=\"if(window.confirm('Delete and Credit? Are you sure?')) {this.form.action.value = 'deleteAndCredit'; form.submit();}\"> ";

            if ($this->InplaceEdit) {
                echo "<input class='button' type=button value=\"Save changes\" onclick=\"if(CheckForm(this.form)){ this.form.action.value = 'update'; form.submit();}\"> ";
            }
        }

        if ($closeTable) {
            echo "</td></tr></table>";
        }
    }

    public function ProcessAction($action, $ids)
    {
        global $Connection, $Interface;

        if (count($ids) == 0) {
            $Interface->DrawMessage("No cards selected", "error");

            return;
        }

        switch ($action) {
            case "print":
                $message = $this->exportToPrinter($ids);

                if (isset($message)) {
                    $Interface->DrawMessage($message, "error");
                } else {
                    if (isset($_GET['State']) && ($_GET['State'] != ONECARD_STATE_PRINTING)) {
                        $_GET['State'] = ONECARD_STATE_PRINTING;
                        $_GET['Preselected'] = implode(",", $ids);
                        ScriptRedirect($_SERVER['SCRIPT_NAME'] . "?" . ImplodeAssoc("=", "&", $_GET, true));
                    } else {
                        $Interface->DrawMessage("Cards successfully exported.", "success");
                    }
                }

                break;

            case "printed":
                $Connection->Execute("update OneCard set State = " . ONECARD_STATE_PRINTED . ", PrintDate = now() where CartID in (" . implode(", ", $ids) . ")");

                break;

            case "broken":
                $Connection->Execute("update OneCard set State = " . ONECARD_STATE_BROKEN . " where CartID in (" . implode(", ", $ids) . ")");

                break;

            case "new":
                $Connection->Execute("update OneCard set State = " . ONECARD_STATE_NEW . " where CartID in (" . implode(", ", $ids) . ")");

                break;

            case "email":
                $this->emailCards($ids);

                break;

            case "addresses":
                $this->exportAddresses($ids);

                break;

            case "deleteAndCredit":
                $Connection->Execute("update OneCard set State = " . ONECARD_STATE_REFUNDED . " where CartID in (" . implode(", ", $ids) . ")");

                break;

            case "delete":
                $Connection->Execute("update OneCard set State = " . ONECARD_STATE_DELETED . " where CartID in (" . implode(", ", $ids) . ")");

                break;

            default:
                parent::ProcessAction($action, $ids);
        }
    }

    public function GetFilters($filterType = "where")
    {
        $result = parent::GetFilters($filterType);

        if (($filterType == "where") && isset($this->selected)) {
            if ($result != "") {
                $result .= " and ";
            }
            $result .= "c.CartID in (" . implode(", ", $this->selected) . ")";
        }

        return $result;
    }

    public function GetExportParams(&$arCols, &$arCaptions)
    {
        parent::GetExportParams($arCols, $arCaptions);
        unset($arCols['CardName']);
        unset($arCols['OrderDate']);
        unset($arCols['PrintDate']);
        unset($arCols['State']);
        unset($arCols['Cards']);
        unset($arCaptions['Cards']);
        unset($arCaptions['Address']);
        ArrayInsert($arCols, 'ShippingName', true, [
            "ShipAddress1" => [
                "Type" => "string",
                "Size" => 250,
            ],
            "ShipAddress2" => [
                "Type" => "string",
                "Size" => 250,
            ],
            "ShipCity" => [
                "Type" => "string",
                "Size" => 80,
            ],
            "ShipZip" => [
                "Type" => "string",
                "Size" => 40,
            ],
            "ShipCountryName" => [
                "Type" => "string",
                "Size" => 120,
            ],
            "ShipStateName" => [
                "Type" => "string",
                "Size" => 250,
            ],
        ]);
        ArrayInsert($arCaptions, 'ShippingName', true, [
            'ShippingName' => 'Name',
            'ShipAddress1' => 'Address1',
            'ShipAddress2' => 'Address2',
            'ShipCity' => 'City',
            'ShipZip' => 'Zip',
            'ShipCountryName' => 'Country',
            'ShipStateName' => 'State',
        ]);

        foreach (array_keys($arCaptions) as $key) {
            if (!isset($arCols[$key])) {
                unset($arCaptions[$key]);
            }
        }
    }

    public function exportToPrinter($ids)
    {
        global $Connection;
        $result = null;
        $container = getSymfonyContainer();
        $rows = SQLToArray("select
			oc.*,
			c.ShipFirstName,
			c.ShipLastName
		from
			OneCard oc
			join Cart c on oc.CartID = c.CartID
		where
			oc.CartID in (" . implode(", ", $ids) . ")
		order by
			{$this->shippingNameExpr}", "OneCardID", "OneCardID", true);
        $browser = new HttpBrowser("", new CurlDriver());
        $url = 'http://veresch.test.awardwallet.com:8080/api/oneCard/putCards.php';

        if (!$browser->PostURL(
            $url,
            [
                "Rows" => json_encode($rows),
                "Password" => $container->getParameter("printer_password"),
            ]
        )) {
            $result = "Can't put data to printer server located at " . $url . ': ' . $browser->Response['code'] . " " . $browser->Response['body'] . $browser->Response['errorCode'] . " " . $browser->Response['errorMessage'];
        }

        if (!isset($result) && ($browser->Response['body'] != 'OK')) {
            $result = $browser->Response['body'];
        }

        if ($result === null) {
            $Connection->Execute("update OneCard set State = " . ONECARD_STATE_PRINTING . " where CartID in (" . implode(", ", $ids) . ")");
        }

        return $result;
    }

    private function exportAddresses($ids)
    {
        $this->selected = $ids;
        $this->ExportName = "OneCardAddresses";
        ob_clean();
        ob_start();
        $this->ExportCSVHeader = ArrayVal($_GET, 'International') != '0';
        $this->ExportCSV();
        $s = ob_get_clean();
        header("Content-type: text/csv; charset=iso-8859-1");
        $s = htmlspecialchars_decode($s);
        $s = mb_convert_encoding($s, "iso-8859-1", "utf-8");
        //        echo "\xEF\xBB\xBF" . $s;
        echo $s;

        exit;
    }

    private function emailCards($ids)
    {
        global $Interface;

        $carts = getSymfonyContainer()->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)
            ->findBy(['cartid' => $ids]);
        $sent = [];
        $mailer = getSymfonyContainer()->get("aw.email.mailer");

        foreach ($carts as $cart) {
            /** @var \AwardWallet\MainBundle\Entity\Cart $cart */
            $userId = $cart->getUser()->getUserid();

            if (isset($sent[$userId])) {
                continue;
            }
            $sent[$userId] = true;

            $template = new OnecardSent($cart->getUser());
            $message = $mailer->getMessageByTemplate($template);
            $mailer->send($message, [Mailer::OPTION_SKIP_DONOTSEND => true]);
        }

        $Interface->DrawMessage("Emailed " . count($sent) . " messages.", "success");
    }
}
