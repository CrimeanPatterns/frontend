<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 27.03.16
 * Time: 15:32.
 */

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class CheckAccountCallback extends CheckCallback
{
    /**
     * @var CheckAccountResponse[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse>")
     */
    private $response;

    /**
     * @return CheckAccountResponse[]
     */
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
