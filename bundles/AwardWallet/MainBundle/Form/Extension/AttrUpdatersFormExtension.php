<?php

namespace AwardWallet\MainBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class AttrUpdatersFormExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $formConfig = $form->getConfig();

        if (!$formConfig->hasAttribute('attr_updaters')) {
            return;
        }

        /** @var callable[] $attrUpdater */
        $attrUpdaters = $formConfig->getAttribute('attr_updaters');
        $attr = $view->vars['attr'] ?? [];

        foreach ($attrUpdaters as $attrUpdater) {
            $attr = $attrUpdater($attr);
        }

        $view->vars['attr'] = $attr;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
