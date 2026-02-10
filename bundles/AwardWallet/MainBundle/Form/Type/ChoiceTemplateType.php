<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceTemplateType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['templates'] = $form->getConfig()->getOption('templates');
        $view->vars['js_callback'] = $form->getConfig()->getOption('js_callback');
        $view->vars['placeholder'] = $form->getConfig()->getOption('placeholder');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['templates', 'js_callback']);
        $resolver->setDefaults([
            'templates' => [],
            'required' => false,
            'compound' => false,
            'mapped' => false,
            'multiple' => false,
            /** @Ignore */
            'placeholder' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'choice_template';
    }
}
