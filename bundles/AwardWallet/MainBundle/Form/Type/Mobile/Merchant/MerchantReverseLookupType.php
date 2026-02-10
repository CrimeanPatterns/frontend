<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\Merchant;

use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MerchantReverseLookupType extends AbstractType
{
    /**
     * @var MobileExtensionLoader
     */
    private $mobileExtensionLoader;

    public function __construct(MobileExtensionLoader $mobileExtensionLoader)
    {
        $this->mobileExtensionLoader = $mobileExtensionLoader;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('credit_card', ChoiceType::class, [
                'required' => false,
                'label' => /** @Ignore */ 'Credit Card',
            ])
            ->add('category', ChoiceType::class, [
                'required' => false,
                'label' => /** @Ignore */ 'Category',
            ])
            ->add('choice_data', HiddenType::class, [
                'data' => $options['data'],
            ]);

        $this->mobileExtensionLoader->loadExtensionByPath($builder, 'engine/awextension/form/MerchantReverseExtension.js');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('csrf_protection', false);
    }
}
