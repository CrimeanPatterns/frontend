<?php

namespace AwardWallet\MobileBundle\Form\Handler\Subscriber;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\PersonalModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PersonalMobile implements EventSubscriberInterface
{
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;

    /**
     * ProfilePlatformHandler constructor.
     */
    public function __construct(FormHandlerHelper $formHandlerHelper)
    {
        $this->formHandlerHelper = $formHandlerHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.mobile.personal.pre_handle' => ['preHandle'],
            'form.mobile.personal.on_valid' => ['onValid', 0],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        if ($this->formHandlerHelper->isSubmitted($form, $request)) {
            $this->formHandlerHelper->throwIfImpersonated();
        }
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        /** @var PersonalModel $data */
        $data = $form->getData();

        /** @var Usr $user */
        $user = $data->getEntity();

        $user->setLogin($data->getLogin());
        $user->setFirstname($data->getFirstname());
        $user->setMidname($data->getMidname());
        $user->setLastname($data->getLastname());

        $event->setData($user);
    }
}
