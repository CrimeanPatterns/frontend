<?php

class TAccountCheckerPerksplus extends TAccountChecker
{
    private $sid = '';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['SuccessURL'] = 'https://unitedperksplus.united.com/default.aspx' . $this->sid;

        return $arg;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->LogHeaders = true;
        $this->http->GetURL("https://unitedperksplus.united.com/default.aspx");

        //# Find "SID"
        $link = $this->http->FindSingleNode("//ul[@id = 'listmenu']//a[contains(@href, 'UPPAccount')]/@href");

        if (!$link) {
            return false;
        }

        $this->logger->debug("Link found " . $link);
        $this->sid = str_replace('UPPAccount.aspx', '', $link);
        $this->http->Log("SID set >>>>> $this->sid <<<<<");
        $this->http->GetURL("https://unitedperksplus.united.com/default.aspx" . $this->sid);

        if (!$this->http->ParseForm("DefaultPage")) {
            return false;
        }
        $this->http->SetInputValue("txtRewardOneNum", $this->AccountFields['Login']);
        $this->http->SetInputValue("txtRewardOnePIN", $this->AccountFields['Pass']);
        $this->http->SetInputValue("imgBtnGo", "Log+In");

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return false;
        }

        //# Access is allowed
        if ($this->http->FindSingleNode("//a[contains(text(), 'Sign Out')]")) {
            return true;
        }
        //# Invalid credentials
        if ($message = $this->http->FindSingleNode("//span[@id = 'lblMsg']")) {
            $this->logger->error($message);

            if (strstr($message, 'There was a problem with your sign in. Please verify your United PerksPlus account ID and PIN/Password.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }

        return false;
    }

    public function Parse()
    {
        //# Account ID
        $this->SetProperty("Number", $this->http->FindSingleNode("//span[@id = 'LblAccountID']"));
        //# Effective Date
        $this->SetProperty('EffectiveDate', $this->http->FindSingleNode("//span[@id = 'LbleffDate']"));
        //# Company Name
        $this->SetProperty('CompanyName', $this->http->FindSingleNode("//span[@id = 'LblCompanyName']"));
        //# Tax ID
        $this->SetProperty("TaxID", $this->http->FindSingleNode("//span[@id = 'LblTaxID']"));
        //# Total Number of Employees
        $this->SetProperty("Employees", $this->http->FindSingleNode("//span[@id = 'LblNumEE']"));
        //# Total Number of Business Travelers
        $this->SetProperty("Travelers", $this->http->FindSingleNode("//span[@id = 'LblBusTrav']"));

        $this->http->GetURL('https://unitedperksplus.united.com/default.aspx' . $this->sid);
        //# Account Balance
        $this->SetBalance($this->http->FindSingleNode("//span[@id = 'lblAccountBalance']"));

//        if ($link = $this->http->FindSingleNode("//a[contains(@href, 'Statement')]/@href"))
//            $this->http->GetURL("http://unitedperksplus.united.com/".$link);
//        ## Balance - ?
//        if ($this->http->FindSingleNode("//*[contains(text(), 'There are no statements available for this account')]"))
//            $this->SetBalanceNA();
    }
    public function TuneFormFields(&$arFields, $values = null)
    {
        ArrayInsert($arFields, array_key_exists("SavePassword", $arFields) ? "SavePassword" : "Login", true, [
            "Balance" => [
                "Type"     => "float",
                "Caption"  => "Balance",
                "Required" => false,
                "Value"    => ArrayVal($values, "Balance", 0),
            ],
        ]);
    }
}
