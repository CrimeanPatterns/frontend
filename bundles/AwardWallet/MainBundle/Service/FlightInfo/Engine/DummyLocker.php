<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class DummyLocker implements LockerInterface
{
    protected $failures_to_lockout;
    protected $lockout_timeout;
    protected $reset_timeout;

    public function __construct($failures_to_lockout, $lockout_timeout, $reset_timeout)
    {
        $this->failures_to_lockout = $failures_to_lockout;
        $this->lockout_timeout = $lockout_timeout;
        $this->reset_timeout = $reset_timeout;
    }

    public function failure()
    {
        return false;
    }

    public function locked()
    {
        return false;
    }

    public function reset()
    {
    }
}
