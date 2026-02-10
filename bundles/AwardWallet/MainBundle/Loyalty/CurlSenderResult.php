<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 06.04.16
 * Time: 16:09.
 */

namespace AwardWallet\MainBundle\Loyalty;

class CurlSenderResult
{
    private $code;
    private $error;
    private $response;

    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }
}
