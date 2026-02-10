<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbRequestStatus;
use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator;

class AbRequestPropertiesType extends AbstractType
{
    /** @var AbRequest */
    protected $data;

    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var Validator\ValidatorInterface */
    protected $validator;

    /** @TODO: replace LegacyValidator and validator service */

    /** @IgnoreAnnotation("validator") */
    public function __construct(EntityManager $em, Validator\ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $statusOptions = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->statuses;
        unset($statusOptions[AbRequest::BOOKING_STATUS_BOOKED_OPENED]);

        $cancelOptions = ['' => ''] + $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->reasons;

        $builder->add(
            'Status',
            ChoiceType::class,
            [
                'label' => 'booking.request.properties.form.change_status_to',
                'choices' => array_flip($statusOptions),
                'constraints' => [
                    new Assert\NotBlank(['groups' => ['properties']]),
                ],
                'required' => false,
            ]
        );
        $builder->add(
            'CancelReason',
            ChoiceType::class,
            [
                'choices' => array_flip($cancelOptions),
                'label' => 'booking.request.properties.form.cancel_reason',
                'required' => false,
            ]
        );
        $builder->add(
            'UntilDate',
            DatePickerType::class,
            [
                'mapped' => false,
                'label' => 'booking.request.properties.form.until_date',
                'required' => false,
                'datepicker_options' => [
                    'yearRange' => 'c-10:c+20',
                ],
            ]
        );

        $builder->add(
            'inboundAirline',
            EntityType::class,
            [
                'class' => Airline::class,
                'query_builder' => function (AirlineRepository $er) {
                    $qb = $er->createQueryBuilder('r');

                    return $qb->where($qb->expr()->in('r.code', AbRequest::BOUND_AIRLINES_CODES))
                              ->andWhere($qb->expr()->eq('r.active', true))
                              ->orderBy('r.name', 'ASC');
                },
                'label' => 'booking.request.properties.form.inbound_airline',
                'placeholder' => 'booking.request.properties.form.please-select',
                'required' => false,
            ]
        );
        $builder->add(
            'outboundAirline',
            EntityType::class,
            [
                'class' => Airline::class,
                'query_builder' => function (AirlineRepository $er) {
                    $qb = $er->createQueryBuilder('r');

                    return $qb->where($qb->expr()->in('r.code', AbRequest::BOUND_AIRLINES_CODES))
                              ->andWhere($qb->expr()->eq('r.active', true))
                              ->orderBy('r.name', 'ASC');
                },
                'label' => 'booking.request.properties.form.outbound_airline',
                'placeholder' => 'booking.request.properties.form.please-select',
                'required' => false,
            ]
        );

        $builder->add(
            'MarkAsUnread',
            CheckboxType::class,
            [
                'mapped' => false,
                'label' => 'booking.request.properties.form.mark_as_unread',
                'required' => false,
            ]
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var AbRequest $data */
                $data = $event->getData();

                if (!empty($data)) {
                    $this->setAssignedOptions($event->getForm(), $data);
                    $this->setInternalStatusOptions($event->getForm(), $data);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                /** @var AbRequest $data */
                $data = $event->getData();

                if (!empty($data)) {
                    $event->getForm()->get('UntilDate')->setData($data->getRemindDate());
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                // save Name.POST_SUBMIT, because we have not this information in field handler
                $this->data = $event->getData();
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                // todo refactor this!
                $NotBlank = new Assert\NotBlank();

                switch ($event->getForm()->get('Status')->getData()) {
                    case AbRequest::BOOKING_STATUS_FUTURE:
                        $errors = $this->validator->validate(
                            $event->getForm()->get('UntilDate')->getData(),
                            [
                                $NotBlank,
                                new Assert\DateTime(),
                                //								new AwAssert\DateRange(['min' => "+1 day", 'max' => "+90 day"])
                            ]
                        );

                        /** @var ConstraintViolation $violation */
                        foreach ($errors as $violation) {
                            $event->getForm()->get('UntilDate')->addError(new FormError(
                                $violation->getMessage(),
                                $violation->getMessageTemplate(),
                                $violation->getParameters(),
                                $violation->getPlural()
                            ));
                        }

                        break;
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\AbRequest',
            'validation_groups' => ['properties'],
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'booking',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'booking_request_properties';
    }

    protected function setAssignedOptions(FormInterface $form, AbRequest $request)
    {
        $assignedOptions = ['' => ''];
        $admins = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessManagers($request->getBooker());

        foreach ($admins as $v) {
            $assignedOptions[$v->getFullName()] = $v->getUserid();
        }

        $form->add(
            'Assigned',
            ChoiceType::class,
            [
                'mapped' => false,
                'choices' => $assignedOptions,
                'label' => 'booking.request.properties.form.assigned_to',
                'data' => $request->getAssignedUser() ? $request->getAssignedUser()->getUserid() : '',
                'validation_groups' => ['properties'],
                'required' => false,
            ]
        );
    }

    protected function setInternalStatusOptions(FormInterface $form, AbRequest $request)
    {
        $internalStatusOptions = ['<div style="color: #000000; background-color: #FFFFFF">Not selected</div>' => ''];
        $statuses = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequestStatus::class)->getStatusesForBooker($request->getBooker());

        if (!count($statuses)) {
            return;
        }

        foreach ($statuses as $v) {
            /** @var AbRequestStatus $v */
            $status = "<div style='color: #{$v->getTextColor()};background-color: #{$v->getBgColor()};'>{$v->getStatus()}</div>";
            $internalStatusOptions[$status] = $v->getAbRequestStatusID();
        }

        $form->add(
            'InternalStatus',
            ChoiceType::class,
            [
                'mapped' => false,
                'label' => /** @Desc("Internal Status") */
                    'booking.table.headers.internal-status',
                'choices' => $internalStatusOptions,
                'data' => $request->getInternalStatus() ? $request->getInternalStatus()->getAbRequestStatusID() : '',
                'validation_groups' => ['properties'],
                'required' => false,
            ]
        );
    }
}
