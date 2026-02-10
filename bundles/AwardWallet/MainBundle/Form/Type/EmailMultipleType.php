<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailMultipleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                return $value;
            },
            function ($value) {
                $emails = preg_split('/[;, ]+/', $value);
                $emails = array_map('trim', $emails);
                $emails = array_map('strtolower', $emails);
                $emails = array_unique($emails);

                return implode(', ', $emails);
            }
        ));
    }

    public function getParent()
    {
        return EmailType::class;
    }

    public function getBlockPrefix()
    {
        return 'email_multiple';
    }
}
