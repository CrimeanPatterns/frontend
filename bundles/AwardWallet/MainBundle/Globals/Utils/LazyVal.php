<?php

namespace AwardWallet\MainBundle\Globals\Utils;

use function AwardWallet\MainBundle\Globals\Utils\iter\toIterInternal;

/**
 * @template T
 */
class LazyVal implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    private const STATE_EMPTY = 0;
    private const STATE_INITIALIZING = 1;
    private const STATE_INITIALIZED = 2;

    /**
     * @var T
     */
    private $value;

    /**
     * @var int
     */
    private $state = self::STATE_EMPTY;

    /**
     * @var callable():T
     */
    private $initializer;

    /**
     * @param callable():T $initializer
     */
    public function __construct(callable $initializer)
    {
        $this->initializer = $initializer;
    }

    /**
     * @return T
     */
    public function __invoke()
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return $this->value;
    }

    public function __sleep()
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return ['value'];
    }

    public function __wakeup()
    {
        $this->state = self::STATE_INITIALIZED;
    }

    /**
     * @psalm-if-this-is self<object>
     */
    public function __call($name, $arguments)
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return $this->value->{$name}(...$arguments);
    }

    /**
     * @psalm-if-this-is self<object>
     */
    public function __get($name)
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return $this->value->{$name};
    }

    /**
     * @psalm-if-this-is self<object>
     */
    public function __set($name, $value)
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        $this->value->{$name} = $value;
    }

    /**
     * @psalm-if-this-is self<object>
     */
    public function __isset($name)
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return isset($this->value->{$name});
    }

    /**
     * @psalm-if-this-is self<object>
     */
    public function __unset($name)
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        unset($this->value->{$name});
    }

    public function __toString()
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return (string) $this->value;
    }

    public function __debugInfo()
    {
        return [
            'value' => $this->value,
            'state' => $this->state,
        ];
    }

    public static function __set_state($array)
    {
        $obj = new self('\\intval');
        $obj->value = $array['value'];
        $obj->state = self::STATE_INITIALIZED;

        return $obj;
    }

    /**
     * @return T
     */
    public function getValue()
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return $this->value;
    }

    /**
     * @psalm-if-this-is self<array|\ArrayAccess>
     */
    public function offsetGet($offset)
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return $this->value[$offset];
    }

    /**
     * @psalm-if-this-is self<array|\ArrayAccess>
     */
    public function offsetExists($offset)
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return isset($this->value[$offset]);
    }

    /**
     * @psalm-if-this-is self<array|\Countable>
     */
    public function count()
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return \count($this->value);
    }

    /**
     * @psalm-if-this-is self<iterable>
     */
    public function getIterator()
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return toIterInternal($this->value);
    }

    public function jsonSerialize()
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        return $this->value;
    }

    /**
     * @template T1
     * @param callable(T): T1 $updater
     * @return self<T1>
     */
    public function map(callable $updater): self
    {
        return new self(fn () => $updater($this->getValue()));
    }

    /**
     * @template T1
     * @param callable(T): self<T1> $updater
     * @return self<T1>
     */
    public function flatMap(callable $updater): self
    {
        return new self(fn () => $updater($this->getValue())->getValue());
    }

    /**
     * @psalm-if-this-is self<array|\ArrayAccess>
     */
    public function offsetSet($offset, $value)
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        $this->value[$offset] = $value;
    }

    /**
     * @psalm-if-this-is self<array|\ArrayAccess>
     */
    public function offsetUnset($offset)
    {
        if (self::STATE_INITIALIZED !== $this->state) {
            $this->initialize();
        }

        unset($this->value[$offset]);
    }

    /**
     * @template V
     * @param callable():V $initializer
     * @return self<V>
     */
    public static function make(callable $initializer): self
    {
        return new self($initializer);
    }

    private function initialize(): void
    {
        if (self::STATE_INITIALIZING === $this->state) {
            throw new \LogicException('Circular reference!');
        }

        $this->state = self::STATE_INITIALIZING;

        try {
            $this->value = ($this->initializer)();
        } catch (\Throwable $e) {
            $this->state = self::STATE_EMPTY;

            throw $e;
        }

        $this->state = self::STATE_INITIALIZED;
        $this->initializer = null;
    }
}
