<?

class OfferList extends TBaseList{

    function __construct($table, $fields, $defaultSort){
        $fields["Users"] = array(
            "Type" => "integer",
            "Database" => false,
            "Caption" => "Users",
            "Value" => "0"
        );
        $fields["Agreed"] = array(
            "Type" => "integer",
            "Database" => false,
            "Caption" => "Agreed",
            "Value" => "0"
        );
        parent::__construct($table, $fields, $defaultSort);
    }
        
	function FormatFields($output = "html"){
		parent::FormatFields($output);
        $t = $this->Query->Fields['OfferID'];
        $q = new TQuery("SELECT COUNT(`UserID`) as `N` FROM `OfferUser` WHERE `OfferID` = $t");
        $this->Query->Fields['Users'] = $q->Fields['N'];
        $t = $this->Query->Fields['OfferID'];
//      $q = new TQuery("SELECT SUM(`Agreed`) as `N` FROM `OfferUser` WHERE `OfferID` = $t");
//        if ($q->Fields['N'] <> null)
//      $this->Query->Fields['Agreed'] = $q->Fields['N'];
//        else
//            $this->Query->Fields['Agreed'] = 0;
        $agreed1 = (new TQuery("
        select count(distinct(UserID)) as Agreed from OfferLog
        where OfferID = $t and Action = 1 and not UserID in (
            select UserID from OfferUser where OfferID = $t and Agreed = 1
        )
        "))->Fields['Agreed'];
        $agreed2 = (new TQuery("
        select count(distinct(UserID)) as Agreed from OfferUser
        where OfferID = $t and Agreed = 1
        "))->Fields['Agreed'];
        $this->Query->Fields['Agreed'] = $agreed1 + $agreed2;
    }
                
	function GetEditLinks(){
        $q = new TQuery("
            select * from(
                select 
                    UserID, 
                    OfferUserID 
                from 
                    OfferUser 
                where 
                    OfferID = ".$this->Query->Fields["OfferID"]." 
                limit 100
            ) ou order by rand()
        ");

        $offerUserId = null;
        ob_start();
        $plugin = $this->loadOfferPlugin();

        if ($plugin) {
            foreach ($q as $userData) {
                if ($plugin->checkUser($userData['UserID'], $userData['OfferUserID'])) {
                    $offerUserId = $userData['OfferUserID'];

                    break;
                }
            }
        }

        ob_end_clean();

        $links = [parent::GetEditLinks()];

        if (isGranted('ROLE_MANAGE_OFFERUSER')) {
            $links[] = "<a href = \"list.php?Schema=OfferUser&OfferID=".$this->Query->Fields["OfferID"]."\">Edit users</a>";
            $links[] = "<a href = \"/manager/offer/update.php?OfferID=".$this->Query->Fields["OfferID"]."\">Search users</a>";
        }

        array_unshift($links, "<a href =\"reports/offerStats.php?offer=".$this->Query->Fields["OfferID"]."\".>Statistics</a>");

        if (isset($offerUserId, $userData)) {
            $impersonateLink = '/manager/impersonate?'.
                'UserID=' . $userData['UserID'] .
                '&AutoSubmit=1' .
                '&Goto=' . urlencode(getSymfonyContainer()->get('router')->generate('aw_account_list') . '?previewUserOfferId=' . $userData['OfferUserID']);

            array_unshift($links, '<a href="'. $impersonateLink . '" target="_blank">Preview</a>');
        }

        return implode(' | ', $links);
	}

    /**
     * @return \OfferPlugin
     */
    protected function loadOfferPlugin()
    {
        $rootDir = $rootDir = getSymfonyContainer()->get('kernel')->getProjectDir();
        $offerClassName = ucfirst($this->Query->Fields["Code"]) . 'OfferPlugin';
        $pluginFile = $rootDir . '/src/manager/offer/plugins/'. $offerClassName . '.php';

        if (!file_exists($pluginFile)) {
            return null;
        }

        require_once $pluginFile;
        /** @var \OfferPlugin $offer */
        return new $offerClassName($this->Query->Fields["OfferID"], getSymfonyContainer()->get('doctrine'));
    }
}
?>
