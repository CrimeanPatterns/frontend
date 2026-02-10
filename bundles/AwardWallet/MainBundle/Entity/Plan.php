<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Globals\StringHandler;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="Plan")
 * @ORM\Entity
 */
class Plan
{
    /**
     * @var int
     * @ORM\Column(name="PlanID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $user;

    /**
     * @var Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $userAgent;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max=250)
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationDate;

    /**
     * @var string
     * @ORM\Column(name="StartDate", type="datetime", nullable=false)
     */
    protected $startDate;

    /**
     * @var string
     * @ORM\Column(name="EndDate", type="datetime", nullable=false)
     */
    protected $endDate;

    /**
     * @var string
     * @ORM\Column(name="Notes", type="string", length=4000, nullable=true)
     */
    protected $notes;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max=32)
     * @ORM\Column(name="ShareCode", type="string", length=32, nullable=false)
     */
    protected $shareCode;

    /**
     * @var Files\PlanFile[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="AwardWallet\MainBundle\Entity\Files\PlanFile",
     *     mappedBy="plan",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=false,
     *     indexBy="uploadDate"
     * )
     */
    protected $files;

    public function __construct()
    {
        $this->creationDate = new \DateTime();
        $this->files = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(?Usr $user = null)
    {
        $this->user = $user;

        return $this;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function setUserAgent(?Useragent $userAgent = null)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return string
     */
    public function getShareCode()
    {
        return $this->shareCode;
    }

    /**
     * @param string $shareCode
     * @return $this
     */
    public function setShareCode($shareCode)
    {
        $this->shareCode = $shareCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getEncodedShareCode()
    {
        return StringHandler::base64_encode_url('Travelplan.' . $this->id . '.' . $this->shareCode);
    }

    /**
     * @return Files\PlanFile[]|Collection
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param Files\PlanFile[]|Collection $files
     */
    public function setFiles($files): self
    {
        $this->files = $files;

        return $this;
    }

    public function addFile(Files\PlanFile $file): self
    {
        $this->files[] = $file;

        return $this;
    }

    public function removeFile(Files\PlanFile $file): self
    {
        $this->files->removeElement($file);

        return $this;
    }
}
