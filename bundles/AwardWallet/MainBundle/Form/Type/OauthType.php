<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OauthType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) { return $value; },
            function ($value) { return null !== $value ? str_replace('_', '/', $value) : null; }
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $provider = $options['provider'];

        $view->vars['program_name'] = $provider->getName();
        $view->vars['request_id'] = StringHandler::getRandomCode(20);
        $view->vars['program_code'] = $provider->getCode();
        $view->vars['autologin_notice'] = $options['autologin_notice'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'provider' => '',
            'autologin_notice' => true,
        ]);
    }

    public function getParent()
    {
        return HiddenType::class;
    }

    public function getBlockPrefix()
    {
        return 'oauth';
    }
}
