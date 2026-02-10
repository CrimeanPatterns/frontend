<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

class ShoppingCategory extends \TBaseSchema
{
    private ProviderOptions $providerOptions;

    public function __construct(ProviderOptions $providerOptions)
    {
        $this->providerOptions = $providerOptions;

        parent::__construct();

        $this->Fields['LinkedToGroupBy']['Options'] = [
            '' => '',
            \AwardWallet\MainBundle\Entity\ShoppingCategory::LINKED_TO_GROUP_BY_MANUALLY => 'Manually',
            \AwardWallet\MainBundle\Entity\ShoppingCategory::LINKED_TO_GROUP_BY_PATTERNS => 'Patterns',
        ];
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();

        unset($result['ProviderID']);
        $result['ShoppingCategoryGroupID']['InputAttributes'] = ' onchange="if (this.value == \'\') this.form.LinkedToGroupBy.value = \'\'; else this.form.LinkedToGroupBy.value = \'' . \AwardWallet\MainBundle\Entity\ShoppingCategory::LINKED_TO_GROUP_BY_MANUALLY . '\'"';

        return $result;
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        unset($result['ClickURL']);
        unset($result['ProviderID']);

        return $result;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);

        $form->OnCheck = function () use ($form) {
            if ($form->Fields['ShoppingCategoryGroupID']['Value'] === null && $form->Fields['LinkedToGroupBy']['Value'] !== null) {
                return "Linked To Group By should be null when Group is null";
            }

            if ($form->Fields['ShoppingCategoryGroupID']['Value'] !== null && $form->Fields['LinkedToGroupBy']['Value'] === null) {
                return "Linked To Group By should be not null when Group is no null";
            }

            return null;
        };
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === 'ProviderID') {
            return $this->providerOptions->getOptions();
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }
}
