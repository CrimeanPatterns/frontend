<?php

namespace AwardWallet\MobileBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordMaskType extends PasswordType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->getConfig()->getOption('always_empty')) {
            // do not clear register form password fields on navigation to "terms of use" page and backwards
            $formTraverser = $form;

            while (($formTraverser = $formTraverser->getParent()) instanceof Form) {
                $formConfig = $formTraverser->getConfig();

                if ($formConfig instanceof FormConfigInterface) {
                    $type = $formConfig->getType();

                    if ($type instanceof ResolvedFormTypeInterface && ($type->getInnerType() instanceof NewUserType || $type->getInnerType() instanceof NewDesign\NewUserType)) {
                        return;
                    }
                }
            }

            $value = str_pad('', mb_strlen($form->getData()), '*');
            $view->vars['value'] = $value;

            if ($parent = $view->parent) {
                $parent->vars['value'] = $value;
            }
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $e) {
            // if password wasn't set at all(even empty) use original value.
            if ($e->getData() === null) {
                $e->setData($e->getForm()->getData());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'always_empty' => true,
            'trim' => false,
            'allow_tags' => true,
            'allow_quotes' => true,
            'allow_urls' => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'passwordMask';
    }
}
