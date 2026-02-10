<?php

namespace AwardWallet\MobileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CreditCardType extends AbstractType
{
    protected $credit_card_types = [
        "Visa" => "Visa",
        "MasterCard" => "MasterCard",
        "Discover" => "Discover",
        "Amex" => "Amex",
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', ChoiceType::class, [
            'label' => /** @Ignore */ 'Credit Card Type',
            'error_bubbling' => false,
            'placeholder' => /** @Ignore */ '-- Select --',
            'choices' => array_flip($this->credit_card_types),
        ]);
        $builder->add('number', TextType::class, [
            'label' => /** @Ignore */ 'Credit Card Number',
            'error_bubbling' => false,
        ]);
        $builder->add('code', TextType::class, [
            'label' => /** @Ignore */ 'CVV2 Code',
            'error_bubbling' => false,
        ]);

        $years = range(date("Y"), date("Y") + 15);
        $years = array_combine($years, $years);
        $builder->add('expiration', CreditCardExpirationType::class, [
            'label' => /** @Ignore */ 'Expiration Date',
            'error_bubbling' => false,
            'years' => $years,
            'format' => 'MMyyyy',
            'pattern' => '{{ month }}{{ year }}',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $collection = [];
        $collection['type'] = [
            new Constraints\NotBlank(),
            new Constraints\Choice([
                'choices' => array_keys($this->credit_card_types),
            ]),
        ];
        $collection['number'] = [
            new Constraints\NotBlank(),
            new Constraints\Length(['min' => 10, 'max' => 20, 'allowEmptyString' => true]),
            new Constraints\Regex([
                "pattern" => "/^\d+/",
                "message" => "Numeric characters only. No spaces or punctuation",
            ]),
        ];
        $collection['code'] = [
            new Constraints\NotBlank(),
            new Constraints\Length(['min' => 1, 'max' => 20, 'allowEmptyString' => true]),
            new Constraints\Regex([
                "pattern" => "/^\d+/",
                "message" => "Numeric characters only. No spaces or punctuation",
            ]),
        ];

        $collectionConstraint = new Constraints\Collection([
            'fields' => $collection,
            'allowExtraFields' => true,
        ]);

        $resolver->setDefaults([
            'constraints' => $collectionConstraint,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'credit_card';
    }
}
