<?php

namespace AwardWallet\MainBundle\Globals\Utils;

/**
 * @template T
 */
class RingBuffer
{
    private array $buffer;
    private int $position;
    private bool $isFull;

    public function __construct(int $size)
    {
        $this->buffer = \array_fill(0, $size, null);
        $this->position = 0;
        $this->isFull = false;
    }

    /**
     * @param T $value
     */
    public function push($value): void
    {
        $this->buffer[$this->position] = $value;
        $this->position = ($this->position + 1) % \count($this->buffer);

        if ($this->position === 0) {
            $this->isFull = true;
        }
    }

    /**
     * @return list<T>
     */
    public function all(): array
    {
        return $this->isFull ? $this->buffer : \array_slice($this->buffer, 0, $this->position);
    }
}
