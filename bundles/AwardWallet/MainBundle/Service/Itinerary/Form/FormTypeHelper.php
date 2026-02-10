<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form;

use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Form\Type\OwnerMetaType;
use AwardWallet\MainBundle\Service\Itinerary\Form\Type\NotesType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FormTypeHelper
{
    public function addOwner(FormBuilderInterface $builder)
    {
        $builder
            ->add('owner', OwnerMetaType::class, [
                'designation' => OwnerRepository::FOR_ITINERARY_ASSIGNMENT,
                'required' => true,
            ]);
    }

    public function addConfirmationNumber(FormBuilderInterface $builder, bool $required)
    {
        $isNA = fn ($value): bool => \preg_match('/^n\/a$/i', \trim($value));

        $builder
            ->add('confirmationNumber', TextType::class, [
                'label' => 'itineraries.confirmation-number',
                'required' => $required,
                'translation_domain' => 'trips',
            ])
            ->get('confirmationNumber')
            ->addModelTransformer(new CallbackTransformer(
                fn ($value) => $isNA($value) ? '' : $value,
                fn ($value) => $isNA($value) ? null : $value
            ));
    }

    public function addNotes(FormBuilderInterface $builder)
    {
        $builder
            ->add('notes', NotesType::class, [
                'label' => 'itineraries.notes',
                'translation_domain' => 'trips',
                'required' => false,
                'allow_quotes' => true,
                'allow_tags' => true,
                'allow_urls' => true,
                'attr' => [
                    'style' => 'display:none !important;',
                ],
            ]);
    }

    public function addDepartureDate(FormBuilderInterface $builder)
    {
        $builder
            ->add('departureDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'label' => 'itineraries.trip.dep-date',
                'translation_domain' => 'trips',
                'invalid_message' => 'invalid_date_and_time',
                'html5' => false,
            ]);
    }

    public function addArrivalDate(FormBuilderInterface $builder)
    {
        $builder
            ->add('arrivalDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'label' => 'itineraries.trip.arr-date',
                'translation_domain' => 'trips',
                'invalid_message' => 'invalid_date_and_time',
                'html5' => false,
            ]);
    }
}
