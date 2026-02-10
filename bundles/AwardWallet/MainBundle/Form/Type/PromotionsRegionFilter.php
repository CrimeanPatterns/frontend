<?php

namespace AwardWallet\MainBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionsRegionFilter extends AbstractType
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $repRegion = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Region::class);
        $choices = $repRegion->getContinentsArray();
        $builder->add('regionid', ChoiceType::class, [
            'choices' => array_flip(['all' => 'All'] + $choices),
            'label' => /** @Desc("Select region") */ 'promotion.select-region.label',
            'error_bubbling' => false,
            'attr' => ['onchange' => 'changeRegion(this)'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['auth'],
            'translation_domain' => 'promotions',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'promotions_region_filter';
    }
}
