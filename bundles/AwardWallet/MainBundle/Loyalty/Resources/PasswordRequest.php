<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class PasswordRequest
{
    /**
     * @var string
     * @Type("string")
     */
    protected $id;
    /**
     * @var string
     * @Type("string")
     */
    protected $partner;
    /**
     * @var string
     * @Type("string")
     */
    protected $provider;
    /**
     * @var string
     * @Type("string")
     */
    protected $login;
    /**
     * @var string
     * @Type("string")
     */
    protected $login2;
    /**
     * @var string
     * @Type("string")
     */
    protected $login3;
    /**
     * @var string
     * @Type("string")
     */
    protected $password;
    /**
     * @var string
     * @Type("string")
     */
    protected $note;
    /**
     * @var string
     * @Type("string")
     */
    protected $userId;
    /**
     * @var \DateTime
     * @Type("DateTime<'Y-m-d H:i:s'>")
     */
    protected $requestDate;

    public function getId()
    {
        return $this->id;
    }

    public function getRequestDate()
    {
        return $this->requestDate;
    }

    /**
     * @return $this
     */
    public function setRequestDate($requestDate)
    {
        $this->requestDate = $requestDate;

        return $this;
    }

    public function getPartner()
    {
        return $this->partner;
    }

    /**
     * @return $this
     */
    public function setPartner($partner)
    {
        $this->partner = $partner;

        return $this;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return $this
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    public function getLogin2()
    {
        return $this->login2;
    }

    /**
     * @return $this
     */
    public function setLogin2($login2)
    {
        $this->login2 = $login2;

        return $this;
    }

    public function getLogin3()
    {
        return $this->login3;
    }

    /**
     * @return $this
     */
    public function setLogin3($login3)
    {
        $this->login3 = $login3;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return $this
     */
    public function setPassword(string $password)
    {
        $this->password = $password;

        return $this;
    }

    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return $this
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }
}
