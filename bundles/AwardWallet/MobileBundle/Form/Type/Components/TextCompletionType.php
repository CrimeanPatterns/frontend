<?php

namespace AwardWallet\MobileBundle\Form\Type\Components;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextCompletionType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['completionLink'] = $options['completionLink'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'completionList' => null,
            'completionLink' => null,
            'attachedAccounts' => [],
            'providerKind' => null,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'text_completion';
    }

    public function getParent()
    {
        return TextType::class;
    }
}
