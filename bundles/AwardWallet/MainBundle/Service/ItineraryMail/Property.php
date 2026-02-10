<?php

namespace AwardWallet\MainBundle\Service\ItineraryMail;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;

/**
 * @NoDI()
 */
class Property
{
    public const NEW_VALUE = 1;
    public const OLD_VALUE = 2;

    protected $code;
    protected $oldValue;
    protected $oldValueFormatted;
    protected $newValue;
    protected $newValueFormatted;

    protected $visible = true;
    protected $groups = [];

    /**
     * @var Segment
     */
    protected $segment;

    /**
     * @var bool
     */
    private $private;

    /**
     * @var string
     */
    private $sortCode;

    public function __construct($code, $newValue, $oldValue = null)
    {
        $this->setCode($code);
        $this->newValue = $newValue;
        $this->oldValue = $oldValue;
    }

    public function __toString()
    {
        $name = $this->getUtil()->translatePropertyName($this->getCode(), $this->getSegment()->getItinerary()->getType());

        return json_encode([
            'trans-key' => $name['key'] ?? 'none',
            'translation' => $name['translation'],
            'code' => $this->getCode(),
            'formatted-new-value' => $this->getFormattedNewValue(),
            'formatted-old-value' => empty($this->getFormattedOldValue()) ? 'none' : $this->getFormattedOldValue(),
            'visible' => $this->getVisible(),
        ], JSON_PRETTY_PRINT);
    }

    public function getName($lang = null)
    {
        $result = $this->getUtil()->translatePropertyName(
            $this->getCode(),
            $this->segment->getItinerary()->getType(),
            $lang ?? $this->segment->getLang()
        );

        return $result['translation'];
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    public function getValue()
    {
        return $this->getNewValue();
    }

    public function getNewValue()
    {
        return $this->newValue;
    }

    public function getFormattedValue($locale = null)
    {
        return $this->getFormattedNewValue($locale);
    }

    public function getFormattedNewValue($locale = null)
    {
        if (!isset($locale)) {
            $locale = $this->getSegment()->getLocale();
        }

        if (isset($this->newValueFormatted[$locale])) {
            return $this->newValueFormatted[$locale];
        }

        return $this->newValueFormatted[$locale] = html_entity_decode(
            $this->format($this->newValue, self::NEW_VALUE, $locale)
        );
    }

    public function setNewValue($newValue)
    {
        $this->newValue = $newValue;
        $this->newValueFormatted = null;

        return $this;
    }

    public function getOldValue()
    {
        return $this->oldValue;
    }

    public function getFormattedOldValue($locale = null)
    {
        if (!isset($locale)) {
            $locale = $this->getSegment()->getLocale();
        }

        if (isset($this->oldValueFormatted[$locale])) {
            return $this->oldValueFormatted[$locale];
        }

        return $this->oldValueFormatted[$locale] = html_entity_decode(
            $this->format($this->oldValue, self::OLD_VALUE, $locale)
        );
    }

    public function setOldValue($oldValue)
    {
        $this->oldValue = $oldValue;
        $this->oldValueFormatted = null;

        return $this;
    }

    public function getVisible()
    {
        global $arExtPropertyStructure;
        $value = $this->getNewValue();

        return $this->visible
        && (!is_string($value) || (is_string($value) && (!empty(trim($value)) || is_numeric($value)) && strlen($value) < 150))
        && ((PropertiesList::FEES === $this->code) || !isset($arExtPropertyStructure[$this->getCode()]))
        && !in_array($this->getCode(), [
            PropertiesList::CURRENCY,
            PropertiesList::CAR_IMAGE_URL,
            PropertiesList::CANCELLATION_POLICY,
        ]);
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setGroups($groups)
    {
        $this->groups = $groups;

        return $this;
    }

    public function inGroup($group)
    {
        return in_array($group, $this->groups);
    }

    /**
     * @return Segment
     */
    public function getSegment()
    {
        return $this->segment;
    }

    public function setSegment($segment)
    {
        $this->segment = $segment;

        return $this;
    }

    public function getUtil()
    {
        return $this->getSegment()->getUtil();
    }

    public function setPrivate(bool $private)
    {
        $this->private = $private;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function getSortCode(): ?string
    {
        return $this->sortCode;
    }

    public function setSortCode(string $sortCode)
    {
        $this->sortCode = $sortCode;

        return $this;
    }

    /**
     * @param int $type new or old value
     * @param string $locale
     */
    protected function format($value, $type, $locale)
    {
        return $value;
    }
}
