<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * AwardWallet\MainBundle\Entity\AbPassenger.
 *
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AbPassengerRepository")
 * @ORM\Table(name="AbPassenger", indexes={@ORM\Index(name="IDX_F644DD5E18FCD26A", columns={"RequestID"})})
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\AbPassengerListener" })
 */
class AbPassenger implements GroupSequenceProviderInterface
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbPassengerID;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = 1, max = 30, allowEmptyString="true")
     */
    protected $FirstName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(min = 1, max = 30, allowEmptyString="true")
     */
    protected $MiddleName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = 1, max = 30, allowEmptyString="true")
     */
    protected $LastName;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\NotBlank(groups={"not_booker"})
     * @AwAssert\DateRange(
     *      min = "1910-01-01",
     *      max = "now",
     *      minMessage = "booking.birthday.min",
     *      maxMessage = "booking.birthday.max"
     * )
     */
    protected $Birthday;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(min = 1, max = 255, allowEmptyString="true")
     */
    protected $Nationality;

    /**
     * @var string
     * @ORM\Column(type="string", length=1, nullable=false)
     * @Assert\NotBlank()
     */
    protected $Gender;

    /**
     * @var AbRequest
     * @ORM\ManyToOne(targetEntity="AbRequest", inversedBy="Passengers")
     * @ORM\JoinColumn(name="RequestID", referencedColumnName="AbRequestID", nullable=false)
     */
    protected $RequestID;

    /**
     * @var Useragent
     * @ORM\ManyToOne(targetEntity="Useragent", inversedBy="Passengers")
     * @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID", nullable=true)
     */
    protected $UserAgentID;

    /**
     * Get AbPassengerID.
     *
     * @return int
     */
    public function getAbPassengerID()
    {
        return $this->AbPassengerID;
    }

    /**
     * Set FirstName.
     *
     * @param string $firstName
     * @return AbPassenger
     */
    public function setFirstName($firstName)
    {
        $this->FirstName = $firstName;

        return $this;
    }

    /**
     * Get FirstName.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->FirstName;
    }

    /**
     * Set MiddleName.
     *
     * @param string $middleName
     * @return AbPassenger
     */
    public function setMiddleName($middleName)
    {
        $this->MiddleName = $middleName;

        return $this;
    }

    /**
     * Get MiddleName.
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->MiddleName;
    }

    /**
     * Set LastName.
     *
     * @param string $lastName
     * @return AbPassenger
     */
    public function setLastName($lastName)
    {
        $this->LastName = $lastName;

        return $this;
    }

    /**
     * Get LastName.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->LastName;
    }

    /**
     * Set Birthday.
     *
     * @param \DateTime $birthday
     * @return AbPassenger
     */
    public function setBirthday($birthday = null)
    {
        $this->Birthday = $birthday;

        return $this;
    }

    /**
     * Get Birthday.
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->Birthday;
    }

    /**
     * Set Nationality.
     *
     * @param string $nationality
     * @return AbPassenger
     */
    public function setNationality($nationality)
    {
        $this->Nationality = $nationality;

        return $this;
    }

    /**
     * Get Nationality.
     *
     * @return string
     */
    public function getNationality()
    {
        return $this->Nationality;
    }

    /**
     * Set request.
     *
     * @return AbPassenger
     */
    public function setRequest(AbRequest $request)
    {
        $this->RequestID = $request;

        return $this;
    }

    /**
     * Get request.
     *
     * @return \AwardWallet\MainBundle\Entity\AbRequest
     */
    public function getRequest()
    {
        return $this->RequestID;
    }

    /**
     * Set Useragent.
     *
     * @return AbPassenger
     */
    public function setUseragent(?Useragent $Useragent = null)
    {
        $this->UserAgentID = $Useragent;

        if ($Useragent instanceof Useragent) {
            $Useragent->addPassenger($this);
        }

        return $this;
    }

    /**
     * Get Useragent.
     *
     * @return \AwardWallet\MainBundle\Entity\Useragent
     */
    public function getUseragent()
    {
        return $this->UserAgentID;
    }

    public function getFullName()
    {
        return trim($this->getFirstName() . ($this->getMiddleName() ? (' ' . $this->getMiddleName()) : '') . ' ' . $this->getLastName());
    }

    /**
     * get user corresponding to this passenger
     * typically request author, but may be one of connected users.
     *
     * @return Usr
     */
    public function getUser()
    {
        foreach ($this->getRequest()->getAccountOwners() as $user) {
            if (strcasecmp($user->getFirstname() . ' ' . $user->getLastname(), $this->getFirstName() . ' ' . $this->getLastName()) == 0) {
                return $user;
            }
        }

        return $this->getRequest()->getUser();
    }

    /**
     * @param string $Gender
     * @return $this
     */
    public function setGender($Gender)
    {
        $this->Gender = $Gender;

        return $this;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->Gender;
    }

    public function getGroupSequence()
    {
        $groups = ['AbPassenger'];

        if (!$this->getRequest()->getByBooker()) {
            $groups[] = 'not_booker';
        }

        return $groups;
    }
}
