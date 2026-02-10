<?php

namespace AwardWallet\MobileBundle\Form\Handler\Subscriber\Common;

use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use PhpOption\Option;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class PartialFormSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'form.mobile.pre_handle' => ['preHandle', 126],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->isMethod('GET')) {
            return;
        }

        if (!($partialFormData = $this->getPartialFormData($request))) {
            return;
        }

        $form = $event->getForm();

        foreach ($partialFormData as $key => $value) {
            if (!$form->has($key)) {
                continue;
            }

            $form->get($key)->submit($value);
        }
    }

    protected function getPartialFormData(Request $request)
    {
        return Option::fromValue(($params = $request->attributes->get('_route_params', null)) ? ($params['partialForm'] ?? null) : null)
            ->filter('is_string')

            ->map(function ($val) { return @base64_decode($val); })
            ->filter('is_string')

            ->map(function ($val) { return @json_decode($val, true); })
            ->filter('is_array')

            ->getOrElse([]);
    }
}
