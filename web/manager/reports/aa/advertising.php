<?
$schema = "qaac";
require "../../start.php";
drawHeader("All advertisers");
class AdsList extends TBaseList{
    function __construct(){
        parent::__construct("SocialAd", array(
                "id" => array(
                    "Type" => "integer",
                    "Caption" => "ID",
                ),
                "Kind" => array(
                    "Type" => "integer",
                    "Required" => True,
                    "Options" => array(
                        "" => "",
                        ADKIND_SOCIAL => "Social",
                        ADKIND_EMAIL => "Email",
                        ADKIND_BALANCE_CHECK => "Balance check",
                        -1 => "Offer",
                        -2 => "Credit Card",
                    ),
                ),
                "title" => array(
                    "Type" => "string",
                    "Caption" => "Title",
                ),
                "content" => array(
                    "Type" => "text",
                    "Caption" => "Content",
                ),
            ),
            "id");
        $this->ReadOnly = true;
        $this->UsePages = false;
        // $this->PageSize = 5000;
        $this->SQL = "
            select SocialAdID as id, Name as title, Content as content, Kind
            from SocialAd
            where EndDate >= now() and (AllProviders = 1 or ProviderKind is not null)
            union
            select OfferID as id, Name as title, Description as content, -1 as Kind
            from Offer
            where Enabled = 1
        ";
    }
}
$list = new AdsList;
$list->Draw();
echo "<br />Please note that unless otherwise specified none of these ads are targeting targeting American Customers, however American Customers could possibly see any of these ads on AwardWallet.<br />";
/* <br />
This reports shows the following items from the contract:
<ul><li>A list of all advertisers and campaigns served to Joint Members.
        <ul><li>As defined in Attachment 4 Section 5, AwardWallet is prohibited from targeting American Customers with advertising without Americans prior written approval.</li></ul></li>
</ul>"; */
drawFooter();
?>
