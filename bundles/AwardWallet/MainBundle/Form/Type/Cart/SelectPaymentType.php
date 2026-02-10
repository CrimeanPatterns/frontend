<?php

namespace AwardWallet\MainBundle\Form\Type\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Globals\Cart\CreditCardPaymentTypeSelector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class SelectPaymentType extends AbstractType
{
    /** @var TranslatorInterface */
    protected $translator;

    protected $logger;
    private CreditCardPaymentTypeSelector $paymentTypeSelector;

    public function __construct(TranslatorInterface $translator, LoggerInterface $logger, CreditCardPaymentTypeSelector $paymentTypeSelector)
    {
        $this->translator = $translator;
        $this->logger = $logger;
        $this->paymentTypeSelector = $paymentTypeSelector;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Cart $cart */
        $cart = $options['cart'];
        $ccPaymentType = $this->paymentTypeSelector->getCreditCardPaymentType($cart);
        $this->logger->info("using credit card payment type: $ccPaymentType", ["CartID" => $cart->getCartid()]);

        $choices = [
            $ccPaymentType => "Credit Card",
            Cart::PAYMENTTYPE_PAYPAL => "PayPal",
            //            Cart::PAYMENTTYPE_BITCOIN => "Bitcoin",
            //            Cart::PAYMENTTYPE_ETHEREUM => "Ethereum",
        ];

        if ($options['debug']) {
            $choices += [
                Cart::PAYMENTTYPE_TEST_CREDITCARD => "Test credit card",
                Cart::PAYMENTTYPE_TEST_PAYPAL => "Test PayPal",
            ];
        }

        $builder->add('type', ChoiceType::class, [
            'attr' => $attrs ?? [],
            'choices' => array_flip($choices),
            'expanded' => true,
            'constraints' => [
                new NotBlank(),
                new Choice(['choices' => array_keys($choices)]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'debug' => false,
            'staff' => true,
        ]);
        $resolver->setRequired(['cart']);
    }

    public function getBlockPrefix()
    {
        return 'select_payment_type';
    }
}
