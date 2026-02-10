<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AbShare.
 *
 * @ORM\Table(name="AbShare")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AbShareRepository")
 */
class AbShare
{
    /**
     * @var bool
     * @ORM\Column(name="IsApproved", type="boolean", nullable=false)
     */
    protected $isApproved = false;
    /**
     * @var int
     * @ORM\Column(name="AbShareID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=false)
     * })
     */
    private $user;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="BookerID", referencedColumnName="UserID", nullable=false)
     * })
     */
    private $booker;

    /**
     * @var \DateTime
     * @ORM\Column(name="RequestDate", type="datetime", nullable=false)
     */
    private $requestDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ApproveDate", type="datetime", nullable=true)
     */
    private $approveDate;

    public function __construct(Usr $user, Usr $booker, $isApproved = false)
    {
        $this->setUser($user);
        $this->setBooker($booker);
        $this->setIsApproved($isApproved);
        $this->requestDate = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): Usr
    {
        return $this->user;
    }

    public function setUser(Usr $user): AbShare
    {
        $this->user = $user;

        return $this;
    }

    public function getBooker(): Usr
    {
        return $this->booker;
    }

    public function setBooker(Usr $booker): AbShare
    {
        $this->booker = $booker;

        return $this;
    }

    public function getRequestDate(): \DateTime
    {
        return $this->requestDate;
    }

    public function setRequestDate(\DateTime $requestDate): AbShare
    {
        $this->requestDate = $requestDate;

        return $this;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): AbShare
    {
        $this->isApproved = $isApproved;

        if ($this->isApproved) {
            $this->setApproveDate(new \DateTime());
        } else {
            $this->setApproveDate(null);
        }

        return $this;
    }

    public function getApproveDate(): ?\DateTime
    {
        return $this->approveDate;
    }

    public function setApproveDate(?\DateTime $approveDate): AbShare
    {
        $this->approveDate = $approveDate;

        return $this;
    }
}
