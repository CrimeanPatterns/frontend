<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\ExternalTracking;

class Superfly implements ExternalTrackingInterface
{
    public const EVENT_COMPLETE_REGISTRATION = 'CompleteRegistration';
    /**
     * @var string[]
     */
    private $events;

    public function __construct(array $events)
    {
        $this->events = $events;
    }

    public function getData(): array
    {
        return [
            'type' => 'superfly',
            'data' => $this->events,
        ];
    }
}
