<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\DTO;

abstract class InterruptionLevel
{
    public const PASSIVE = "passive";
    public const ACTIVE = "active";
    public const TIME_SENSITIVE = "time-sensitive";
    public const CRITICAL = "critical";
}
