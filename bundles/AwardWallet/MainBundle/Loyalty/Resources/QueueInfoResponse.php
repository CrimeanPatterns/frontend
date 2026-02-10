<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class QueueInfoResponse
{
    /**
     * @var QueueInfoItem[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\QueueInfoItem>")
     */
    private $queues;

    /**
     * @return QueueInfoItem[]
     */
    public function getQueues()
    {
        return $this->queues;
    }

    /**
     * @param QueueInfoItem[] $queues
     * @return $this
     */
    public function setQueues($queues)
    {
        $this->queues = $queues;

        return $this;
    }
}
