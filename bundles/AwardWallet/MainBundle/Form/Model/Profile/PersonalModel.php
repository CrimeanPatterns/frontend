<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints\Callback;

/**
 * Class PersonalModel.
 *
 * @AwAssert\AndX(constraints = {
 *     @AwAssert\AntiBruteforceLocker(
 *         service = "aw.security.antibruteforce.check_login",
 *         keyMethod = "getLockerKey"
 *     ),
 *     @AwAssert\ConstraintReference(
 *         sourceClass = Usr::class,
 *         excludedConstraints = {
 *             Callback::class
 *         },
 *         excludedProperties = {
 *             "entity",
 *             "id",
 *             "ip"
 *         }
 *     )
 * })
 * @property Usr $entity
 */
class PersonalModel extends AbstractEntityAwareModel
{
    /**
     * @var string
     */
    private $login;
    /**
     * @var string
     */
    private $firstname;
    /**
     * @var string
     */
    private $midname;
    /**
     * @var string
     */
    private $lastname;
    /**
     * @var string
     */
    private $ip;

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     * @return PersonalModel
     */
    public function setLogin($login)
    {
        $this->login = $login;

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
     * @param string $firstname
     * @return PersonalModel
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

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
     * @param string $lastname
     * @return PersonalModel
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getLockerKey()
    {
        return $this->ip;
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
     * @param string $midname
     * @return PersonalModel
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
}
