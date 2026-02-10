<?php

namespace AwardWallet\MainBundle\Service\GeoLocation\PointOutboxProcessor;

use AwardWallet\MainBundle\Entity\Outbox;
use AwardWallet\MainBundle\Service\OutboxProcessorInterface;

class UsrLastLogonPointOutboxProcessor implements OutboxProcessorInterface
{
    private PointProcessor $pointProcessor;

    public function __construct(PointProcessor $pointProcessor)
    {
        $this->pointProcessor = $pointProcessor;
    }

    public function process(array $outboxItem): void
    {
        $this->pointProcessor->process($outboxItem, 'UsrLastLogonPoint', 'UserID');
    }

    public static function getSupportedOutboxTypes(): array
    {
        return [Outbox::TYPE_USR_LAST_LOGON_POINT, Outbox::TYPE_USR_LAST_LOGON_POINT_INITIAL_IMPORT];
    }
}
