<?php

namespace AwardWallet\MainBundle\Service\Notification;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;

/**
 * @NoDI
 */
class TransformedContent
{
    public $message;
    public $payload;
    /**
     * @var string
     */
    public $type;
    /**
     * @var Options
     */
    public $options;

    public function __construct($message, $payload, $type, ?Options $options = null)
    {
        $this->message = $message;
        $this->payload = $payload;
        $this->type = $type;
        $this->options = $options;
    }
}
