<?

class OfferUserList extends TBaseList{
    /**
     * @var \OfferPlugin[]
     */
    protected $plugins = [];

    protected $offers = [];
    
    function __construct($table, $fields, $defaultSort){
        $fields["Agreed"] = array(
            "Type" => "string",
            "Database" => true,
            "Caption" => "Agreed",
            "Options" => array ("" => "", "0" => "Refused", "1" => "Agreed"),
            "Value" => ""
        );
        $fields["ShowDate"] = array(
            "Type" => "string",
            "Database" => true,
            "Caption" => "ShowDate",
            "Value" => ""
        );

        $q = new TQuery("SELECT OfferID, Code FROM Offer ORDER BY OfferID DESC");

        foreach ($q as $offerData) {
            $this->offers[$offerData['OfferID']] = $offerData['Code'];
        }

        parent::__construct($table, $fields, $defaultSort);
    }
    
    function FormatFields($output = "html"){
        parent::FormatFields($output);

        $plugin = $this->loadOfferPlugin($this->Query->Fields['OfferID']);

        if ($plugin && $plugin->checkUser($this->Query->Fields['UserID'], $this->Query->Fields['OfferUserID'])) {
            $impersonateLink = '/manager/impersonate?'.
                'UserID=' . $this->Query->Fields['UserID'] .
                '&AutoSubmit=1' .
                '&Goto=' . urlencode(getSymfonyContainer()->get('router')->generate('aw_account_list') . '?previewUserOfferId=' . $this->Query->Fields['OfferUserID']);

            $this->Query->Fields['UserID'] = "<a href = \"{$impersonateLink}\" target='_blank'><b>".$this->Query->Fields['UserID']."</b></a>";
        }
    }

    /**
     * @param $offerId
     *
     * @return \OfferPlugin|null
     */
    protected function loadOfferPlugin($offerId)
    {
        $offerId = (int) $offerId;

        if (!isset($this->offers[(int) $offerId])) {
            return null;
        }

        $code = $this->offers[$offerId];

        if (isset($this->plugins[$code])) {
            return $this->plugins[$code];
        }

        $rootDir = $rootDir = getSymfonyContainer()->get('kernel')->getProjectDir();
        $offerClassName = ucfirst($code) . 'OfferPlugin';
        $pluginFile = $rootDir . '/src/manager/offer/plugins/' . $offerClassName . '.php';

        if (!file_exists($pluginFile)) {
            return null;
        }

        require_once $pluginFile;

        return $this->plugins[$code] = new $offerClassName($offerId, getSymfonyContainer()->get('doctrine'));
    }
}
