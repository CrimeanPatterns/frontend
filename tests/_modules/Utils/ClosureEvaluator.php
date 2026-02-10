<?php

namespace AwardWallet\Tests\Modules\Utils\ClosureEvaluator;

/**
 * @template T of object
 * @param \Closure(T): mixed $closure
 * @return T
 */
function create(\Closure $closure): object
{
    $evaluated =
        (new \ReflectionFunction($closure))
        ->getParameters()[0]
        ->getClass()
        ->newInstance();

    $closure($evaluated);

    return $evaluated;
}

/**
 * @template T of object
 * @param T $object
 * @param list<\Closure(T):mixed> $modifiers
 * @return T
 */
function modifyEncapsulated(object $object, \Closure ...$modifiers): object
{
    foreach ($modifiers as $modifier) {
        $modifierBind = \Closure::bind($modifier, $object, \get_class($object));
        $modifierBind($object);
    }

    return $object;
}

/**
 * @template T of object
 * @param T $object
 * @param list<\Closure(T):mixed> $modifiers
 * @return T
 */
function cloneAndModify(object $object, \Closure ...$modifiers): object
{
    $object = clone $object;

    foreach ($modifiers as $modifier) {
        $modifier($object);
    }

    return $object;
}
/**
 * @template T of object
 * @param T $object
 * @param list<\Closure(T):mixed> $modifiers
 * @return T
 */
function cloneAndModifyEncapsulated(object $object, \Closure ...$modifiers): object
{
    $object = clone $object;

    foreach ($modifiers as $modifier) {
        $modifierBind = \Closure::bind($modifier, $object, \get_class($object));
        $modifierBind($object);
    }

    return $object;
}

class Counter
{
    /**
     * @var int 0 - no checks
     */
    private $checkCount = 0;

    public function nextCheck()
    {
        $this->checkCount++;
    }

    public function getCount(): int
    {
        return $this->checkCount;
    }
}

class Diff
{
    /**
     * @var array
     */
    private $storage;
    /**
     * @var Counter
     */
    private $diffCounter;

    public function __construct(array $storage, Counter $diffCounter)
    {
        $this->storage = \array_values($storage);
        $this->diffCounter = $diffCounter;
    }

    public function __invoke()
    {
        return $this->getValue();
    }

    public function getValue()
    {
        $diffCounter = $this->diffCounter->getCount();

        return \array_key_exists($diffCounter, $this->storage) ?
            $this->storage[$diffCounter] :
            $this->storage[\count($this->storage) - 1];
    }
}

class DiffFactory
{
    /**
     * @var Counter
     */
    private $diffCounter;

    public function __construct(Counter $diffCounter)
    {
        $this->diffCounter = $diffCounter;
    }

    public function __invoke(...$storage)
    {
        return $this->createDiff($storage)->getValue();
    }

    public function createDiff(array $storage): Diff
    {
        return new Diff($storage, $this->diffCounter);
    }
}

class DateTimeImmutableFormatted extends \DateTimeImmutable
{
    /**
     * @var string
     */
    private $defaultFormat;

    public function __invoke(?string $autoModify = null)
    {
        if (!isset($this->defaultFormat)) {
            throw new \RuntimeException('No default format is specified!');
        }

        $date = \is_null($autoModify) ? $this : $this->modify($autoModify);

        return $date->format($this->defaultFormat);
    }

    public function withDefaultFormat(string $format): self
    {
        $self = new self(
            $this->format('Y-m-d H:i:s'),
            $this->getTimezone()
        );
        $self->defaultFormat = $format;

        return $self;
    }

    public function modify($modify)
    {
        $self = new self(
            parent::modify($modify)->format('Y-m-d H:i:s'),
            $this->getTimezone()
        );
        $self->defaultFormat = $this->defaultFormat;

        return $self;
    }

    public function add($interval): \DateTimeImmutable
    {
        $this->throwUnimplemented();
    }

    public function setDate($year, $month, $day): \DateTimeImmutable
    {
        $this->throwUnimplemented();
    }

    public function setISODate($year, $week, $day = 1): \DateTimeImmutable
    {
        $this->throwUnimplemented();
    }

    public function setTime($hour, $minute, $second = 0, $microseconds = 0): \DateTimeImmutable
    {
        $this->throwUnimplemented();
    }

    public function setTimestamp($unixtimestamp): \DateTimeImmutable
    {
        $this->throwUnimplemented();
    }

    public function setTimezone($timezone): \DateTimeImmutable
    {
        $this->throwUnimplemented();
    }

    public function sub($interval): \DateTimeImmutable
    {
        $this->throwUnimplemented();
    }

    protected function throwUnimplemented()
    {
        throw new \LogicException('Unimplemented! But YOU can implement this!');
    }
}
