<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class AcceptBookingInvoice extends AbstractBookingTemplate
{
    /**
     * @var AbInvoice
     */
    public $invoice;

    /**
     * @var bool
     */
    public $checkSent;

    public $enableUnsubscribe = false;

    public static function getDescription(): string
    {
        return 'Accept booking invoice';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);
        $builder->add('checkSent', CheckboxType::class, [
            'required' => false,
            'label' => /** @Ignore */ 'Check sent',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);

        $message = Tools::createAbInvoice();
        $template->setBusinessArea(true);
        $template->request->addMessage($message);
        $template->toBooker = true;
        $template->invoice = $message->getInvoice();
        $template->checkSent = isset($options['checkSent']) && $options['checkSent'];

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
