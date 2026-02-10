<?php

namespace AwardWallet\MainBundle\Entity\Files;

use AwardWallet\MainBundle\Entity\Plan;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Table(name="PlanFile")
 * @ORM\Entity
 */
class PlanFile extends AbstractFile
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="PlanFileID", type="integer", nullable=false)
     */
    protected ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Plan", cascade={"detach"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PlanID", referencedColumnName="PlanID", nullable=true)
     * })
     * @JMS\Exclude();
     */
    protected ?Plan $plan;

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(?Plan $plan): self
    {
        $this->plan = $plan;

        return $this;
    }
}
