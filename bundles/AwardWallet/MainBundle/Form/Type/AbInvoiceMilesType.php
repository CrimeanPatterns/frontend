<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbInvoiceMiles;
use AwardWallet\MainBundle\Form\Transformer\BalanceTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbInvoiceMilesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('CustomName', null, [
                'label' => 'booking.forms.invoice.miles.program',
                'attr' => ['class' => 'cp-autocomplete'],
            ])
            ->add('Owner', ChoiceType::class, [
                'label' => 'booking.forms.invoice.miles.holder',
                'choices' => array_flip($options['attr']['passengers']),
            ])
            ->add('Balance', null, ['label' => 'booking.forms.invoice.miles.miles']);
        $builder->get('Balance')->addViewTransformer(new BalanceTransformer());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AbInvoiceMiles::class,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'booking',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'booking_request_invoice_miles';
    }
}
