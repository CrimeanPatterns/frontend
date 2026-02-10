<?php

namespace AwardWallet\MainBundle\Form\Type\Cart;

use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class BalanceWatchCreditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = array_keys(BalanceWatchCredit::COUNT_PRICE);

        $builder
            ->add('balanceWatchCredit', ChoiceType::class, [
                'data' => $choices[0],
                'choices' => array_combine($choices, $choices),
                'constraints' => [
                    new Constraints\NotBlank(),
                    new Constraints\Choice([
                        'choices' => $choices,
                    ]),
                ],
                'attr' => [
                    'data-ng-model' => 'model.user_pay.balanceWatchCredit',
                    'convert-to-number' => '',
                ],
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'user_pay_balanceWatchCredit';
    }
}
