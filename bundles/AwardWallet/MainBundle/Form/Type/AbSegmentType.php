<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbSegment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbSegmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentYear = date('Y');
        $maxYear = date('Y') + 20;
        $builder->add('Dep', TextType::class, [
            'label' => 'booking.request.add.form.segment.from',
            'required' => true,
            'attr' => [
                'class' => 'airport-autocomplete',
                'style' => 'text-transform: uppercase',
                'maxlength' => 250,
            ],
        ]);
        // Make sure the airport code is always uppercase
        $builder->get('Dep')->addModelTransformer(new CallbackTransformer(
            fn ($dep) => $dep,
            fn ($dep) => strtoupper($dep)
        ));
        $builder->add('DepCheckOtherAirports', CheckboxType::class, [
            'label' => /** @Desc("Also check other airports in the metro area, if available") */ 'booking.check-other-airports',
            'required' => false,
        ]);
        $builder->add('Arr', TextType::class, [
            'label' => 'booking.request.add.form.segment.to',
            'required' => true,
            'attr' => [
                'class' => 'airport-autocomplete',
                'style' => 'text-transform: uppercase',
                'maxlength' => 250,
            ],
        ]);
        // Make sure the airport code is always uppercase
        $builder->get('Arr')->addModelTransformer(new CallbackTransformer(
            fn ($arr) => $arr,
            fn ($arr) => strtoupper($arr)
        ));
        $builder->add('ArrCheckOtherAirports', CheckboxType::class, [
            'label' => /** @Desc("Also check other airports in the metro area, if available") */ 'booking.check-other-airports',
            'required' => false,
        ]);
        $builder->add('DepDateIdeal', DatePickerType::class, [
            'label' => 'booking.request.add.form.segment.date.dep',
            'datepicker_options' => [
                'onSelect' => 'function(val, elem) { $.proxy(Segments.dateIdealChanged, this)(val, elem); }',
                'yearRange' => "$currentYear:$maxYear",
            ],
            'required' => false,
        ]);
        $builder->add('DepDateFlex', ChoiceType::class, [
            'label' => /** @Desc("Do you have flexibility on your date to leave?") */ 'booking.dep_date.flex',
            'required' => true,
            'expanded' => true,
            'choices' => [
                'no' => false,
                'yes' => true,
            ],
        ]);
        $builder->get('DepDateFlex')->addModelTransformer(new CallbackTransformer(
            function ($bool) {return $bool; },
            function ($int) {return $int == 1 ? true : false; }
        )
        );
        $builder->add('DepDateFrom', DatePickerType::class, [
            'label' => 'booking.request.add.form.segment.date.earliest',
            'datepicker_options' => [
                'onSelect' => 'function(val, elem) { $.proxy(Segments.dateFromChanged, this)(val, elem); }',
                'yearRange' => "$currentYear:$maxYear",
            ],
            'required' => false,
        ]);
        $builder->add('DepDateTo', DatePickerType::class, [
            'label' => 'booking.request.add.form.segment.date.latest',
            'datepicker_options' => [
                'onSelect' => 'function(val, elem) { $.proxy(Segments.dateToChanged, this)(val, elem); }',
                'yearRange' => "$currentYear:$maxYear",
            ],
            'required' => false,
        ]);

        $builder->add('ReturnDateIdeal', DatePickerType::class, [
            'label' => 'booking.request.add.form.segment.date.arr',
            'datepicker_options' => [
                'onSelect' => 'function(val, elem) { $.proxy(Segments.dateIdealChanged, this)(val, elem); }',
                'yearRange' => "$currentYear:$maxYear",
            ],
            'required' => false,
        ]);
        $builder->add('ReturnDateFlex', ChoiceType::class, [
            'label' => /** @Desc("Do you have flexibility on your date to return?") */ 'booking.return_date.flex',
            'required' => true,
            'expanded' => true,
            'choices' => [
                'no' => false,
                'yes' => true,
            ],
        ]);

        $builder->get('ReturnDateFlex')->addModelTransformer(new CallbackTransformer(
            function ($bool) {return $bool; },
            function ($int) {return $int == 1 ? true : false; }
        )
        );
        $builder->add('ReturnDateFrom', DatePickerType::class, [
            'label' => 'booking.request.add.form.segment.date.earliest',
            'datepicker_options' => [
                'onSelect' => 'function(val, elem) { $.proxy(Segments.dateFromChanged, this)(val, elem); }',
                'yearRange' => "$currentYear:$maxYear",
            ],
            'required' => false,
        ]);
        $builder->add('ReturnDateTo', DatePickerType::class, [
            'label' => 'booking.request.add.form.segment.date.latest',
            'datepicker_options' => [
                'onSelect' => 'function(val, elem) { $.proxy(Segments.dateToChanged, this)(val, elem); }',
                'yearRange' => "$currentYear:$maxYear",
            ],
            'required' => false,
        ]);
        $builder->add('RoundTrip', ChoiceType::class, [
            /** @Ignore */
            'label' => false,
            'choices' => [
                /** @Desc("One way") */
                'booking.request.add.form.segment.one' => AbSegment::ROUNDTRIP_ONEWAY,
                /** @Desc("Round trip") */
                'booking.request.add.form.segment.round' => AbSegment::ROUNDTRIP_ROUND,
                /** @Desc("Multiple destinations") */
                'booking.request.add.form.segment.multiple' => AbSegment::ROUNDTRIP_MULTIPLE,
            ],
            'required' => false,
            'multiple' => false,
            'expanded' => true,
            /** @Ignore */
            'placeholder' => null,
        ]);

        $builder->add('RoundTripDaysIdeal', IntegerType::class, [
            /** @Desc("Number of days") */
            'label' => 'booking.request.add.form.segment.round-trip-days',
            'required' => false,
            'attr' => ['min' => 1, 'max' => 30, 'step' => 1, 'class' => 'small-input'],
        ]);

        $builder->add('RoundTripDaysFlex', ChoiceType::class, [
            'label' => /** @Desc("Do you have flexibility on the number of days of your trip, excluding flight days?") */ 'booking.round_trip_days.flex',
            'required' => true,
            'expanded' => true,
            'choices' => [
                'no' => false,
                'yes' => true,
            ],
        ]);
        $builder->get('RoundTripDaysFlex')->addModelTransformer(new CallbackTransformer(
            function ($bool) {return $bool; },
            function ($int) {return $int == 1 ? true : false; }
        )
        );

        $builder->add('RoundTripDaysFrom', HiddenType::class, [
            'required' => false,
        ]);
        $builder->add('RoundTripDaysTo', HiddenType::class, [
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\AbSegment',
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'booking',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'booking_segment';
    }
}
