<?php

namespace AwardWallet\MainBundle\Globals\Utils\Result;

/**
 * @template B
 * @template-implements ResultInterface<null, B>
 */
final class Fail implements ResultInterface
{
    use ArrayAccessTrait;

    /**
     * @var B|null
     */
    private $reason;

    /**
     * @param B|null $reason
     */
    public function __construct($reason = null)
    {
        $this->reason = $reason;
    }

    public function unwrap()
    {
        return $this->reason;
    }

    public function offsetGet($offset)
    {
        return $this->offsetGetHelper($this->reason, 0, 1, $offset);
    }

    public function isSuccess(): bool
    {
        return false;
    }

    public function isFail(): bool
    {
        return true;
    }
}
