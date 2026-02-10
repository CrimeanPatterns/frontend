<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\CreditCardShoppingCategoryGroup;
use Sonata\Form\Type\DatePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShoppingCategoryGroupMultipliersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('multiplier', NumberType::class, [
            'scale' => 2,
        ])
                ->add('creditCard', EntityType::class, [
                    'class' => 'AwardWallet\MainBundle\Entity\CreditCard',
                    'choice_label' => 'name',
                    'required' => true,
                ])
                ->add('startDate', DatePickerType::class, [
                    'required' => false,
                ])
                ->add('endDate', DatePickerType::class, [
                    'required' => false,
                ])
                ->add('sortIndex')
                ->add('description', TextareaType::class, [
                    'allow_tags' => true,
                    'allow_urls' => true,
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CreditCardShoppingCategoryGroup::class,
        ]);
    }
}
