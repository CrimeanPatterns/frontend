<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\Location;
use AwardWallet\MainBundle\Entity\LocationSetting;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\LoyaltyLocationModel;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

class LoyaltyLocationTransformer extends AbstractModelTransformer implements TranslationContainerInterface
{
    /**
     * @var Usr
     */
    private $user;

    /**
     * @return LoyaltyLocationTransformer
     */
    public function setUser(Usr $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param Location $location
     * @return LoyaltyLocationModel
     */
    public function transform($location)
    {
        $tracked = false;

        if ($settings = $this->findLocationSettings($location, $this->user)) {
            $tracked = $settings->isTracked();
        }

        return (new LoyaltyLocationModel())
            ->setName($location->getName())
            ->setLat($location->getLat())
            ->setLng($location->getLng())
            ->setRadius($location->getRadius())
            ->setTracked($tracked)
            ->setUser($this->user)
            ->setEntity($location);
    }

    public function transformToEntity(Location $location, LoyaltyLocationModel $model)
    {
        $location->setName($model->getName());
        $location->setLat($model->getLat());
        $location->setLng($model->getLng());
        $location->setRadius($model->getRadius());

        $settings = $this->findLocationSettings($location, $model->getUser());

        if (!$settings) {
            $settings = new LocationSetting($location, $model->getUser(), $model->isTracked());
            $location->addLocationSettings($settings);
        } else {
            $settings->setTracked((bool) $model->isTracked());
        }
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('loyalty-location.track-location'))->setDesc('Track this location geographically'),
            (new Message('loyalty-location.track-location.notice'))->setDesc('It turned on, we will attempt to send you a push notification reminder with a bar code that could be used at checkout when you get in proximity to the store.'),
            (new Message('button.add'))->setDesc('Add'),
        ];
    }

    /**
     * @return LocationSetting|null
     */
    private function findLocationSettings(Location $location, Usr $user)
    {
        $settings = $location->getLocationSettings()->filter(function ($item) use ($user) {
            /** @var LocationSetting $item */
            return $user === $item->getUser();
        });

        if ($settings->count() === 0) {
            return null;
        }

        return $settings->first();
    }
}
