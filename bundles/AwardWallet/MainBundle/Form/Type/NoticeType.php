<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoticeType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['message'] = $options['message'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['message']);
        $resolver->setDefaults([
            'type' => 'warning',
            'required' => false,
            'mapped' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'notice';
    }

    public function getParent()
    {
        return HiddenType::class;
    }
}
