<?php

namespace AwardWallet\MainBundle\Form\Model;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\EntityContainerInterface;

/**
 * Class AddAgent.
 *
 * @AwAssert\AndX(constraints = {
 *     @AwAssert\ConstraintReference(
 *         sourceClass = Useragent::class,
 *         excludedProperties = {
 *            "invite",
 *            "ip",
 *            "entity"
 *         }
 *     ),
 *     @AwAssert\ConditionOld(
 *         if = "isEmailVerificationRequired",
 *         then = {
 *             @AwAssert\AndX(constraints = {
 *                 @AwAssert\Service(
 *                     name = "aw.mobile.form.validator.add_agent",
 *                     method = "checkInviterEmailVerified"
 *                 ),
 *                 @AwAssert\Service(
 *                     name = "aw.mobile.form.validator.add_agent",
 *                     method = "validateEmail",
 *                     errorPath = "email"
 *                 ),
 *             })
 *         }
 *     ),
 *     @AwAssert\AntiBruteforceLocker(
 *         service = "aw.security.antibruteforce.add_connection",
 *         keyMethod = "getKeyForUser"
 *     ),
 *     @AwAssert\AntiBruteforceLocker(
 *         service = "aw.security.antibruteforce.add_connection",
 *         keyMethod = "getKeyForIp"
 *     ),
 *     @AwAssert\Service(
 *         name = "aw.mobile.form.validator.add_agent",
 *         method = "checkAgentCount"
 *     ),
 *     @AwAssert\Service(
 *         name = "aw.mobile.form.validator.add_agent",
 *         method = "checkAgentExists"
 *     )
 * })
 * @AwAssert\Service(
 *     name = "aw.mobile.form.validator.add_agent",
 *     method = "validateNamePart",
 *     errorPath = "firstname"
 * )
 * @AwAssert\Service(
 *     name = "aw.mobile.form.validator.add_agent",
 *     method = "validateNamePart",
 *     errorPath = "lastname"
 * )
 */
class AddAgentModel implements EntityContainerInterface
{
    public const PLATFORM_WEB = 'web';
    public const PLATFORM_MOBILE = 'mobile';

    /**
     * @var string
     */
    private $firstname;
    /**
     * @var string
     */
    private $lastname;
    /**
     * @var string
     */
    private $email;
    /**
     * @var bool
     */
    private $invite;
    /**
     * @var Usr
     */
    private $inviter;
    /**
     * @var string
     */
    private $ip;
    /**
     * @var Useragent
     */
    private $entity;
    private string $platform;

    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function isEmailVerificationRequired()
    {
        return (bool) $this->invite;
    }

    public function setInviter(Usr $user): self
    {
        $this->inviter = $user;

        return $this;
    }

    public function getKeyForUser()
    {
        return (string) $this->inviter->getUserid();
    }

    public function getKeyForIp()
    {
        return $this->ip;
    }

    /**
     * @return Usr
     */
    public function getInviter()
    {
        return $this->inviter;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $ip
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @param string $firstname
     * @return AddAgentModel
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @param string $lastname
     * @return AddAgentModel
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @param string $email
     * @return AddAgentModel
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param bool $invite
     * @return AddAgentModel
     */
    public function setInvite($invite)
    {
        $this->invite = $invite;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInvite()
    {
        return $this->invite;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): AddAgentModel
    {
        $this->platform = $platform;

        return $this;
    }
}
