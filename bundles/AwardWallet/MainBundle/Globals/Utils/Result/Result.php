<?php

namespace AwardWallet\MainBundle\Globals\Utils\Result;

/**
 * @template E
 * @template S
 * @template-implemets ArrayAccess<int, E|S>.
 */
final class Result implements \ArrayAccess
{
    /**
     * @var bool
     */
    private $isSuccess;

    /**
     * @var E
     */
    private $fail;

    /**
     * @var S
     */
    private $success;

    /**
     * Result constructor.
     *
     * @param S $successValue
     * @param E $failValue
     */
    public function __construct(bool $isSuccess, $failValue, $successValue)
    {
        $this->isSuccess = $isSuccess;
        $this->success = $successValue;
        $this->fail = $failValue;
    }

    /**
     * @param S $success
     * @return Result<null, S>
     */
    public static function createSuccess($success): self
    {
        return new self(true, null, $success);
    }

    /**
     * @param E $fail
     * @return Result<S, null>
     */
    public static function createFail($fail): self
    {
        return new self(false, $fail, null);
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function isFail(): bool
    {
        return !$this->isSuccess;
    }

    /**
     * @return E
     */
    public function getFail()
    {
        if (!$this->isSuccess) {
            return $this->fail;
        }

        $this->throwShouldNotBeCalled();
    }

    /**
     * @return S
     */
    public function getSuccess()
    {
        if ($this->isSuccess) {
            return $this->success;
        }

        $this->throwShouldNotBeCalled();
    }

    /**
     * @return S|E|null
     */
    public function offsetGet($offset)
    {
        return $this->isSuccess ?
            $this->offsetGetHelper($this->success, 1, 0, $offset) :
            $this->offsetGetHelper($this->fail, 0, 1, $offset);
    }

    public function offsetExists($offset)
    {
        $this->throwShouldNotBeCalled();
    }

    public function offsetSet($offset, $value)
    {
        $this->throwShouldNotBeCalled();
    }

    public function offsetUnset($offset)
    {
        $this->throwShouldNotBeCalled();
    }

    private function offsetGetHelper($value, $first, $second, $offset)
    {
        if ($first === $offset) {
            return null;
        } elseif ($second === $offset) {
            return $value;
        } else {
            throw new \LogicException("Index should be {$first} or {$second}");
        }
    }

    private function throwShouldNotBeCalled()
    {
        throw new \LogicException('Should not be called!');
    }
}
