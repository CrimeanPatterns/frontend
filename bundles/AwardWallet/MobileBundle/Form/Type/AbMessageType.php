<?php

namespace AwardWallet\MobileBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Form\Transformer\AbMessagePostTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AbMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('message', TextareaType::class, [
            'allow_quotes' => true,
            'allow_tags' => true,
            'allow_urls' => true,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 20000]),
            ],
            'property_path' => 'Post',
        ]);

        $builder->get('message')
            ->addModelTransformer(new AbMessagePostTransformer());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AbMessage::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'mobile_ab_message';
    }
}
