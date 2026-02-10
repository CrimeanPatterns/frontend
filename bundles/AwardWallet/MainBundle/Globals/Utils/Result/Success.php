<?php

namespace AwardWallet\MainBundle\Globals\Utils\Result;

/**
 * @template A
 * @template-implements ResultInterface<A, null>
 */
final class Success implements ResultInterface
{
    use ArrayAccessTrait;

    /**
     * @var A|null
     */
    private $value;

    /**
     * @param A|null $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

    /**
     * @return A
     */
    public function unwrap()
    {
        return $this->value;
    }

    public function offsetGet($offset)
    {
        return $this->offsetGetHelper($this->value, 1, 0, $offset);
    }

    public function isSuccess(): bool
    {
        return true;
    }

    public function isFail(): bool
    {
        return false;
    }
}
