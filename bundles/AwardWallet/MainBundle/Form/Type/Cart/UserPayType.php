<?php

namespace AwardWallet\MainBundle\Form\Type\Cart;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserPayType extends AbstractType
{
    protected $maxCards = 10;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $minOneCards = $options['with_upgrade_account'] ? 0 : 1;
        $oneCardRange = range($minOneCards, $this->maxCards);

        $builder
            ->add('onecard', ChoiceType::class, [
                'choices' => array_combine($oneCardRange, $oneCardRange),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Choice([
                        'choices' => $oneCardRange,
                    ]),
                ],
                'attr' => [
                    'data-ng-model' => 'model.user_pay.onecard',
                    'convert-to-number' => '',
                ],
            ])
            ->add('awPlus', HiddenType::class, [
                'attr' => [
                    'ng-value' => 'model.giveAWPlus',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'with_upgrade_account' => true,
            'label' => /** @Ignore */ false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'user_pay';
    }
}
