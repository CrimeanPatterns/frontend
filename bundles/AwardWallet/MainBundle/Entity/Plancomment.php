<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Plancomment.
 *
 * @ORM\Table(name="PlanComment")
 * @ORM\Entity
 */
class Plancomment
{
    /**
     * @var int
     * @ORM\Column(name="PlanCommentID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $plancommentid;

    /**
     * @var \DateTime
     * @ORM\Column(name="PostDate", type="datetime", nullable=false)
     */
    protected $postdate;

    /**
     * @var string
     * @ORM\Column(name="Comment", type="text", nullable=false)
     */
    protected $comment;

    /**
     * @var \Travelplan
     * @ORM\ManyToOne(targetEntity="Travelplan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TravelPlanID", referencedColumnName="TravelPlanID")
     * })
     */
    protected $travelplanid;

    /**
     * @var \Plangroup
     * @ORM\ManyToOne(targetEntity="Plangroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PlanGroupID", referencedColumnName="PlanGroupID")
     * })
     */
    protected $plangroupid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get plancommentid.
     *
     * @return int
     */
    public function getPlancommentid()
    {
        return $this->plancommentid;
    }

    /**
     * Set postdate.
     *
     * @param \DateTime $postdate
     * @return Plancomment
     */
    public function setPostdate($postdate)
    {
        $this->postdate = $postdate;

        return $this;
    }

    /**
     * Get postdate.
     *
     * @return \DateTime
     */
    public function getPostdate()
    {
        return $this->postdate;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     * @return Plancomment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set travelplanid.
     *
     * @return Plancomment
     */
    public function setTravelplanid(?Travelplan $travelplanid = null)
    {
        $this->travelplanid = $travelplanid;

        return $this;
    }

    /**
     * Get travelplanid.
     *
     * @return \AwardWallet\MainBundle\Entity\Travelplan
     */
    public function getTravelplanid()
    {
        return $this->travelplanid;
    }

    /**
     * Set plangroupid.
     *
     * @return Plancomment
     */
    public function setPlangroupid(?Plangroup $plangroupid = null)
    {
        $this->plangroupid = $plangroupid;

        return $this;
    }

    /**
     * Get plangroupid.
     *
     * @return \AwardWallet\MainBundle\Entity\Plangroup
     */
    public function getPlangroupid()
    {
        return $this->plangroupid;
    }

    /**
     * Set userid.
     *
     * @return Plancomment
     */
    public function setUserid(?Usr $userid = null)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUserid()
    {
        return $this->userid;
    }
}
