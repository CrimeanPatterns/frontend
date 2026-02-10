<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @AwAssert\AndX(constraints = {
 *     @AwAssert\Condition(
 *         if = "this.isReauthRequired()",
 *         then = {
 *             @AwAssert\AndX(constraints = {
 *                 @AwAssert\ConstraintReference(
 *                     sourceClass = Usr::class,
 *                     sourceProperty = "pass",
 *                     targetProperty = "password",
 *                     skipSourceClassConstraints = true,
 *                     excludedConstraints = {
 *                         AwAssert\ByteLength::class,
 *                         Assert\Callback::class
 *                     }
 *                 ),
 *                 @AwAssert\AntiBruteforceLocker(
 *                     service = "aw.security.antibruteforce.password",
 *                     keyMethod = "getPasswordLockerKey"
 *                 ),
 *                 @AwAssert\UserPassword(
 *                     service = "aw.form.validator.user_password.disallow_master_pass",
 *                     message = "invalid.password",
 *                     property = "password"
 *                 )
 *             })
 *         }
 *     ),
 *     @AwAssert\ConstraintReference(
 *         sourceClass = Usr::class,
 *         sourceProperty = "email",
 *         skipSourceClassConstraints = true,
 *         excludedConstraints = {
 *             Assert\Callback::class
 *         }
 *     ),
 *     @AwAssert\AntiBruteforceLocker(
 *         service = "aw.security.antibruteforce.check_email",
 *         keyMethod = "getEmailLockerKey"
 *     ),
 *     @AwAssert\UniqueEntity(
 *         fields = {"email"},
 *         errorPath = "email",
 *         message="user.email_taken"
 *     ),
 *     @AwAssert\Service(
 *         name = "AwardWallet\MainBundle\Validator\UserEmailValidator",
 *         method = "validate"
 *     )
 * })
 * @property Usr $entity
 */
class EmailModel extends AbstractEntityAwareModel
{
    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var bool
     */
    private $reauthRequired = true;

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return EmailModel
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return EmailModel
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getPasswordLockerKey()
    {
        return (string) $this->entity->getUserid();
    }

    public function getEmailLockerKey()
    {
        return $this->ip;
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

    public function isReauthRequired(): bool
    {
        return $this->reauthRequired;
    }

    public function setReauthRequired(bool $reauthRequired): EmailModel
    {
        $this->reauthRequired = $reauthRequired;

        return $this;
    }
}
