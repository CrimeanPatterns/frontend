<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class PasswordModel.
 *
 * @AwAssert\AndX(constraints = {
 *     @AwAssert\Condition(
 *         if = "this.isOldPasswordRequired()",
 *         then = {
 *             @AwAssert\AndX(constraints = {
 *                 @AwAssert\AntiBruteforceLocker(
 *                     service = "aw.security.antibruteforce.login",
 *                     keyMethod = "getLockerKey"
 *                 ),
 *                 @AwAssert\ConstraintReference(
 *                     sourceClass = Usr::class,
 *                     sourceProperty = "pass",
 *                     targetProperty = "oldPassword",
 *                     excludedConstraints = {
 *                         AwAssert\ByteLength::class,
 *                         Assert\Callback::class
 *                     },
 *                     clone = true
 *                 ),
 *                 @AwAssert\UserPassword(
 *                     service = "aw.form.validator.user_password.disallow_master_pass",
 *                     message = "invalid.password",
 *                     property = "oldPassword"
 *                 )
 *             })
 *         }
 *     ),
 *     @AwAssert\ConstraintReference(
 *         sourceClass = Usr::class,
 *         excludedConstraints = {
 *             Assert\Callback::class
 *         },
 *         sourceProperty = "pass",
 *         clone = true
 *     ),
 *     @AwAssert\Service(
 *         name="aw.form.validator.not_same_password"
 *     )
 * })
 * @property Usr $entity
 */
class PasswordModel extends AbstractEntityAwareModel
{
    /**
     * @var string
     */
    private $oldPassword;

    /**
     * @var string
     */
    private $pass;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var bool
     */
    private $oldPasswordRequired = true;

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $email;

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param string $pass
     * @return $this
     */
    public function setPass($pass)
    {
        $this->pass = $pass;

        return $this;
    }

    public function getLockerKey()
    {
        return "change_password_" . ($this->entity ?
            (string) $this->entity->getUserid() :
            $this->ip);
    }

    /**
     * @return string
     */
    public function getOldPassword()
    {
        return $this->oldPassword;
    }

    /**
     * @param string $oldPassword
     * @return $this
     */
    public function setOldPassword($oldPassword)
    {
        $this->oldPassword = $oldPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
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

    /**
     * @return bool
     */
    public function isOldPasswordRequired()
    {
        return $this->oldPasswordRequired;
    }

    /**
     * @param bool $oldPasswordRequired
     * @return $this
     */
    public function setOldPasswordRequired($oldPasswordRequired)
    {
        $this->oldPasswordRequired = $oldPasswordRequired;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     * @return $this
     */
    public function setLogin($login)
    {
        $this->login = $login;

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
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }
}
