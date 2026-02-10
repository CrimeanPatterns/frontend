<?php

class TBonusConversionProviderSchema extends TBaseSchema{

	public function __construct(){
		parent::TBaseSchema();
		$this->TableName = "BonusConversionProvider";
		$this->ListClass = "BonusConversionProviderList";
		$this->DefaultSort = 'BonusConversionProviderID';
		$this->Fields = [
			'BonusConversionProviderID' => [
				'Caption' =>	'ID',
				'Type' =>		'integer',
                'Required' => true,
                'InputAttributes' => ' readonly',
                'InplaceEdit' => false
			],
			'ProviderID' => [
                'Caption' => 'Provider',
				'Type' =>	'integer',
				'Required' => true,
                'Options' => SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
                'InplaceEdit' => false,
			],
			'Enabled' => [
				'Type' => 'boolean',
				'Required' => true,
                'InplaceEdit' => true,
			],
			'Name' => [
				'Type' => 'string',
				'Required' => true,
                'InplaceEdit' => false,
			],
            'PurchaseLink' => [
                'Type' => 'string',
                'Rquired' => false,
                'InplaceEdit' => false,
            ],
            'MinMiles' => [
                'Caption' => 'Minimum (Miles)',
                'Type' => 'integer',
                'Required' => true,
                'InplaceEdit' => false,
            ],
            'MaxMiles' => [
                'Caption' => 'Maximum (Miles)',
                'Type' => 'integer',
                'Required' => true,
                'InplaceEdit' => false,
            ],
            'StepMiles' => [
                'Caption' => 'Increment interval (Miles)',
                'Type' => 'integer',
                'Required' => true,
                'InplaceEdit' => false,
            ],
            'OneMileCost' => [
                'Caption' => 'One mile cost (USD)',
                'Type' => 'float',
                'Required' => true,
                'InplaceEdit' => false,
                'DecimalPlaces' => '5',
            ],
            'FixedFee' => [
                'Caption' => 'Fixed fee (USD)',
                'Type' => 'float',
                'Required' => true,
                'InplaceEdit' => false,
                'DecimalPlaces' => '2'
            ],
		];
	}

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();
        unset($fields['BonusConversionProviderID']);

        return $fields;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);

        $form->OnCheck = [$this, 'checkForm', & $form];
    }

    public function checkForm(TForm $objForm)
    {
        $fields = $objForm->Fields;
        try {
            $this->g($fields, $name = 'MinMiles', 0);
            $this->g($fields, $name = 'MaxMiles', 0);
            $this->g($fields, $name = 'StepMiles', 0);
            $this->g($fields, $name = 'OneMileCost', 0);
            $this->ge($fields, $name = 'FixedFee', 0);
            $this->ge($fields, $name = 'MaxMiles', $fields['MinMiles']['Value'] + $fields['StepMiles']['Value']);

        } catch (\OutOfBoundsException $e) {
            return $objForm->Fields[$name]['Error'] = "'{$objForm->Fields[$name]['Caption']}' is invalid. " . $e->getMessage();
        }

        return null;
    }

    protected function g($fields, $name, $value)
    {
        if (((float)$fields[$name]['Value']) <= $value) {
            throw new \OutOfBoundsException("Value must be greater than {$value}");
        }
    }

    protected function ge($fields, $name, $value)
    {
        if (((float)$fields[$name]['Value']) < $value) {
            throw new \OutOfBoundsException("Value must be greater or equal than {$value}");
        }
    }
}
