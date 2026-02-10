<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class NewBookingRequest extends AbstractBookingTemplate
{
    public $enableUnsubscribe = false;

    public static function getDescription(): string
    {
        return 'New booking request (to user and booker)';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);
        $builder->add('to', CheckboxType::class, [
            'required' => false,
            'label' => /** @Ignore */ 'To booker',
        ]);
        $builder->add('paymentCash', CheckboxType::class, [
            'required' => false,
            'label' => /** @Ignore */ 'Payment cash',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);
        $template->toBooker = $options['to'] ?? false;
        $template->confirm = !$template->toBooker;

        if ($template->toBooker) {
            $template->setBusinessArea(true);
        } else {
            $template->toUser($template->request->getUser(), false);
        }

        $template->request->setPaymentCash($options['paymentCash'] ?? false);

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
