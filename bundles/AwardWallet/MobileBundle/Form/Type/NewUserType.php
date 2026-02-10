<?php

namespace AwardWallet\MobileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewUserType extends AbstractType
{
    protected $options;

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('login', TextType::class, [
            'label' => /** @Ignore */ 'Username',
            'attr' => ['notice' => '4 characters or longer'],
            'error_bubbling' => false,
        ]);
        $firstData = (isset($this->options['data']['pass']['first'])) ? $this->options['data']['pass']['first'] : '';
        $secondData = (isset($this->options['data']['pass']['second'])) ? $this->options['data']['pass']['second'] : '';
        $builder->add('pass', RepeatedType::class, [
            'type' => PasswordMaskType::class,
            'label' => /** @Ignore */ 'Password',
            'invalid_message' => /** @Ignore */ 'The password fields must match',
            'first_options' => ['label' => /** @Ignore */ 'Password', 'data' => $firstData],
            'second_options' => ['label' => /** @Ignore */ 'Confirm password', 'data' => $secondData],
            'options' => ['attr' => ['notice' => '4 characters or longer to be safe'], 'trim' => true],
            'error_bubbling' => false,
        ]);
        $builder->add('firstname', TextType::class, [
            'label' => /** @Ignore */ 'First name',
            'error_bubbling' => false,
        ]);
        $builder->add('lastname', TextType::class, [
            'label' => /** @Ignore */ 'Last name',
            'error_bubbling' => false,
        ]);
        $builder->add('email', EmailType::class, [
            'label' => /** @Ignore */ 'Email',
            'error_bubbling' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // TODO: remove 'withoutUnique' workaround after NewDesign release
        $validationGroups = isset($this->options['withoutUnique']) && $this->options['withoutUnique'] ? ['register'] : ['register', 'unique'];
        $resolver->setDefaults([
            'validation_groups' => $validationGroups,
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\Usr',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'new_user';
    }
}
