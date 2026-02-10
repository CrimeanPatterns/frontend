<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use RMS\PushNotificationsBundle\Message\MacMessage;

class SafariWebPushMessage extends MacMessage
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

    /**
     * Gets the full message body to send to APN.
     *
     * @return array
     */
    public function getMessageBody()
    {
        return [
            'aps' => [
                'alert' => [
                    'title' => $this->message,
                    'body' => $this->data['body'],
                ],
                'url-args' => [substr($this->data['url'], 1)],
            ],
        ];
    }
}
