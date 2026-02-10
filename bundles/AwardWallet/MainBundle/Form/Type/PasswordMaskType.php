<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PasswordMaskType extends PasswordType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->getConfig()->getAttribute('always_empty')) {
            $view->vars['value'] = '';
        }
    }

    public function getBlockPrefix()
    {
        return 'password';
    }
}
