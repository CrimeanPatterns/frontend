<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MobileBundle\Form\Transformer\FloatBalanceTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlexibleFloatType extends AbstractType
{
    /**
     * @var FloatBalanceTransformer
     */
    private $floatBalanceTransformer;

    public function __construct(FloatBalanceTransformer $floatBalanceTransformer)
    {
        $this->floatBalanceTransformer = $floatBalanceTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->floatBalanceTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getParent()
    {
        return TextType::class;
    }

    public function getBlockPrefix()
    {
        return 'flex_float';
    }
}
