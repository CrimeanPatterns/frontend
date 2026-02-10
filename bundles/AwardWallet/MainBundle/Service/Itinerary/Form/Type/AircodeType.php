<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\Transformer\AircodeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AircodeType extends AbstractType
{
    private AircodeTransformer $aircodeTransformer;

    public function __construct(AircodeTransformer $aircodeTransformer)
    {
        $this->aircodeTransformer = $aircodeTransformer;
    }

    public function getParent()
    {
        return TextType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $airport = '';

        if ($data = $form->getData()) {
            $airport = $data->getAirname();
        }

        $view->vars['airport'] = $airport;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->aircodeTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'invalid_message' => 'trips.invalid_airport_code',
            'airport' => '',
        ]);
    }
}
