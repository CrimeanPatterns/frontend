<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class AutoLoginResponse
{
    /**
     * @var string
     * @Type("string")
     */
    private $response;
    /**
     * @var string
     * @Type("string")
     */
    private $userData;

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserData()
    {
        return $this->userData;
    }

    /**
     * @param string $userData
     * @return $this
     */
    public function setUserData($userData)
    {
        $this->userData = $userData;

        return $this;
    }
}
