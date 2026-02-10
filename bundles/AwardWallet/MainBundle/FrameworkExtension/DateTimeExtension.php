<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

class DateTimeExtension extends \DateTime
{
    public function __toString()
    {
        return $this->format('U');
    }
}
