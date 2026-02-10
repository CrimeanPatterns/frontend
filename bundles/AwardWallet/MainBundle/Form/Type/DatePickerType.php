<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatePickerType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['datepicker_options'] = $options['datepicker_options'];

        if (!empty($view->vars['datepicker_options']['yearRange']) && is_array($view->vars['datepicker_options']['yearRange']) && \count($view->vars['datepicker_options']['yearRange']) > 1) {
            $view->vars['datepicker_options']['yearRange'] = array_pop($view->vars['datepicker_options']['yearRange']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'widget' => 'single_text',
            'datepicker_options' => [],
        ]);
    }

    public function getParent()
    {
        return DateType::class;
    }

    public function getBlockPrefix()
    {
        return 'datepicker';
    }
}
