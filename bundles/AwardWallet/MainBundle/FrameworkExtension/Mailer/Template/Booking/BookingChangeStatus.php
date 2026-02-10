<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

class BookingChangeStatus extends AbstractBookingTemplate
{
    /**
     * @var string new status of booking request
     */
    public $status;

    public $enableUnsubscribe = false;

    public static function getDescription(): string
    {
        return 'Change status of booking request';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);
        $builder->add('Status', ChoiceType::class, [
            'choices' => [
                /** @Ignore */
                'Opened' => 'booking.statuses.opened',
                /** @Ignore */
                'Canceled' => 'booking.statuses.canceled',
                /** @Ignore */
                'Booked' => 'booking.statuses.booked',
                /** @Ignore */
                'Paid' => 'booking.statuses.paid',
                /** @Ignore */
                'Future' => 'booking.statuses.future',
            ],
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);

        $template->toUser($template->request->getUser(), false);
        $template->toBooker = false;
        $template->status = $options['Status'] ?? 'booking.statuses.booked';

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
