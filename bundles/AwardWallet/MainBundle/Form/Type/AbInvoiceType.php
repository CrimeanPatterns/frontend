<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class AbInvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $passengers = [];

        /** @var Usr $user */
        foreach ($options['attr']['request']->getPassengers() as $user) {
            $fullname = $user->getFullName();
            $passengers[$fullname] = $fullname;
        }

        $builder
            ->add('Items', CollectionType::class, [
                'entry_type' => AbInvoiceItemType::class,
                /** @Ignore */
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'constraints' => new Valid(),
            ])
            ->add('Miles', CollectionType::class, [
                'entry_type' => AbInvoiceMilesType::class,
                /** @Ignore */
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'entry_options' => ['attr' => ['passengers' => $passengers]],
                'constraints' => new Valid(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AbInvoice::class,
            /** @Ignore */
            'label' => false,
            'constraints' => new Valid(),
            'translation_domain' => 'booking',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'booking_request_invoice';
    }
}
