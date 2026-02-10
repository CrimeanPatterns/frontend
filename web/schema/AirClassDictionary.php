<?

class TAirClassDictionarySchema extends TBaseSchema
{
	function __construct(){
		parent::TBaseSchema();
		$this->TableName = "AirClassDictionary";
		$this->Fields = [
			"AirlineCode" => [
			    "Type" => "string",
			    "Size" => 2,
                "InplaceEdit" => False,
			],
			"Source" => [
			    "Type" => "string",
			    "Size" => 120,
			    "Required" => True,
                "InplaceEdit" => False,
			],
			"Target" => [
			    "Type" => "string",
			    "Size" => 40,
                "Required" => True,
			    "Options" => array_merge(
			        [
                        \AwardWallet\MainBundle\Service\MileValue\Constants::CLASS_MAP_UNKNOWN => \AwardWallet\MainBundle\Service\MileValue\Constants::CLASS_MAP_UNKNOWN,
                        \AwardWallet\MainBundle\Service\MileValue\Constants::CLASS_MAP_PARSE_ERROR => \AwardWallet\MainBundle\Service\MileValue\Constants::CLASS_MAP_PARSE_ERROR,
			        ],
			        array_combine(
			            \AwardWallet\MainBundle\Service\MileValue\Constants::CLASSES_OF_SERVICE,
                        \AwardWallet\MainBundle\Service\MileValue\Constants::CLASSES_OF_SERVICE
                    )
                ),
			],
            "Classes" => [
                "Type" => "string",
                "Size" => 40,
                "InplaceEdit" => False,
                "Database" => False,
            ],
            "TripIDs" => [
                "Type" => "string",
                "Caption" => "Trip IDs",
                "Size" => 40,
                "InplaceEdit" => False,
            ],
            "ProviderIDs" => [
                "Type" => "string",
                "Caption" => "Provider Codes",
                "Size" => 40,
                "InplaceEdit" => False,
            ],
            "SourceFareClass" => [
                "Type" => "string",
                "Size" => 40,
                "Required" => True,
                "InplaceEdit" => False,
                "Options" => [2 => 'RewardAvailability'],
            ]
		];
		$this->bIncludeList = false;
		$this->ListClass = \AwardWallet\MainBundle\Manager\Schema\AirClassDictionaryList::class;
	}

    function TuneList( &$list ) {
	    parent::TuneList($list);
	    $list->MultiEdit = true;
	    $list->InplaceEdit = true;
    }

    function GetFormFields()
    {
        $result = parent::GetFormFields();
        $result['AirlineCode']['InputAttributes'] = 'style="width: 50px;"';
        unset($result['TripIDs']);
        unset($result['ProviderIDs']);
        unset($result['SourceFareClass']);
        unset($result['Classes']);
        return $result;
    }

    function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  	array(
		    	"Fields" => array("Source", "AirlineCode"),
		    	"ErrorMessage" => "This airline code / source  already exists. Please choose another combination."
		  	),
		);
	}

}
