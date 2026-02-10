<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Model\Profile\CouponModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileCouponType extends AbstractType
{
    private DataTransformerInterface $dataTransformer;

    public function __construct(DataTransformerInterface $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('coupon', TextType::class, [
            'label' => /** @Desc("Coupon code") */ 'user.coupon.form.label',
            'allow_urls' => true,
        ]);
        $builder->addModelTransformer($this->dataTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false,
            'data_class' => CouponModel::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'desktop_profile_coupon';
    }
}
