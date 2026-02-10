<?php
/**
 * Created by PhpStorm.
 * User: norgen
 * Date: 21.04.14
 * Time: 15:05.
 */

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('login', TextType::class);
        $builder->add('password', PasswordMaskType::class);
        $builder->add('rememberme', CheckboxType::class, ['mapped' => false, 'required' => false, 'label' => 'login.keep']);
    }

    public function getBlockPrefix()
    {
        return 'user_login';
    }
}
