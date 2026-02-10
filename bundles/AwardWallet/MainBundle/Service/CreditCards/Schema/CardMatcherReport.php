<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

class CardMatcherReport extends \TBaseSchema
{
    private ProviderOptions $providerOptions;

    public function __construct(ProviderOptions $providerOptions)
    {
        $this->providerOptions = $providerOptions;

        parent::__construct();

        unset($this->Fields['Rows']);
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->ReadOnly = true;
    }

    public function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === 'ProviderID') {
            return $this->providerOptions->getOptions();
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }
}
