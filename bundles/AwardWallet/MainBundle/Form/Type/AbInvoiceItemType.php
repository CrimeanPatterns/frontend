<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Model\Booking\InvoiceItemModel;
use AwardWallet\MainBundle\Form\Transformer\InvoiceItemTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbInvoiceItemType extends AbstractType
{
    /**
     * @var InvoiceItemTransformer
     */
    private $dataTransformer;

    public function __construct(InvoiceItemTransformer $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->dataTransformer);
        $builder
            ->add('description', TextType::class, [
                'allow_quotes' => true,
                'label' => 'invoice.message.description',
                'attr' => ['class' => 'desc-autocomplete', 'maxlength' => 255],
            ])
            ->add('quantity', TextType::class, [
                'label' => 'user-pay.column.quantity.title',
                'attr' => ['maxlength' => 5],
                'translation_domain' => 'messages',
            ])
            ->add('price', TextType::class, [
                'label' => 'user-pay.column.price.title',
                'attr' => ['maxlength' => 10],
                'translation_domain' => 'messages',
            ])
            ->add('discount', IntegerType::class, [
                'required' => false,
                'label' => 'booking.forms.invoice.discount',
                'attr' => ['maxlength' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InvoiceItemModel::class,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'booking',
            'attr' => ['autocomplete' => 'off'],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'booking_request_invoice_item';
    }
}
