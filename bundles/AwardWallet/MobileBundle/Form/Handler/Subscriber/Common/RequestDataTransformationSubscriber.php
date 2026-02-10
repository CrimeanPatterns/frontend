<?php

namespace AwardWallet\MobileBundle\Form\Handler\Subscriber\Common;

use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RequestDataTransformationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'form.mobile.pre_handle' => ['preHandle', 127],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $request = $event->getRequest();
        $form = $event->getForm();

        $request->request->replace([
            $form->getName() => $request->request->all(),
        ]);
    }
}
