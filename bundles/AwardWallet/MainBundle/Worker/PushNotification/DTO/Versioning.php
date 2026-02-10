<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\DTO;

trait Versioning
{
    private $version;

    /**
     * @throws OutdatedClientException
     * @throws OutdatedMessageException
     * @throws \LogicException
     */
    public function __wakeup()
    {
        if (!defined('static::VERSION') || null === static::VERSION || null === $this->version) {
            throw new \LogicException(sprintf('class "%s" unversioned', __CLASS__));
        }

        $classVersion = (int) static::VERSION;

        if ($classVersion === $this->version) {
            return;
        }

        $error = sprintf('version mismatch: class "%s" :: "%s" != "%s"', __CLASS__, $classVersion, $this->version);

        if ($classVersion > $this->version) {
            throw new OutdatedMessageException($error);
        } else {
            throw new OutdatedClientException($error);
        }
    }

    protected function setVersion($version)
    {
        $this->version = (int) $version;
    }
}
