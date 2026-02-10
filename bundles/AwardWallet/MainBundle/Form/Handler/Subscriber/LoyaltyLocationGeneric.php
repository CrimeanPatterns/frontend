<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber;

use AwardWallet\MainBundle\Entity\Location;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\LoyaltyLocationModel;
use AwardWallet\MainBundle\Form\Transformer\LoyaltyLocationTransformer;
use AwardWallet\MainBundle\Service\TrackedLocationsLimiter;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoyaltyLocationGeneric implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var FormHandlerHelper
     */
    private $helper;

    /**
     * @var LoyaltyLocationTransformer
     */
    private $transformer;
    /**
     * @var array
     */
    private $customRadiuses = [];
    private TrackedLocationsLimiter $trackedLocationsLimiter;

    public function __construct(
        EntityManager $em,
        FormHandlerHelper $helper,
        LoyaltyLocationTransformer $transformer,
        TrackedLocationsLimiter $trackedLocationsLimiter,
        array $customRadiuses = [])
    {
        $this->em = $em;
        $this->helper = $helper;
        $this->transformer = $transformer;
        $this->customRadiuses = $customRadiuses;
        $this->trackedLocationsLimiter = $trackedLocationsLimiter;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.loyalty_location.on_valid' => ['onValid'],
            'form.generic.loyalty_location.pre_handle' => ['preHandle'],
        ];
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var LoyaltyLocationModel $model */
        $model = $form->getData();
        /** @var Location $location */
        $location = $model->getEntity();

        $locationRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Location::class);
        $tracked = $locationRep->getCountTracked($model->getUser());

        if ($tracked >= $this->trackedLocationsLimiter->getMaxTrackedLocations()) {
            $model->setTracked(false);
        }

        // change default radius for some providers
        /** @var Provider $provider */
        $provider = $location->getProvider();

        if (isset($provider) && isset($this->customRadiuses[$provider->getCode()])) {
            $model->setRadius($this->customRadiuses[$provider->getCode()]);
        }

        $this->transformer->transformToEntity($location, $model);
        $location->setGenerated(false);

        if (!$location->getId()) {
            $this->em->persist($location);
        }
        $this->em->flush();
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        if ($this->helper->isSubmitted($form, $request)) {
            $this->helper->throwIfImpersonated();
        }
    }
}
