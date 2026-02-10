<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Slaevent.
 *
 * @ORM\Table(name="SlaEvent")
 * @ORM\Entity
 */
class Slaevent
{
    /**
     * @var int
     * @ORM\Column(name="SlaEventID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $slaeventid;

    /**
     * @var \DateTime
     * @ORM\Column(name="EventDate", type="datetime", nullable=false)
     */
    protected $eventdate;

    /**
     * @var bool
     * @ORM\Column(name="OldSeverity", type="boolean", nullable=true)
     */
    protected $oldseverity;

    /**
     * @var bool
     * @ORM\Column(name="NewSeverity", type="boolean", nullable=true)
     */
    protected $newseverity;

    /**
     * @var int
     * @ORM\Column(name="Checked", type="integer", nullable=false)
     */
    protected $checked;

    /**
     * @var int
     * @ORM\Column(name="Errors", type="integer", nullable=false)
     */
    protected $errors;

    /**
     * @var string
     * @ORM\Column(name="Event", type="string", length=25, nullable=false)
     */
    protected $event;

    /**
     * @var bool
     * @ORM\Column(name="OldTier", type="boolean", nullable=true)
     */
    protected $oldtier;

    /**
     * @var bool
     * @ORM\Column(name="NewTier", type="boolean", nullable=true)
     */
    protected $newtier;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get slaeventid.
     *
     * @return int
     */
    public function getSlaeventid()
    {
        return $this->slaeventid;
    }

    /**
     * Set eventdate.
     *
     * @param \DateTime $eventdate
     * @return Slaevent
     */
    public function setEventdate($eventdate)
    {
        $this->eventdate = $eventdate;

        return $this;
    }

    /**
     * Get eventdate.
     *
     * @return \DateTime
     */
    public function getEventdate()
    {
        return $this->eventdate;
    }

    /**
     * Set oldseverity.
     *
     * @param bool $oldseverity
     * @return Slaevent
     */
    public function setOldseverity($oldseverity)
    {
        $this->oldseverity = $oldseverity;

        return $this;
    }

    /**
     * Get oldseverity.
     *
     * @return bool
     */
    public function getOldseverity()
    {
        return $this->oldseverity;
    }

    /**
     * Set newseverity.
     *
     * @param bool $newseverity
     * @return Slaevent
     */
    public function setNewseverity($newseverity)
    {
        $this->newseverity = $newseverity;

        return $this;
    }

    /**
     * Get newseverity.
     *
     * @return bool
     */
    public function getNewseverity()
    {
        return $this->newseverity;
    }

    /**
     * Set checked.
     *
     * @param int $checked
     * @return Slaevent
     */
    public function setChecked($checked)
    {
        $this->checked = $checked;

        return $this;
    }

    /**
     * Get checked.
     *
     * @return int
     */
    public function getChecked()
    {
        return $this->checked;
    }

    /**
     * Set errors.
     *
     * @param int $errors
     * @return Slaevent
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Get errors.
     *
     * @return int
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set event.
     *
     * @param string $event
     * @return Slaevent
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event.
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set oldtier.
     *
     * @param bool $oldtier
     * @return Slaevent
     */
    public function setOldtier($oldtier)
    {
        $this->oldtier = $oldtier;

        return $this;
    }

    /**
     * Get oldtier.
     *
     * @return bool
     */
    public function getOldtier()
    {
        return $this->oldtier;
    }

    /**
     * Set newtier.
     *
     * @param bool $newtier
     * @return Slaevent
     */
    public function setNewtier($newtier)
    {
        $this->newtier = $newtier;

        return $this;
    }

    /**
     * Get newtier.
     *
     * @return bool
     */
    public function getNewtier()
    {
        return $this->newtier;
    }

    /**
     * Set providerid.
     *
     * @return Slaevent
     */
    public function setProviderid(?Provider $providerid = null)
    {
        $this->providerid = $providerid;

        return $this;
    }

    /**
     * Get providerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getProviderid()
    {
        return $this->providerid;
    }
}
