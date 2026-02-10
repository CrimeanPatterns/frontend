<?php

namespace AwardWallet\MainBundle\Form\Type\AccountList;

use AwardWallet\MainBundle\Entity\Account;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Форма, отвечающая за изменение параметра "архивный" у аккаунтов и купонов.
 */
class ChangeAccountArchiveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('accounts', CollectionType::class, [
            'allow_add' => true,
            'allow_delete' => true,
            'constraints' => [
                new Valid(),
                new Assert\Count(['min' => 1, 'max' => 500]),
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'array']),
            ],
            'delete_empty' => true,
            'entry_options' => [
                'constraints' => [
                    new Assert\Regex(['pattern' => '/[ac]?\d+/']),
                ],
            ],
            'entry_type' => TextType::class,
        ]);
        $builder->add('isArchived', IntegerType::class, [
            'constraints' => [
                new Assert\Choice(['choices' => [Account::NOT_ARCHIVED, Account::ARCHIVED]]),
                new Assert\NotBlank(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'form';
    }
}
