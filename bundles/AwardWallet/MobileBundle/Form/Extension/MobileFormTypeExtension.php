<?php

namespace AwardWallet\MobileBundle\Form\Extension;

use AwardWallet\MobileBundle\Form\Type\MobileType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MobileFormTypeExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'submit_label' => null,
            'children_order' => [],
        ]);
        $resolver->setAllowedTypes('children_order', 'array');
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['submit_label'] = $form->getConfig()->getAttribute('submit_label');
    }

    public static function getExtendedTypes(): iterable
    {
        return [MobileType::class];
    }
}
