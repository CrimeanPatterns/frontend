<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sitegroup.
 *
 * @ORM\Table(name="SiteGroup")
 * @ORM\Entity
 */
class Sitegroup
{
    public const STAFF_ID = 3;
    public const STAFF_DEVELOPER_ID = 37;
    public const GROUP_VIP_EARLY_SUPPORTER_ID = 126;

    public const GROUP_BUSINESS_DETECTED = 'Business Detected';
    public const GROUP_VIP_EARLY_SUPPORTER = 'VIP Early Supporter';

    // Email templates
    public const TEST_SUPPORTER_3M_UPGRADE = 'TEST_SUPPORTER_3M_UPGRADE';
    public const TEST_VIP_FULL_SUPPORTER = 'TEST_VIP_FULL_SUPPORTER';
    public const EMAILED_BEFORE_PRICE_INCREASE_IN_DECEMBER_182024 = 'EmailedBeforePriceIncreaseInDecember182024';
    public const ACTUAL_EARLY_SUPPORTERS_AS_OF_DECEMBER_182024 = 'ActualEarlySupportersAsOfDecember182024';
    public const EARLY_SUPPORTERS_3_MONTHS_EMAILED_JANUARY_2025 = '3MonthsEarlySupportersEmailedJanuary2025';
    public const EARLY_SUPPORTERS_12_MONTHS_EMAILED_JANUARY_2025 = '12MonthsEarlySupportersEmailedJanuary2025';

    /**
     * @var int
     * @ORM\Column(name="SiteGroupID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $sitegroupid;

    /**
     * @var string
     * @ORM\Column(name="GroupName", type="string", length=80, nullable=false)
     */
    protected $groupname;

    /**
     * @var string
     * @ORM\Column(name="Description", type="string", length=250, nullable=true)
     */
    protected $description;

    /**
     * Get sitegroupid.
     *
     * @return int
     */
    public function getSitegroupid()
    {
        return $this->sitegroupid;
    }

    /**
     * Set groupname.
     *
     * @param string $groupname
     * @return Sitegroup
     */
    public function setGroupname($groupname)
    {
        $this->groupname = $groupname;

        return $this;
    }

    /**
     * Get groupname.
     *
     * @return string
     */
    public function getGroupname()
    {
        return $this->groupname;
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return Sitegroup
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * code of the group, for roles, see GroupVoter.
     *
     * @return string
     */
    public function getCode()
    {
        return strtoupper(preg_replace("#[^a-z\d]+#i", "_", $this->groupname));
    }
}
