<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AwardWallet\MainBundle\Entity\AbMessageColor.
 *
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AbMessageColorRepository")
 * @ORM\Table(
 *     name="AbMessageColor",
 *     indexes={
 *         @ORM\Index(name="BookerID", columns={"BookerID"})
 *     }
 * )
 */
class AbMessageColor
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbMessageColorID;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="BookerID", referencedColumnName="UserID", nullable=false)
     */
    protected $Booker;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $Color;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $Description;

    /**
     * @return int
     */
    public function getAbMessageColorID()
    {
        return $this->AbMessageColorID;
    }

    /**
     * @param int $AbMessageColorID
     */
    public function setAbMessageColorID($AbMessageColorID)
    {
        $this->AbMessageColorID = $AbMessageColorID;
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getBooker()
    {
        return $this->Booker;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\Usr $Booker
     */
    public function setBooker($Booker)
    {
        $this->Booker = $Booker;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->Color;
    }

    /**
     * @param string $Color
     */
    public function setColor($Color)
    {
        $this->Color = $Color;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * @param string $Description
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
    }
}
