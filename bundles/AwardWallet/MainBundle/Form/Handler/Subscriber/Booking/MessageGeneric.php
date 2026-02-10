<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Booking;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Event\BookingMessage;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\BookingRespond;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MessageGeneric implements EventSubscriberInterface
{
    /**
     * @var BookingRequestManager
     */
    private $abManager;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(BookingRequestManager $abManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->abManager = $abManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.ab_message.on_valid' => ['onValid'],
        ];
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var AbMessage $message */
        $message = $form->getData();
        $abRequest = $message->getRequest();
        $request = $event->getRequest();

        $add = empty($message->getAbMessageID());

        if ($add) {
            $abRequest->addMessage($message);
            $this->abManager->addMessage($message);
        }
        $this->abManager->flush();

        if ($add) {
            $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($message, [
                'emailClass' => BookingRespond::class,
                'emailClassType' => BookingRespond::TYPE_INCLUDE,
            ]), 'aw.booking.message.new');
        } else {
            $this->eventDispatcher->dispatch(new BookingMessage\EditEvent($message), 'aw.booking.message.edit');
        }
    }
}
