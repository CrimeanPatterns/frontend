<?php

namespace AwardWallet\MainBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldTypeHelpExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('help', $options['help']);
        $builder->setAttribute('header', $options['header']);
        $builder->setAttribute('messages', $options['messages']);
        $builder->setAttribute('javascript', $options['javascripts']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['help'] = $form->getConfig()->getAttribute('help');
        $view->vars['header'] = $form->getConfig()->getAttribute('header');
        $view->vars['messages'] = $form->getConfig()->getAttribute('messages');
        $view->vars['javascripts'] = $form->getConfig()->getAttribute('javascripts');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            /** @Ignore */
            'help' => null,
            'header' => null,
            'messages' => [],
            'javascripts' => null,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
