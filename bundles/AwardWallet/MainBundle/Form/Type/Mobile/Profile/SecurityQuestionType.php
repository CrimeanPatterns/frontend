<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\Profile;

use AwardWallet\MainBundle\Form\Model\Profile\SecurityQuestionModel;
use AwardWallet\MainBundle\Form\Type\ProfileSecurityQuestionEntryType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SecurityQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('questions', CollectionType::class, [
            'entry_type' => ProfileSecurityQuestionEntryType::class,
            'entry_options' => ['label' => false],
            'allow_add' => false,
            'allow_delete' => false,
        ]);

        $builder->setAttribute('submit_label', 'form.button.update');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SecurityQuestionModel::class,
            'error_bubbling' => false,
            'label' => false,
            'translation_domain' => 'messages',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'profile_security_question_mobile';
    }
}
