<?php

namespace AwardWallet\MainBundle\Service\GeoLocation\PointOutboxProcessor;

use AwardWallet\MainBundle\Entity\Outbox;
use AwardWallet\MainBundle\Service\OutboxProcessorInterface;

class UserIPPointOutboxProcessor implements OutboxProcessorInterface
{
    private PointProcessor $pointProcessor;

    public function __construct(PointProcessor $pointProcessor)
    {
        $this->pointProcessor = $pointProcessor;
    }

    public function process(array $outboxItem): void
    {
        $this->pointProcessor->process($outboxItem, 'UserIPPoint', 'UserIPID');
    }

    public static function getSupportedOutboxTypes(): array
    {
        return [Outbox::TYPE_USERIP_POINT, Outbox::TYPE_USERIP_POINT_INITIAL_IMPORT];
    }
}
