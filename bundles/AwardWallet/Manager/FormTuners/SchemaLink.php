<?php

namespace AwardWallet\Manager\FormTuners;

use AwardWallet\Manager\FormTunerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SchemaLink implements FormTunerInterface
{
    private ServiceLocator $schemas;

    public function __construct(ServiceLocator $schemas)
    {
        $this->schemas = $schemas;
    }

    public function tuneForm(\TBaseForm $form): void
    {
        global $Interface;

        $linked = false;

        foreach ($form->Fields as $fieldName => &$field) {
            if (empty($field['LookupTable'])) {
                continue;
            }

            if (!$this->schemas->has($field['LookupTable'])) {
                continue;
            }

            $field['Widgets'][] = "<a data-schema='{$field['LookupTable']}' data-field='{$fieldName}' class='schema-lookup-edit' href='edit.php?Schema={$field['LookupTable']}&ID=0' target='_blank'>edit</a>";
            $linked = true;
        }

        unset($field);

        if ($linked) {
            $Interface->FooterScripts[] = "require(['manager/js/schema-link-manager']);";
        }
    }
}
