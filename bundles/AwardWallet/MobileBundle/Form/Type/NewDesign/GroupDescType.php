<?php

namespace AwardWallet\MobileBundle\Form\Type\NewDesign;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupDescType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'group_desc';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['label'] = $options['text'];
    }

    public function getParent()
    {
        return HiddenType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'text' => null,
            'mapped' => false,
        ]);
    }
}
