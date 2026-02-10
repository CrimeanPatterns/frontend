<?php
/**
 * Created by PhpStorm.
 * User: puzakov
 * Date: 14/04/2017
 * Time: 17:55.
 */

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Discriminator;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

/**
 * Class CheckCallback.
 *
 * @property $type
 * @property $priority
 * @Discriminator(field = "method",
 * map = {
 * 		"account": "AwardWallet\MainBundle\Loyalty\Resources\CheckAccountCallback",
 * 		"confirmation": "AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationCallback"
 * })
 */
abstract class CheckCallback
{
    /**
     * @var string
     * @Type("string")
     * @SerializedName("method")
     */
    protected $type;

    abstract public function getResponse();

    abstract public function setResponse($response);

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
