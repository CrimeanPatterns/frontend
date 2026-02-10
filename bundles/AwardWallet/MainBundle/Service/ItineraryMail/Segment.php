<?php

namespace AwardWallet\MainBundle\Service\ItineraryMail;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter\SimpleFormatterInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Util;

/**
 * @NoDI()
 */
class Segment
{
    /**
     * @var Rental|Reservation|Restaurant|Tripsegment|Parking
     */
    protected $itinerary;

    protected bool $showChanges;

    /**
     * @var string
     */
    protected $itKind;

    /**
     * @var Util
     */
    protected $util;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $lang;

    /**
     * @var Property[]
     */
    protected $properties = [];

    /**
     * @var array
     */
    protected $encoders;

    /**
     * @var SimpleFormatterInterface
     */
    private $simpleFormatter;

    public function __construct(
        $it,
        bool $showChanges,
        SimpleFormatterInterface $simpleFormatter,
        Util $util,
        $locale,
        $lang
    ) {
        $this->itinerary = $it;
        $this->showChanges = $showChanges;
        $this->util = $util;
        $this->locale = $locale;
        $this->lang = $lang;
        $this->simpleFormatter = $simpleFormatter;
    }

    public function __toString()
    {
        $result = '';

        foreach ($this->getProperties() as $property) {
            $result .= (string) $property . "\n";
        }

        return $result;
    }

    /**
     * @return Rental|Reservation|Restaurant|Tripsegment|Parking
     */
    public function getItinerary()
    {
        return $this->itinerary;
    }

    /**
     * @return string
     */
    public function getKind()
    {
        if (isset($this->itKind)) {
            return $this->itKind;
        }

        return $this->itKind = Util::getKind($this->itinerary);
    }

    /**
     * @return string
     */
    public static function getSourceId(object $itinerary)
    {
        $pref = $itinerary->getKind();

        if ($itinerary instanceof Tripsegment) {
            return "$pref." . $itinerary->getTripsegmentid();
        } elseif ($itinerary instanceof Itinerary) {
            return "$pref." . $itinerary->getId();
        }
    }

    /**
     * @return Util
     */
    public function getUtil()
    {
        return $this->util;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return !isset($this->locale) ? $this->util->container->getParameter("locale") : $this->locale;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getConfNumber()
    {
        if (!$this->hasProperty(PropertiesList::CONFIRMATION_NUMBER)) {
            throw new \LogicException("No confirmation number");
        }

        return $this->getProperty(PropertiesList::CONFIRMATION_NUMBER);
    }

    public function hasProperty($code)
    {
        return isset($this->properties[$code]);
    }

    public function getProperty($code)
    {
        return $this->properties[$code];
    }

    public function getPropertiesByGroup(array $groups, $filterByVisible = true)
    {
        $result = [];

        foreach ($this->getProperties() as $property) {
            if (
                sizeof(array_intersect($property->getGroups(), $groups))
                && (($filterByVisible && $property->getVisible()) || !$filterByVisible)
            ) {
                $result[] = $property;
            }
        }

        return $result;
    }

    public function addProperty($code, $visible = true, $private = false): ?Property
    {
        $value = $this->simpleFormatter->getValue($code);

        if ((is_null($value) && $code != PropertiesList::CONFIRMATION_NUMBER) || $this->hasProperty($code)) {
            return null;
        }

        $property = new Property(
            $code,
            $value,
            $this->showChanges ? $this->simpleFormatter->getPreviousValue($code) : null
        );

        $property->setVisible($visible);
        $property->setPrivate($private);
        $property->setSegment($this);
        $this->properties[$code] = $property;

        return $property;
    }

    /**
     * @param Property[] $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;

        return $this;
    }

    public function isShowAIWarningForEmailSource(): bool
    {
        return $this->itinerary && $this->itinerary->isShowAIWarningForEmailSource();
    }
}
