<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\Schema\Form;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Service\RA\Flight\Api;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RAFlightSearchQueryType extends AbstractType
{
    private RAFlightSearchQueryTransformer $transformer;

    private Api $api;

    public function __construct(RAFlightSearchQueryTransformer $transformer, Api $api)
    {
        $this->transformer = $transformer;
        $this->api = $api;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
        $parserList = $this->api->getParserList();
        $parserChoices = array_flip(array_map(fn (array $parser) => $parser['name'], $parserList));

        $builder
            ->add('fromAirports', TextType::class, [
                'label' => /** @Ignore */ 'From',
                'help' => /** @Ignore */ 'One value or several separated by commas',
            ])
            ->add('toAirports', TextType::class, [
                'label' => /** @Ignore */ 'To',
                'help' => /** @Ignore */ 'One value or several separated by commas',
            ])
            ->add('fromDate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('toDate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('flightClass', ChoiceType::class, [
                'label' => /** @Ignore */ 'Class of Service',
                'attr' => ['class' => 'col-3'],
                'choices' => [
/** @Ignore */ 'Any' => RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
/** @Ignore */ 'Economy & Premium Economy' => RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY,
/** @Ignore */ 'Business & First' => RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
/** @Ignore */ 'Economy' => RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
/** @Ignore */ 'Premium Economy' => RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY,
/** @Ignore */ 'Business' => RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS,
/** @Ignore */ 'First' => RAFlightSearchQuery::FLIGHT_CLASS_FIRST,

/** @Ignore */ 'Economy & Business' => RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS,
/** @Ignore */ 'Economy & First' => RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
/** @Ignore */ 'Premium Economy & Business' => RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS,
/** @Ignore */ 'Premium Economy & First' => RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
/** @Ignore */ 'Economy & Premium Economy & Business' => RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS,
/** @Ignore */ 'Economy & Premium Economy & First' => RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
/** @Ignore */ 'Economy & Business & First' => RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
/** @Ignore */ 'Premium Economy & Business & First' => RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
                ],
                'preferred_choices' => [
                    RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
                    RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY,
                    RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
                    RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
                    RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY,
                    RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS,
                    RAFlightSearchQuery::FLIGHT_CLASS_FIRST,
                ],
            ])
            ->add('adults', ChoiceType::class, [
                'label' => /** @Ignore */ 'Number of Passengers',
                'attr' => ['class' => 'col-3'],
                'choices' => array_combine(range(1, 9), range(1, 9)),
            ])
            ->add('searchInterval', ChoiceType::class, [
                'label' => /** @Ignore */ 'Repeat',
                'attr' => ['class' => 'col-3'],
                'choices' => [
/** @Ignore */ 'Once' => RAFlightSearchQuery::SEARCH_INTERVAL_ONCE,
/** @Ignore */ 'Daily' => RAFlightSearchQuery::SEARCH_INTERVAL_DAILY,
/** @Ignore */ 'Weekly' => RAFlightSearchQuery::SEARCH_INTERVAL_WEEKLY,
                ],
            ])
            ->add('autoSelectParsers', CheckboxType::class, [
                'label' => /** @Ignore */ 'Select Parsers Automatically',
            ])
            ->add('excludeParsers', ChoiceType::class, [
                'label' => /** @Ignore */ 'Exclude Parsers',
                'attr' => ['class' => 'col-4', 'size' => 15],
                'choices' => $parserChoices,
                'multiple' => true,
                'expanded' => false,
                'help' => /** @Ignore */ 'Additional filtering of search results. These parsers will not be excluded from the search. Only the search results from these parsers will be filtered.',
            ])
            ->add('parsers', ChoiceType::class, [
                'label' => /** @Ignore */ 'Parsers',
                'attr' => ['class' => 'col-4', 'size' => 15],
                'choices' => $parserChoices,
                'multiple' => true,
                'expanded' => false,
            ])
            ->add('economyMilesLimit', TextType::class, [
                'label' => /** @Ignore */ 'Economy Miles Limit',
                'attr' => ['class' => 'col-3'],
                'help' => /** @Ignore */ 'Per one passenger',
            ])
            ->add('premiumEconomyMilesLimit', TextType::class, [
                'label' => /** @Ignore */ 'Premium Economy Miles Limit',
                'attr' => ['class' => 'col-3'],
                'help' => /** @Ignore */ 'Per one passenger',
            ])
            ->add('businessMilesLimit', TextType::class, [
                'label' => /** @Ignore */ 'Business Miles Limit',
                'attr' => ['class' => 'col-3'],
                'help' => /** @Ignore */ 'Per one passenger',
            ])
            ->add('firstMilesLimit', TextType::class, [
                'label' => /** @Ignore */ 'First Miles Limit',
                'attr' => ['class' => 'col-3'],
                'help' => /** @Ignore */ 'Per one passenger',
            ])
            ->add('maxTotalDuration', NumberType::class, [
                'label' => /** @Ignore */ 'Maximum duration (hours)',
                'attr' => ['class' => 'col-3'],
                'rounding_mode' => NumberToLocalizedStringTransformer::ROUND_HALF_UP,
                'scale' => 2,
                'help' => /** @Ignore */ 'Max duration of all flights including layovers.
                    For example, a value of 10.5 will only save results with a total duration of 10h 30min or less.
                ',
            ])
            ->add('maxSingleLayoverDuration', NumberType::class, [
                'label' => /** @Ignore */ 'Maximum single layover (hours)',
                'attr' => ['class' => 'col-3'],
                'rounding_mode' => NumberToLocalizedStringTransformer::ROUND_HALF_UP,
                'scale' => 2,
                'help' => /** @Ignore */ 'Max duration of a single layover.
                    For example, if the value is 5.0, no itinerary will be saved that has a layover of > 5.0 hours.
                ',
            ])
            ->add('maxTotalLayoverDuration', NumberType::class, [
                'label' => /** @Ignore */ 'Maximum total layover (hours)',
                'attr' => ['class' => 'col-3'],
                'rounding_mode' => NumberToLocalizedStringTransformer::ROUND_HALF_UP,
                'scale' => 2,
                'help' => /** @Ignore */ 'Max duration of all layovers combined.
                    For example, if the value is 6.0, an itinerary with 2 stops; 3h and 4h20m will not be saved because 3 + 4.333 > 6.0.
                ',
            ])
            ->add('maxStops', IntegerType::class, [
                'label' => /** @Ignore */ 'Maximum stops',
                'attr' => ['class' => 'col-3'],
                'help' => /** @Ignore */ 'Maximum number of stops
                    For example, if the value is 1, no itineraries with 2 stops or more will be saved.
                ',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            $buttonAttr = ['attr' => ['class' => 'btn btn-primary btn-lg']];

            if ($data->getId()) {
                $form->add('update', SubmitType::class, $buttonAttr);
            } else {
                $form->add('save', SubmitType::class, $buttonAttr);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RAFlightSearchQueryModel::class,
            'required' => false,
        ]);
    }
}
