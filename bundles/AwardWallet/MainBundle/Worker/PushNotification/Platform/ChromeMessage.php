<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use RMS\PushNotificationsBundle\Message\MessageInterface;

class ChromeMessage implements MessageInterface
{
    /**
     * String message.
     *
     * @var string
     */
    protected $message;

    /**
     * The data to send in the message.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Identifier of the target device.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Chrome notification options.
     *
     * @see https://developer.chrome.com/apps/notifications#type-NotificationOptions
     * @var array
     */
    protected $options;

    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Sets the data. For Android, this is any custom data to use.
     *
     * @param array $data The custom data to send
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    public function setDeviceIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getMessageBody()
    {
        $result = json_decode($this->identifier, true);
        $result['payload'] = [
            'title' => $this->message,
            'message' => [
                'body' => $this->data['body'],
                //				'icon' => '/awardwallet-256x256.png',
                'data' => [
                    'url' => $this->data['url'],
                ],
                'requireInteraction' => true,
            ],
        ];

        if ($this->options) {
            //            if (isset($this->options['requireInteraction'])) {
            //                $result['payload']['message']['requireInteraction'] = $this->options['requireInteraction'];
            //            }
            //
            if (isset($this->options['ttl'])) {
                $result['ttl'] = $this->options['ttl'];
            }
        }

        return $result;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getDeviceIdentifier()
    {
        return $this->identifier;
    }

    public function getTargetOS()
    {
        return "aw.rms_push_notifications.os.chrome";
    }
}
