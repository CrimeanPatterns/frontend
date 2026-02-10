<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\CreditCardShoppingCategoryGroup;
use AwardWallet\MainBundle\Entity\ShoppingCategoryGroup;
use Sonata\Form\Type\DatePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreditCardMultipliersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('multiplier', NumberType::class, [
            'scale' => 2,
        ])
                ->add('shoppingCategoryGroup', EntityType::class, [
                    'class' => ShoppingCategoryGroup::class,
                    'choice_label' => 'name',
                    'placeholder' => /** @Ignore */ 'No Category Group(All Transactions)',
                    'required' => false,
                ])
                ->add('startDate', DatePickerType::class, [
                    'required' => false,
                ])
                ->add('endDate', DatePickerType::class, [
                    'required' => false,
                ])
                ->add('description', TextareaType::class, [
                    'allow_tags' => true,
                    'allow_urls' => true,
                ])
                ->add('sortIndex')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CreditCardShoppingCategoryGroup::class,
        ]);
    }
}
