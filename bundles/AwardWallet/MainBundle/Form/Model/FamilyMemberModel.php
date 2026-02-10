<?php

namespace AwardWallet\MainBundle\Form\Model;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @property Useragent $entity
 */
class FamilyMemberModel extends AbstractEntityAwareModel
{
    /**
     * @var string
     * @AwAssert\Conversion(
     *     expression = "this.clearName(value)",
     *     constraints = {
     *         @Assert\NotBlank
     *     }
     * )
     */
    private $firstname;

    /**
     * @var string
     */
    private $midname;

    /**
     * @var string
     * @AwAssert\Conversion(
     *     expression = "this.clearName(value)",
     *     constraints = {
     *         @Assert\NotBlank
     *     }
     * )
     */
    private $lastname;

    /**
     * @var string
     * @AwAssert\Conversion(
     *     expression = "this.clearName(value)",
     *     constraints = {
     *         @Assert\NotBlank
     *     }
     * )
     */
    private $alias;

    /**
     * @var string
     * @AwAssert\AndX(
     *     @AwAssert\Condition(
     *         if = "this.isBusinessAgent()",
     *         then = {
     *             @Assert\NotBlank
     *         }
     *     ),
     *     @Assert\Email
     * )
     */
    private $email;

    /**
     * @var string
     */
    private $notes;

    /**
     * @var int
     */
    private $sendemails;

    /**
     * @param string $firstname
     * @return $this
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $lastname
     * @return $this
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @return $this
     */
    public function setMidname($midname)
    {
        $this->midname = $midname;

        return $this;
    }

    /**
     * @return string
     */
    public function getMidname()
    {
        return $this->midname;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $notes
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param int $flag
     * @return $this
     */
    public function setSendemails($flag)
    {
        $this->sendemails = $flag;

        return $this;
    }

    /**
     * @return int
     */
    public function getSendemails()
    {
        return $this->sendemails;
    }

    public function clearName($value)
    {
        return Useragent::cleanName($value);
    }

    public function isBusinessAgent(): bool
    {
        $agent = $this->entity->getAgentid();

        return $agent ? $agent->isBusiness() : false;
    }
}
