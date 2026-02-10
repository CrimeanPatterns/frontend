<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Aviewaccount.
 *
 * @ORM\Table(name="AViewAccount")
 * @ORM\Entity
 */
class Aviewaccount
{
    /**
     * @var int
     * @ORM\Column(name="AViewAccountID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $aviewaccountid;

    /**
     * @var string
     * @ORM\Column(name="Kind", type="string", length=1, nullable=false)
     */
    protected $kind;

    /**
     * @var int
     * @ORM\Column(name="ID", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="SortIndex", type="integer", nullable=false)
     */
    protected $sortindex;

    /**
     * @var \Aview
     * @ORM\ManyToOne(targetEntity="Aview")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AViewID", referencedColumnName="AViewID")
     * })
     */
    protected $aviewid;

    /**
     * Get aviewaccountid.
     *
     * @return int
     */
    public function getAviewaccountid()
    {
        return $this->aviewaccountid;
    }

    /**
     * Set kind.
     *
     * @param string $kind
     * @return Aviewaccount
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind.
     *
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set id.
     *
     * @param int $id
     * @return Aviewaccount
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set sortindex.
     *
     * @param int $sortindex
     * @return Aviewaccount
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

    /**
     * Set aviewid.
     *
     * @return Aviewaccount
     */
    public function setAviewid(?Aview $aviewid = null)
    {
        $this->aviewid = $aviewid;

        return $this;
    }

    /**
     * Get aviewid.
     *
     * @return \AwardWallet\MainBundle\Entity\Aview
     */
    public function getAviewid()
    {
        return $this->aviewid;
    }
}
