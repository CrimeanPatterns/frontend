<?php

namespace AwardWallet\Manager\Schema;

use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class RetailProviderMerchant extends \TBaseSchema
{
    private Connection $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        parent::__construct();

        $this->dbConnection = $dbConnection;
        $this->Fields = [
            "MerchantID" => [
                "Type" => "string",
                "Size" => 255,
                "Required" => true,
                "InplaceEdit" => false,
                "Note" => "accepts MerchantID (ex. 62281127), Offer URL (ex. https://awardwallet.com/merchants/IKEA_3)",
                "FilterField" => "m.MerchantID",
            ],
            "ProviderID" => [
                "Caption" => "Provider",
                "Type" => "integer",
                "Required" => false,
                "Options" =>
                    it($this->dbConnection->executeQuery("select ProviderID, CONCAT(DisplayName, ' (', Code, ')') from Provider order by DisplayName")->fetchAllNumeric())
                    ->map(fn ($pair) => [$pair[0], \html_entity_decode($pair[1])])
                    ->prepend(["", "All Providers"])
                    ->fromPairs()
                    ->toArrayWithKeys(),
                "InplaceEdit" => false,
                "InputAttributes" => " data-form-select2='" . \json_encode(['width' => '300px']) . "'",
                "FilterField" => 'rpm.ProviderID',
                "Sort" => 'rpm.ProviderID DESC',
            ],
            'ProviderKind' => [
                "Database" => false,
                "Caption" => "Kind Match",
                "Type" => "string",
                "InplaceEdit" => false,
            ],
            "Manual" => [
                "Caption" => "Approved",
                "Type" => "boolean",
                "FilterField" => 'rpm.Manual',
                "Sort" => 'rpm.Manual',
                "InplaceEdit" => true,
                "Note" => 'merchant was linked manually or approved by human',
            ],
            "Auto" => [
                "Type" => "boolean",
                "InputAttributes" => ' readonly="readonly" onclick="return false;"',
                "FilterField" => 'rpm.Auto',
                "Sort" => 'rpm.Auto',
                "InplaceEdit" => true,
                "Note" => 'merchant was linked automatically',
            ],
            "Disabled" => [
                "Type" => "boolean",
                "FilterField" => 'rpm.Disabled',
                "Sort" => 'rpm.Disabled',
                "InplaceEdit" => true,
                'Note' => 'just disable (DO NOT REMOVE) to prevent automatic linking in future, if no other candidate is found',
            ],
            'Popularity' => [
                'Type' => 'integer',
                'Sort' => 'Popularity DESC',
                "InplaceEdit" => false,
                'FilterField' => 'p.Accounts',
            ],
            'Site' => [
                'Type' => 'string',
                'Sort' => 'p.Site',
                "InplaceEdit" => false,
                'FilterField' => 'p.Site',
            ],
        ];
    }

    public function TuneForm(\TBaseForm $form)
    {
        unset($form->Fields['Popularity']);
        unset($form->Fields['Site']);
        unset($form->Fields['ProviderKind']);

        if ($form->IsPost) {
            if (
                isset($_POST['MerchantID'])
                && \preg_match('#(?:/merchants/|^)[^/]+_(\d+)#', $_POST['MerchantID'], $merchantIdMatch)
            ) {
                $_POST['MerchantID'] = $merchantIdMatch[1];
            }
        }
        parent::TuneForm($form);
    }
}
