<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 27.03.16
 * Time: 15:35.
 */

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class CheckConfirmationCallback extends CheckCallback
{
    /**
     * @var CheckConfirmationResponse[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationResponse>")
     */
    private $response;

    /**
     * @return CheckConfirmationResponse[]
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
