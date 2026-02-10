<?php

class TCreditCardEmailSchema extends TBaseSchema
{

    public function __construct()
    {
        parent::TBaseSchema();
        $this->TableName = "CreditCardEmail";
        $this->Fields = array(
            "Template" => array(
                "Type" => "string",
                "Size" => 80,
                "Required" => true,
                "Options" => array_merge(...\AwardWallet\MainBundle\Service\ChaseEmails\Constants::CARD_TEMPLATES),
            ),
            "Enabled" => array(
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
            ),
        );
        $this->Fields["Template"]["Options"] = array_combine(
            $this->Fields["Template"]["Options"],
            $this->Fields["Template"]["Options"]
        );
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->Uniques = [
            [
                'Fields' => ['Template'],
                'ErrorMessage' => 'Row with this template already exists',
            ]
        ];

    }

}
