<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BookingInvoice extends AbstractBookingTemplate
{
    /**
     * @var AbInvoice
     */
    public $invoice;

    public $enableUnsubscribe = false;

    public static function getDescription(): string
    {
        return 'Booker has generated an invoice';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);

        $template->toUser($template->request->getUser(), false);
        $template->toBooker = false;

        $message = Tools::createAbInvoice();
        $template->request->addMessage($message);
        $template->invoice = $message->getInvoice();

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
