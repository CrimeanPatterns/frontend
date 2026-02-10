<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\LoyaltyLocation;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoyaltyLocationListGeneric implements EventSubscriberInterface
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
     * @var CacheManager
     */
    private $cache;
    /**
     * @var LoyaltyLocation
     */
    private $loyaltyLocationService;

    public function __construct(
        EntityManager $em,
        FormHandlerHelper $helper,
        LoyaltyLocation $loyaltyLocationService,
        CacheManager $cache
    ) {
        $this->em = $em;
        $this->helper = $helper;
        $this->cache = $cache;
        $this->loyaltyLocationService = $loyaltyLocationService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.loyalty_location_list.on_valid' => ['onValid'],
            'form.generic.loyalty_location_list.pre_handle' => ['preHandle'],
        ];
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        $data = $form->getData();

        /** @var Usr $user */
        $user = $form->getConfig()->getOption("user");
        $disabled = $enabled = [];

        foreach ($data as $name => $value) {
            if (is_string($name) && preg_match("/^location_(\d+)$/", $name, $matches) && is_bool($value)) {
                if ($value) {
                    $enabled[] = intval($matches[1]);
                } else {
                    $disabled[] = intval($matches[1]);
                }
            }
        }

        $this->loyaltyLocationService->disableLocations($user, $disabled);
        $this->loyaltyLocationService->enableLocations($user, $enabled);
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
