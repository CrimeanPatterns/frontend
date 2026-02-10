<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu;

class Phones
{
    public const GROUP_GEO = 'geo';

    /**
     * List of phone groups, device should consider list ordering.
     *
     * @var PhoneGroup[]
     */
    public $groups;

    /**
     * @var Phone[]
     */
    public $phones;

    /**
     * @var string
     */
    public $ownerCountry;
    /**
     * @var string
     */
    public $icon;
    /**
     * @var string
     */
    public $title;

    /**
     * @var int
     */
    private $groupAutoId = 0;

    public function __construct(?string $icon, ?string $title)
    {
        $this->icon = $icon;
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getAutoGroupId()
    {
        return (string) $this->groupAutoId++;
    }
}

/**
 * @property string $phone phone number
 * @property string $name display name
 * @property string $country disaply country name
 * @property string $countryCode 2-letter country code
 * @property string $group group refrence, @see PhoneGroup
 */
class Phone extends \ArrayObject
{
}

/**
 * @property string $name group name to reference from phone
 * @property array[] $order ordering conditions, ex.:
 *      ["+rank"] - order by "rank" phone property asc(min rank first)
 *      ["-prop0"] - order by "prop0" phone property desc(max prop0 first)
 *      ["geo", "-rank"] - order by geolocation(current country first) then by rank desc
 */
class PhoneGroup extends \ArrayObject
{
}
