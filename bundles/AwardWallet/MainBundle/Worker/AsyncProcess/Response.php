<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

class Response
{
    public const STATUS_NONE = 'none';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_ERROR = 'error';
    public const STATUS_READY = 'ready';

    /**
     * @var string
     */
    public $status = self::STATUS_NONE;

    /**
     * @var int
     */
    public $executionCount = 0;

    /**
     * @param string $status - one of STATUS_ constants
     */
    public function __construct(string $status = self::STATUS_NONE)
    {
        $this->status = $status;
    }
}
