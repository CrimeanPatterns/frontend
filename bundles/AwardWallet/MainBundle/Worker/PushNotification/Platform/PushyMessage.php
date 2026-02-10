<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use RMS\PushNotificationsBundle\Message\MessageInterface;

class PushyMessage implements MessageInterface
{
    /**
     * @var string
     */
    private $deviceIdentifier;
    /**
     * @var string
     */
    private $message;
    /**
     * @var array
     */
    private $options = [];
    /**
     * @var array
     */
    private $data = [];

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setDeviceIdentifier($identifier)
    {
        $this->deviceIdentifier = $identifier;
    }

    public function getMessageBody()
    {
        $messageBody = [
            'to' => $this->deviceIdentifier,
            'data' => array_merge(
                [
                    'message' => $this->message,
                ],
                $this->data ? ['payload' => $this->data] : []
            ),
        ];

        if (isset($this->options)) {
            if (isset($this->options['ttl'])) {
                $messageBody['time_to_live'] = $this->options['ttl'];
            }

            $encrypted = isset($this->options['encrypt']) && $this->options['encrypt'];

            if ($encrypted) {
                $messageBody['data'] = [
                    'ciphertext' => $messageBody['data'], // TODO: implement
                ];
            }
        }

        return $messageBody;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getDeviceIdentifier()
    {
        return $this->deviceIdentifier;
    }

    public function getTargetOS()
    {
        return 'aw.rms_push_notifications.os.pushy';
    }
}
