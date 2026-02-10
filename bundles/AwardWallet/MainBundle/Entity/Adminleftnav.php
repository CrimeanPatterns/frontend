<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Adminleftnav.
 *
 * @ORM\Table(name="adminLeftNav")
 * @ORM\Entity
 */
class Adminleftnav
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="parentID", type="integer", nullable=true)
     */
    protected $parentid;

    /**
     * @var string
     * @ORM\Column(name="caption", type="string", length=255, nullable=false)
     */
    protected $caption;

    /**
     * @var string
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    protected $path;

    /**
     * @var string
     * @ORM\Column(name="note", type="string", length=255, nullable=true)
     */
    protected $note;

    /**
     * @var int
     * @ORM\Column(name="rank", type="integer", nullable=true)
     */
    protected $rank;

    /**
     * @var bool
     * @ORM\Column(name="visible", type="boolean", nullable=true)
     */
    protected $visible;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set parentid.
     *
     * @param int $parentid
     * @return Adminleftnav
     */
    public function setParentid($parentid)
    {
        $this->parentid = $parentid;

        return $this;
    }

    /**
     * Get parentid.
     *
     * @return int
     */
    public function getParentid()
    {
        return $this->parentid;
    }

    /**
     * Set caption.
     *
     * @param string $caption
     * @return Adminleftnav
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * Get caption.
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * Set path.
     *
     * @param string $path
     * @return Adminleftnav
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set note.
     *
     * @param string $note
     * @return Adminleftnav
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set rank.
     *
     * @param int $rank
     * @return Adminleftnav
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank.
     *
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set visible.
     *
     * @param bool $visible
     * @return Adminleftnav
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }
}
