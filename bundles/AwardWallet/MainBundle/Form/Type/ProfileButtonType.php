<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileButtonType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['value'] = $options['value'];
        $view->vars['valueCounter'] = $options['valueCounter'];
        $view->vars['link'] = $options['link'];
        $view->vars['link_label'] = $options['link_label'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'value' => '',
            'valueCounter' => '',
            'link' => '',
            'link_label' => '',
        ]);
    }

    public function getParent()
    {
        return TextType::class;
    }

    public function getBlockPrefix()
    {
        return 'profile_button';
    }
}
