<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Textelitelevel.
 *
 * @ORM\Table(name="TextEliteLevel")
 * @ORM\Entity
 */
class Textelitelevel
{
    /**
     * @var int
     * @ORM\Column(name="TextID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $textid;

    /**
     * @var string
     * @ORM\Column(name="ValueText", type="string", length=250, nullable=false)
     */
    protected $valuetext;

    /**
     * @var \Elitelevel
     * @ORM\ManyToOne(targetEntity="Elitelevel")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="EliteLevelID", referencedColumnName="EliteLevelID")
     * })
     */
    protected $elitelevelid;

    /**
     * Get textid.
     *
     * @return int
     */
    public function getTextid()
    {
        return $this->textid;
    }

    /**
     * Set valuetext.
     *
     * @param string $valuetext
     * @return Textelitelevel
     */
    public function setValuetext($valuetext)
    {
        $this->valuetext = $valuetext;

        return $this;
    }

    /**
     * Get valuetext.
     *
     * @return string
     */
    public function getValuetext()
    {
        return $this->valuetext;
    }

    /**
     * Set elitelevelid.
     *
     * @return Textelitelevel
     */
    public function setElitelevelid(?Elitelevel $elitelevelid = null)
    {
        $this->elitelevelid = $elitelevelid;

        return $this;
    }

    /**
     * Get elitelevelid.
     *
     * @return \AwardWallet\MainBundle\Entity\Elitelevel
     */
    public function getElitelevelid()
    {
        return $this->elitelevelid;
    }
}
