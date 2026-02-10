<?php

namespace AwardWallet\MainBundle\Service\Cache;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountshare;
use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\AirlineAlias;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Elitelevel;
use AwardWallet\MainBundle\Entity\Files\ItineraryFile;
use AwardWallet\MainBundle\Entity\Files\PlanFile;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Location;
use AwardWallet\MainBundle\Entity\LocationSetting;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Providercouponshare;
use AwardWallet\MainBundle\Entity\Providerphone;
use AwardWallet\MainBundle\Entity\Region;
use AwardWallet\MainBundle\Entity\Travelplan;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\GeneralUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class Invalidator
{
    private CacheManager $cache;
    /**
     * @var array
     */
    private $tags = [];

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $changeSet = $args->getEntityChangeSet();
        $entity = $args->getEntity();

        // account
        if ($entity instanceof Account || $entity instanceof Providercoupon) {
            $this->tags[] = Tags::getUserAccountsKey($this->getValue($entity, 'userid', $changeSet, 0), $this->getValue($entity, 'useragentid', $changeSet, 0));
            $this->tags[] = Tags::getUserAccountsKey($this->getValue($entity, 'userid', $changeSet, 1), $this->getValue($entity, 'useragentid', $changeSet, 1));

            if (isset($changeSet['user'])) {
                $this->tags[] = Tags::getAllAccountsKey($this->getValue($entity, 'userid', $changeSet, 0));
                $this->tags[] = Tags::getAllAccountsKey($this->getValue($entity, 'userid', $changeSet, 1));
            }
        }

        // itineraries
        if ($entity instanceof Tripsegment) {
            $entity = GeneralUtils::coalesce($entity->getTripid(), $entity->getTravelplanid());
        }

        if ($entity instanceof Itinerary) {
            if (isset($changeSet['user']) || isset($changeSet['userAgent'])) {
                foreach ([0, 1] as $index) {
                    if ($_user = $this->getValue($entity, 'user', $changeSet, $index)) {
                        $this->tags[] = Tags::getTimelineKey(
                            $_user,
                            $this->getValue($entity, 'userAgent', $changeSet, $index)
                        );
                    }
                }
            }

            $this->tags[] = Tags::getTimelineKey($entity->getUser(), $entity->getUserAgent());
            $this->tags[] = Tags::getTimelineKey($entity->getUser());
        }

        if ($entity instanceof Travelplan) {
            if (isset($changeSet['userid']) || isset($changeSet['useragentid'])) {
                foreach ([0, 1] as $index) {
                    if ($_user = $this->getValue($entity, 'userid', $changeSet, $index)) {
                        $this->tags[] = Tags::getTimelineKey(
                            $_user,
                            $this->getValue($entity, 'useragentid', $changeSet, $index)
                        );
                    }
                }
            }

            $this->tags[] = Tags::getTimelineKey($entity->getUserid(), $entity->getUseragentid());
            $this->tags[] = Tags::getTimelineKey($entity->getUserid());
        }

        // provider
        if ($entity instanceof Provider && isset($changeSet['state'])) {
            $this->providerStateChanged();
        }

        // elite level
        if ($entity instanceof Elitelevel) {
            $this->eliteLevelsChanged();
        }

        if ($entity instanceof Location) {
            $this->loyaltyLocationChanged($entity);
        }

        if ($entity instanceof LocationSetting) {
            $this->loyaltyLocationSettingChanged($entity);
        }

        // connections
        if ($entity instanceof Useragent) {
            //			$this->connectionsChanged($entity->getAgentid()->getUserid());
            if (!empty($entity->getClientid())) {
                $this->connectionsChanged($entity->getClientid()->getUserid());
            }
        }

        // plan
        if ($entity instanceof Plan) {
            $this->tags[] = Tags::getTimelineKey($entity->getUser(), $entity->getUserAgent());
            $this->tags[] = Tags::getTimelineKey($entity->getUser());
        }

        if ($entity instanceof PlanFile) {
            $this->tags[] = Tags::getTimelineKey($entity->getPlan()->getUser(), $entity->getPlan()->getUserAgent());
            $this->tags[] = Tags::getTimelineKey($entity->getPlan()->getUser());
        }

        if ($entity instanceof ItineraryFile) {
            $this->tags[] = Tags::getTimelineKey($entity->getItinerary()->getUser(), $entity->getItinerary()->getUserAgent());
            $this->tags[] = Tags::getTimelineKey($entity->getItinerary()->getUser());
        }

        // trip
        if ($entity instanceof Trip) {
            $this->tags[] = Tags::getTimelineCounterKey($entity->getUser(), $entity->getUserAgent(), true);
        }

        if ($entity instanceof CreditCard) {
            $this->creditCardsChanged();
        }

        if ($entity instanceof Providerphone) {
            $this->providerStateChanged();
        }

        if ($entity instanceof Airline || $entity instanceof AirlineAlias) {
            $this->airlineChanged();
        }

        if ($entity instanceof Usr && isset($changeSet['countryid'])) {
            $this->tags[] = Tags::getCreditCardAdKey($entity->getUserid());
        }

        if ($entity instanceof Region) {
            $this->regionsChanged();
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->existenceInvalidation($args->getEntity());
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->postRemove($args);
    }

    public function onFlush()
    {
        $this->tags = [];
    }

    public function postFlush()
    {
        if (count($this->tags) > 0) {
            $this->cache->invalidateTags(array_unique($this->tags));
        }
    }

    public function providerStateChanged()
    {
        $this->cache->invalidateGlobalTags([Tags::TAG_PROVIDERS]);
    }

    public function airlineChanged()
    {
        $this->cache->invalidateGlobalTags([Tags::TAG_AIRLINE]);
    }

    public function creditCardsChanged()
    {
        $this->cache->invalidateGlobalTags([Tags::TAG_CREDITCARDS]);
    }

    public function eliteLevelsChanged()
    {
        $this->cache->invalidateGlobalTags([Tags::TAG_ELITE_LEVELS]);
    }

    public function regionsChanged()
    {
        $this->cache->invalidateGlobalTags([Tags::TAG_REGIONS]);
    }

    public function connectionsChanged($userId, $addPrefix = true)
    {
        $this->cache->invalidateTags(Tags::getConnectionsTags($userId), $addPrefix);
        $this->cache->invalidateTags(Tags::getPersonsWidgetTags($userId), $addPrefix);
        $this->cache->invalidateTags([Tags::getTimelineKey($userId)]);
    }

    public function connectionChanged($userAgentId, $addPrefix = true)
    {
        $this->cache->invalidateTags(Tags::getUserAgentTags($userAgentId), $addPrefix);
    }

    public function loyaltyLocationChanged(Location $location)
    {
        $container = $location->getContainer();

        /** @var Usr $user */
        if ($user = $container->getUserid()) {
            $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($user->getUserid())]);
        }

        foreach ($location->getUseragents() as $ua) {
            $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($ua->getAgentid()->getUserid())]);
        }

        foreach ($location->getLocationSettings() as $setting) {
            /** @var LocationSetting $setting */
            $this->loyaltyLocationSettingChanged($setting);
        }
    }

    public function loyaltyLocationSettingChanged(LocationSetting $locationSetting)
    {
        $this->cache->invalidateTags([Tags::getLoyaltyLocationsKey($locationSetting->getUser()->getUserid())]);
    }

    protected function existenceInvalidation($entity)
    {
        if ($entity instanceof Account || $entity instanceof Providercoupon) {
            $this->tags[] = Tags::getUserAccountsKey($userId = $this->getValue($entity, 'userid'), $userAgentId = $this->getValue($entity, 'useragentid'));
            $this->tags[] = Tags::getAllAccountsKey($userId);

            if (sizeof($entity->getUseragents())) {
                foreach ($entity->getUseragents() as $ua) {
                    $this->tags[] = Tags::getUserAccountsKey($ua->getAgentid()->getUserid());
                    $this->tags[] = Tags::getAllAccountsKey($ua->getAgentid()->getUserid());
                }
            }
        }

        if (
            $entity instanceof Provider
            || $entity instanceof Providerphone
        ) {
            $this->providerStateChanged();
        }

        if ($entity instanceof Elitelevel) {
            $this->eliteLevelsChanged();
        }

        if ($entity instanceof Providercouponshare || $entity instanceof Accountshare) {
            $this->tags[] = Tags::getUserAccountsKey($entity->getUseragentid()->getAgentid()->getUserid());
            $this->tags[] = Tags::getAllAccountsKey($entity->getUseragentid()->getAgentid()->getUserid());
            $this->tags[] = Tags::getUserAccountsKey($entity->getUseragentid()->getAgentid()->getUserid(), $entity->getUseragentid()->getUseragentid());
            $this->connectionChanged($entity->getUseragentid()->getUseragentid());
        }

        if ($entity instanceof Tripsegment) {
            $entity = GeneralUtils::coalesce($entity->getTripid(), $entity->getTravelplanid());
        }

        if ($entity instanceof Itinerary) {
            $this->tags[] = Tags::getTimelineKey($entity->getUser(), $entity->getUserAgent());
            $this->tags[] = Tags::getTimelineKey($entity->getUser());
        }

        if ($entity instanceof Travelplan) {
            $this->tags[] = Tags::getTimelineKey($entity->getUserid(), $entity->getUseragentid());
            $this->tags[] = Tags::getTimelineKey($entity->getUserid());
        }

        if ($entity instanceof Plan) {
            $this->tags[] = Tags::getTimelineKey($entity->getUser(), $entity->getUserAgent());
            $this->tags[] = Tags::getTimelineKey($entity->getUser());
        }

        if ($entity instanceof PlanFile) {
            $this->tags[] = Tags::getTimelineKey($entity->getPlan()->getUser(), $entity->getPlan()->getUserAgent());
            $this->tags[] = Tags::getTimelineKey($entity->getPlan()->getUser());
        }

        if ($entity instanceof ItineraryFile && null !== $entity->getItinerary()) {
            $this->tags[] = Tags::getTimelineKey($entity->getItinerary()->getUser(), $entity->getItinerary()->getUserAgent());
            $this->tags[] = Tags::getTimelineKey($entity->getItinerary()->getUser());
        }

        if ($entity instanceof Useragent) {
            $this->connectionsChanged($entity->getAgentid()->getUserid());
            $this->connectionChanged($entity->getUseragentid());
        }

        if (
            $entity instanceof CreditCard
        ) {
            $this->creditCardsChanged();
        }

        if ($entity instanceof Airline || $entity instanceof AirlineAlias) {
            $this->airlineChanged();
        }

        if ($entity instanceof Location) {
            $this->loyaltyLocationChanged($entity);
        }

        if ($entity instanceof LocationSetting) {
            $this->loyaltyLocationSettingChanged($entity);
        }

        if ($entity instanceof Region) {
            $this->regionsChanged();
        }
    }

    private function getValue($entity, $fieldName, array $changeSet = [], $index = 0)
    {
        $method = "get" . ucfirst($fieldName);

        if (array_key_exists($fieldName, $changeSet)) {
            $value = $changeSet[$fieldName][$index];
        } else {
            $value = $entity->$method();
        }

        if (method_exists($value, $method)) {
            $value = $value->$method();
        }

        return $value;
    }

    private function getOldValue($entity, $fieldName, array $changeSet = [])
    {
        return $this->getValue($entity, $fieldName, $changeSet, 0);
    }

    private function getNewValue($entity, $fieldName, array $changeSet = [])
    {
        return $this->getValue($entity, $fieldName, $changeSet, 1);
    }
}
