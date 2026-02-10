<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\EventModel;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractItineraryType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->helper->addOwner($builder);

        $builder->add('eventType', ChoiceType::class, [
            'label' => 'itineraries.event.type',
            'choices' => [
                'event.type.conference' => Restaurant::EVENT_CONFERENCE,
                'event.type.meeting' => Restaurant::EVENT_MEETING,
                'event.type.show' => Restaurant::EVENT_SHOW,
                'event.type.restaurant' => Restaurant::EVENT_RESTAURANT,
                'event.type.event' => Restaurant::EVENT_EVENT,
                'event.type.rave' => Restaurant::EVENT_RAVE,
            ],
        ]);

        $this->helper->addConfirmationNumber($builder, false);

        $builder
            ->add('title', TextType::class, [
                'label' => 'itineraries.event.title',
                'allow_quotes' => true,
            ])
            ->add('startDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'label' => 'itineraries.restaurant.start-date',
                'invalid_message' => 'invalid_date_and_time',
                'html5' => false,
            ])
            ->add('endDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'label' => 'itineraries.restaurant.end-date',
                'invalid_message' => 'invalid_date_and_time',
                'html5' => false,
            ])
            ->add('address', GoogleAddressAutocompleteType::class, [
                'label' => 'itineraries.address',
            ])
            ->add('phone', TextType::class, [
                'label' => 'itineraries.phone',
                'required' => false,
            ]);

        $this->helper->addNotes($builder);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventModel::class,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'trips',
        ]);
    }

    /**
     * @deprecated
     */
    public function getBlockPrefix()
    {
        return 'event';
    }
}
