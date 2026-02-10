<?php

namespace AwardWallet\MainBundle\Service\Notification;

use AwardWallet\MainBundle\Entity\MobileDevice;

interface TransformerInterface
{
    /**
     * @return ?TransformedContent - return null to prevent sending, for example mobile device does not support this type of notifications
     */
    public function transform(MobileDevice $device, Content $content): ?TransformedContent;
}
