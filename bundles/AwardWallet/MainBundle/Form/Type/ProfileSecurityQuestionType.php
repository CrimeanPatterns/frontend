<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Model\Profile\SecurityQuestionModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileSecurityQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('questions', CollectionType::class, [
            'entry_type' => ProfileSecurityQuestionEntryType::class,
            'entry_options' => ['label' => false],
            'allow_add' => false,
            'allow_delete' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SecurityQuestionModel::class,
            'error_bubbling' => false,
            'translation_domain' => 'messages',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'profile_security_question';
    }
}
