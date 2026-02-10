<?php

namespace AwardWallet\MainBundle\Service\ProviderSignal;

use AwardWallet\MainBundle\Entity\Signalattribute;
use Doctrine\DBAL\Connection;

class ProviderSignalSchema extends \TBaseSchema
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->ListClass = ProviderSignalList::class;
        $this->connection = $connection;
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();
        $manager = new \TTableLinksFieldManager();
        $manager->TableName = "SignalAttribute";
        $manager->KeyField = "ProviderSignalID";
        $manager->CanEdit = true;
        $manager->UniqueFields = ["Name"];
        $manager->Fields = [
            "Name" => [
                "Type" => "string",
                "Caption" => "Name",
                "Required" => true,
            ],
            "Type" => [
                "Type" => "integer",
                "Caption" => "Type",
                "Required" => true,
                "Options" => [
                    Signalattribute::TYPE_INT => "Integer",
                    Signalattribute::TYPE_FLOAT => "Float",
                    Signalattribute::TYPE_STRING => "String",
                ],
            ],
            "PromptHelper" => [
                "Type" => "string",
                "InputType" => "textarea",
                "Caption" => "Prompt Helper",
                "Required" => true,
            ],
        ];
        $fields['Attributes'] = [
            "Manager" => $manager,
        ];

        return $fields;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->OnSave = [$this, "formSaved"];
    }

    public function formSaved()
    {
        \TProviderSchema::triggerDatabaseUpdate();
    }

    public function GetListFields()
    {
        $fields = parent::GetListFields();
        $fields["Attributes"] = [
            "Type" => "string",
        ];

        return $fields;
    }
}
