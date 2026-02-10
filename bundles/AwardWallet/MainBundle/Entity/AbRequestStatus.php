<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AwardWallet\MainBundle\Entity\AbRequestStatus.
 *
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AbRequestStatusRepository")
 * @ORM\Table(
 *     name="AbRequestStatus",
 *     indexes={
 *         @ORM\Index(name="BookerID", columns={"BookerID"})
 *     }
 * )
 */
class AbRequestStatus
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbRequestStatusID;

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
    protected $Status;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotBlank()
     */
    protected $SortIndex;

    /**
     * @var string
     * @ORM\Column(type="string", length=6, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "6", allowEmptyString="true")
     */
    protected $TextColor;

    /**
     * @var string
     * @ORM\Column(type="string", length=6, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "6", allowEmptyString="true")
     */
    protected $BgColor;

    /**
     * @return int
     */
    public function getAbRequestStatusID()
    {
        return $this->AbRequestStatusID;
    }

    /**
     * @param int $AbRequestStatusID
     */
    public function setAbRequestStatusID($AbRequestStatusID)
    {
        $this->AbRequestStatusID = $AbRequestStatusID;
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
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * @param string $Status
     */
    public function setStatus($Status)
    {
        $this->Status = $Status;
    }

    /**
     * @return string
     */
    public function getBgColor()
    {
        return $this->BgColor;
    }

    /**
     * @param string $BgColor
     */
    public function setBgColor($BgColor)
    {
        $this->BgColor = $BgColor;
    }

    /**
     * @return int
     */
    public function getSortIndex()
    {
        return $this->SortIndex;
    }

    /**
     * @param int $SortIndex
     */
    public function setSortIndex($SortIndex)
    {
        $this->SortIndex = $SortIndex;
    }

    /**
     * @return string
     */
    public function getTextColor()
    {
        return $this->TextColor;
    }

    /**
     * @param string $TextColor
     */
    public function setTextColor($TextColor)
    {
        $this->TextColor = $TextColor;
    }
}
