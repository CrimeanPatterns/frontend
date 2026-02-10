<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HtmlType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['html_text'] = $options['html'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['html']);
        $resolver->setDefaults([
            'compound' => false,
            'attr' => ['disableLabel' => true],
            'required' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'html';
    }
}
