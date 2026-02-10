<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Plangroup.
 *
 * @ORM\Table(name="PlanGroup")
 * @ORM\Entity
 */
class Plangroup
{
    /**
     * @var int
     * @ORM\Column(name="PlanGroupID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $plangroupid;

    /**
     * Get plangroupid.
     *
     * @return int
     */
    public function getPlangroupid()
    {
        return $this->plangroupid;
    }
}
