<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Transformer\CardNumberTransformer;
use AwardWallet\MainBundle\Globals\Cart\CartUserSource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class CardInfoType extends AbstractType
{
    private TranslatorInterface $translator;
    private CartUserSource $cartUserSource;
    private RouterInterface $router;

    public function __construct(
        TranslatorInterface $translator,
        CartUserSource $cartUserSource,
        RouterInterface $router
    ) {
        $this->translator = $translator;
        $this->cartUserSource = $cartUserSource;
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $yearRange = range(date("Y"), date("Y", strtotime('+10 year')));

        $empty = '';
        $user = $this->cartUserSource->getPayer();

        $builder
            ->add(
                'full_name',
                TextType::class,
                [
                    'required' => true,
                    'label' => $this->translator->trans(
                        "cart.full-name"
                    ),
                    'help' => $this->translator->trans(
                        /** @Desc("as it appears on the credit card") */
                        "card.number.help"
                    ),
                    'attr' => [
                        'autocomplete' => 'off',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'data' => $user->getFirstname() . ' ' . $user->getLastname(),
                ]
            )
            ->add(
                'card_number',
                TextType::class,
                [
                    'required' => true,
                    'label' => $this->translator->trans(
                        /** @Desc("Credit Card Number") */
                        "card.number"
                    ),
                    'attr' => [
                        'autocomplete' => 'off',
                        'inputmode' => 'numeric',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\CardScheme([
                            'schemes' => [
                                'VISA',
                                'AMEX',
                                'DISCOVER',
                                'MASTERCARD',
                            ],
                            'message' => $this->translator->trans(
                                /** @Desc("Your credit card number is invalid.") */
                                "card.number.invalid"
                            ),
                        ]),
                    ],
                    'help' => $this->translator->trans(
                        /** @Desc("AwardWallet is %link_on%coded as Travel%link_off%") */
                        'awardwallet-coded-as-travel',
                        [
                            '%link_on%' => '<a href="'
                                . $this->router->generate('aw_merchant_lookup_preload', [
                                    'merchantName' => 'Awardwallet+Llc_62246920',
                                    'categoryGroupId' => 5,
                                ])
                                . '" target="_blank">',
                            '%link_off%' => '</a>',
                        ]
                    ),
                ]
            )
            ->add(
                'security_code',
                TextType::class,
                [
                    'required' => true,
                    'label' => $this->translator->trans(
                        /** @Desc("Security Code") */
                        "card.security_code"
                    ),
                    'attr' => [
                        'autocomplete' => 'off',
                        'maxlength' => 4,
                        'minlength' => 3,
                        'pattern' => '^\d{3,4}$',
                    ],
                    'label_attr' => [
                        'class' => 'blue bold',
                        'id' => 'security-code',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Regex([
                            'pattern' => '/^\d{3,4}$/',
                            'message' => $this->translator->trans("card.security_code.invalid"),
                        ]),
                    ],
                ]
            )
            ->add(
                'expiration_month',
                ChoiceType::class,
                [
                    'required' => true,
                    'label' => $this->translator->trans(
                        /** @Desc("Expiration Date") */
                        "card.expiration"
                    ),
                    'choices' => array_combine(range(1, 12), range(1, 12)),
                    'placeholder' => $empty,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Choice([
                            'choices' => range(1, 12),
                        ]),
                    ],
                ]
            )
            ->add(
                'expiration_year',
                ChoiceType::class,
                [
                    'required' => true,
                    /** @Ignore */
                    'choices' => array_combine($yearRange, $yearRange),
                    'placeholder' => $empty,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Choice([
                            'choices' => $yearRange,
                        ]),
                    ],
                ]
            );

        $builder->get('card_number')->addViewTransformer(new CardNumberTransformer());
    }

    public function getBlockPrefix()
    {
        return 'card_info';
    }
}
