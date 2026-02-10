<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Goal.
 *
 * @ORM\Table(name="Goal")
 * @ORM\Entity
 */
class Goal
{
    /**
     * @var int
     * @ORM\Column(name="GoalID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $goalid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=120, nullable=false)
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="SortIndex", type="integer", nullable=false)
     */
    protected $sortindex;

    /**
     * Get goalid.
     *
     * @return int
     */
    public function getGoalid()
    {
        return $this->goalid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Goal
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set sortindex.
     *
     * @param int $sortindex
     * @return Goal
     */
    public function setSortindex($sortindex)
    {
        $this->sortindex = $sortindex;

        return $this;
    }

    /**
     * Get sortindex.
     *
     * @return int
     */
    public function getSortindex()
    {
        return $this->sortindex;
    }
}
