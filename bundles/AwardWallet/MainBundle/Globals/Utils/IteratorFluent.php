<?php

namespace AwardWallet\MainBundle\Globals\Utils;

use ArrayAccess;
use AwardWallet\MainBundle\Globals\Utils\iter as iterAux;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use IteratorAggregate;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template K
 * @template V
 * @implements IteratorAggregate<K, V>
 * @implements ArrayAccess<K|null, V>
 */
class IteratorFluent implements \IteratorAggregate, \ArrayAccess, \JsonSerializable, \Countable, \Serializable, Selectable
{
    /**
     * @var iterable|\Iterator
     */
    protected $iterable;
    /**
     * @var bool
     */
    protected $innerIteratorInitialized = false;

    public function __construct(iterable $iterable)
    {
        $this->iterable = $iterable;
    }

    public function __get($field): self
    {
        return $this->field($field);
    }

    public function __call($name, $arguments): self
    {
        return $this->map(function ($value) use ($name, $arguments) {
            return $value->{$name}(...$arguments);
        });
    }

    public function __invoke(callable $function): self
    {
        return $this->map($function);
    }

    public function getIterator()
    {
        return $this->iterable instanceof \Traversable ?
            $this->iterable :
            iterAux\toIterInternal($this->iterable);
    }

    public function lazy(): self
    {
        $this->initialize();

        return $this;
    }

    /**
     * @template VMap
     * @param callable(V): VMap $function
     * @return static<K, VMap>
     */
    public function map(callable $function): self
    {
        $this->iterable = \iter\map($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @template VMapIndexed
     * @param callable(V, K): VMapIndexed $function
     * @return static<K, VMapIndexed>
     */
    public function mapIndexed(callable $function): self
    {
        $this->iterable = iterAux\mapIndexed($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapVariadic(callable $function): self
    {
        $this->iterable = iterAux\mapVariadic($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @template VFlatMap
     * @template KFlatMap
     * @param callable(V, K): iterable<KFlatMap, VFlatMap> $function
     * @return static<KFlatMap, VFlatMap>
     */
    public function flatMapIndexed(callable $function): self
    {
        $this->iterable = iterAux\flatMapIndexed($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function flatMapVariadic(callable $function)
    {
        $this->iterable = iterAux\flatMapVariadic($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapKeys(callable $function): self
    {
        $this->iterable = \iter\mapKeys($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @template VFlatMap
     * @template KFlatMap
     * @param callable(V): iterable<KFlatMap, VFlatMap> $function
     * @return static<KFlatMap, VFlatMap>
     */
    public function flatMap(callable $function): self
    {
        $this->iterable = \iter\flatMap($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function flatMapColumn(string $column): self
    {
        $this->iterable = iterAux\flatMapColumn($column, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function flatMapField(string $field): self
    {
        $this->iterable = iterAux\flatMapField($field, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function flatMapPropertyPath($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): self
    {
        $this->iterable = iterAux\flatMapPropertyPath($propertyPath, $this->iterable, $propertyAccessor);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function transform(callable $callable): self
    {
        $this->iterable = iterAux\transform($this->iterable, $callable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function toPairsWithFirst($firstValue): self
    {
        $this->iterable = iterAux\toPairsWithFirst($firstValue, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function toPairsWithSecond($secondValue): self
    {
        $this->iterable = iterAux\toPairsWithSecond($secondValue, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @template KReindex
     * @param callable(V): KReindex $function
     * @return static<KReindex, V>
     */
    public function reindex(callable $function): self
    {
        $this->iterable = \iter\reindex($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function apply(callable $function): void
    {
        \iter\apply($function, $this->iterable);
    }

    public function forEach(callable $function): void
    {
        \iter\apply($function, $this->iterable);
    }

    /**
     * @param callable $function - function($value, $key)
     */
    public function applyIndexed(callable $function): void
    {
        iterAux\applyIndexed($function, $this->iterable);
    }

    public function forEachIndexed(callable $function): void
    {
        iterAux\applyIndexed($function, $this->iterable);
    }

    /**
     * @param callable(V): bool $function
     * @return static<K, V>
     */
    public function filter(callable $function): self
    {
        $this->iterable = \iter\filter($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterIndexed(callable $function): self
    {
        $this->iterable = iterAux\filterIndexed($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotIndexed(callable $function): self
    {
        $this->iterable = iterAux\filterNotIndexed($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function enumerate(): self
    {
        $this->iterable = \iter\enumerate($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function toPairs(): self
    {
        $this->iterable = \iter\toPairs($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function fromPairs(): self
    {
        $this->iterable = \iter\fromPairs($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function reduce(callable $function, $startValue = null)
    {
        return \iter\reduce($function, $this->iterable, $startValue);
    }

    public function fold($startValue, callable $function)
    {
        return \iter\reduce($function, $this->iterable, $startValue);
    }

    public function scanLeft($neutral, callable $operation): self
    {
        $this->iterable = iterAux\scanLeft($this->iterable, $neutral, $operation);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function scanLeftIndexed($neutral, callable $operation): self
    {
        $this->iterable = iterAux\scanLeftIndexed($this->iterable, $neutral, $operation);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function reductions(callable $function, $startValue = null): self
    {
        $this->iterable = \iter\reductions($function, $this->iterable, $startValue);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function zip(iterable ...$iterables): self
    {
        $this->iterable = \iter\zip($this->iterable, ...$iterables);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function chain(iterable ...$iterables): self
    {
        $this->iterable = \iter\chain($this->iterable, ...$iterables);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function product(iterable ...$iterables): self
    {
        $this->iterable = \iter\product($this->iterable, ...$iterables);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function slice(int $start, $length = INF): self
    {
        $this->iterable = iterAux\slice($this->iterable, $start, $length);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @return static<K, V>
     */
    public function take(int $num): self
    {
        $this->iterable = iterAux\take($num, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function drop(int $num): self
    {
        return $this->slice($num);
    }

    public function tail(): self
    {
        return $this->slice(1);
    }

    /**
     * @return static<int, K>
     */
    public function keys(): self
    {
        $this->iterable = \iter\keys($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function values(): self
    {
        $this->iterable = \iter\values($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function none(callable $predicate): bool
    {
        return iterAux\none($predicate, $this->iterable);
    }

    /**
     * @param callable(V): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        return \iter\any($predicate, $this->iterable);
    }

    public function all(callable $predicate): bool
    {
        return \iter\all($predicate, $this->iterable);
    }

    public function search(callable $predicate)
    {
        return \iter\search($predicate, $this->iterable);
    }

    public function find(callable $predicate)
    {
        return $this->search($predicate);
    }

    public function takeWhile(callable $predicate): self
    {
        $this->iterable = \iter\takeWhile($predicate, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function dropWhile(callable $predicate): self
    {
        $this->iterable = \iter\dropWhile($predicate, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function flatten($levels = INF): self
    {
        $this->iterable = \iter\flatten($this->iterable, $levels);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function flip(): self
    {
        $this->iterable = \iter\flip($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @return static<int, list<V>>
     */
    public function chunk(int $size): self
    {
        $this->iterable = \iter\chunk($this->iterable, $size, false);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function chunkWithKeys(int $size)
    {
        $this->iterable = \iter\chunk($this->iterable, $size, true);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function chunkLazy(int $size): self
    {
        $this->iterable = iterFluent\mapToIter(iterAux\chunkLazy($this->iterable, $size));
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function slidingPartial(int $windowSize, int $stepSize = 1): self
    {
        $this->iterable = iterAux\slidingPartial($this->iterable, $windowSize, $stepSize);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function slidingPartialWithKeys(int $windowSize, int $stepSize = 1): self
    {
        $this->iterable = iterAux\slidingPartialWithKeys($this->iterable, $windowSize, $stepSize);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function sliding(int $windowSize, int $stepSize = 1): self
    {
        $this->iterable = iterAux\sliding($this->iterable, $windowSize, $stepSize);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function slidingWithKeys(int $windowSize, int $stepSize = 1): self
    {
        $this->iterable = iterAux\slidingWithKeys($this->iterable, $windowSize, $stepSize);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function joinToString(string $separator = ''): string
    {
        if (!\is_array($this->iterable)) {
            return \iter\join($separator, $this->iterable);
        } else {
            return \implode($separator, $this->iterable);
        }
    }

    public function count(): int
    {
        return \iter\count($this->iterable);
    }

    public function increment(int &$counter): self
    {
        $this->iterable = iterAux\increment($this->iterable, $counter);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function decrement(int &$counter): self
    {
        $this->iterable = iterAux\decrement($this->iterable, $counter);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function nth(int $n, ?int &$counter = null): self
    {
        $this->iterable = iterAux\nth($this->iterable, $n, $counter);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function onNth(int $n, callable $block, ?int &$counter = null): self
    {
        $this->iterable = iterAux\onNth($this->iterable, $n, $block, $counter);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function onNthAndLast(int $n, callable $block, ?int &$counter = null): self
    {
        $this->iterable = iterAux\onNthAndLast($this->iterable, $n, $block, $counter);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @param callable $block - function(int $millisFromStart, int $iteration, $currentValue, $currentKey)
     */
    public function onNthMillis(int $millis, callable $block): self
    {
        $this->iterable = iterAux\onNthMillis($this->iterable, $millis, $block);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @param callable $block - function(int $millisFromStart, int $iteration, $currentValue, $currentKey, bool $isLast)
     */
    public function onNthMillisAndLast(int $millis, callable $block): self
    {
        $this->iterable = iterAux\onNthMillisAndLast($this->iterable, $millis, $block);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function append($value, $key = 0): self
    {
        $this->iterable = iterAux\append($this->iterable, $value, $key);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function appendNotNull($value, $key = 0): self
    {
        $this->iterable = iterAux\appendNotNull($this->iterable, $value, $key);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function prepend($value, $key = 0): self
    {
        $this->iterable = iterAux\prepend($this->iterable, $value, $key);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function prependNotNull($value, $key = 0): self
    {
        $this->iterable = iterAux\prependNotNull($this->iterable, $value, $key);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function unshift($value, $key = 0): self
    {
        return $this->prepend($value, $key);
    }

    public function isEmpty(): bool
    {
        return \iter\isEmpty($this->iterable);
    }

    public function isNotEmpty(): bool
    {
        return !\iter\isEmpty($this->iterable);
    }

    public function orElse(iterable $elseIterable): self
    {
        $this->iterable = iterAux\orElse($this->iterable, $elseIterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function recurse(callable $function)
    {
        return \iter\recurse($function, $this->iterable);
    }

    /**
     * @return list<V>
     */
    public function toArray(): array
    {
        return iterAux\toArray($this->iterable);
    }

    public function toArrayWithCapacity(int $capacity): array
    {
        return \is_array($this->iterable) ? $this->iterable : iterAux\toArrayWithCapacity($this->iterable, $capacity);
    }

    public function toObject(?string $class = null): object
    {
        return iterAux\toObject($this->iterable, $class);
    }

    public function nop(): array
    {
        return [];
    }

    /**
     * @return (K is int ? array<int, V> : (K is float ? array<int, V> : (K is bool ? array<int, V> : (K is null ? array<"", V> : (K is numeric-string ? array<int, V> : (K is scalar ? array<array-key, V> : never))))))
     */
    public function toArrayWithKeys(): array
    {
        return iterAux\toArrayWithKeys($this->iterable);
    }

    public function toCollection(): ArrayCollectionExtended
    {
        return new ArrayCollectionExtended(iterAux\toArray($this->iterable));
    }

    public function toCollectionWithKeys(): ArrayCollectionExtended
    {
        return new ArrayCollectionExtended(iterAux\toArrayWithKeys($this->iterable));
    }

    public function toLazyCollection(): LazyArrayCollection
    {
        return new LazyArrayCollection($this->iterable, false);
    }

    public function toLazyCollectionWithKeys(): LazyArrayCollection
    {
        return new LazyArrayCollection($this->iterable, true);
    }

    public function groupAdjacentBy(callable $comparator): self
    {
        $this->iterable = iterAux\groupAdjacentBy($this->iterable, $comparator);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function groupAdjacentByLazy(callable $comparator): self
    {
        $this->iterable = iterFluent\mapToIter(iterAux\groupAdjacentByLazy($this->iterable, $comparator));
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function groupAdjacentByWithKeys(callable $comparator): self
    {
        $this->iterable = iterAux\groupAdjacentByWithKeys($this->iterable, $comparator);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function groupAdjacentByKey(?callable $comparator = null): self
    {
        $this->iterable = iterAux\groupAdjacentByKey($this->iterable, $comparator);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function cycle($num = INF): self
    {
        $this->iterable = iterAux\cycle($this->iterable, $num);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function pick(iterable ...$iterables): self
    {
        $this->iterable = iterAux\uniformPick($this->iterable, ...$iterables);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function memoize(): self
    {
        $this->iterable = iterAux\memoize($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function groupAdjacentByField(string $field): self
    {
        $this->iterable = iterAux\groupAdjacentByField($field, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function groupAdjacentByFieldWithKeys(string $field): self
    {
        $this->iterable = iterAux\groupAdjacentByFieldWithKeys($field, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @deprecated - use groupAdjacentBy
     */
    public function groupAdjacentByColumn($column): self
    {
        $this->iterable = iterAux\groupAdjacentByColumn($column, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function groupAdjacentByColumnWithKeys($column): self
    {
        $this->iterable = iterAux\groupAdjacentByColumnWithKeys($column, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function groupAdjacentByPropertyPath($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): self
    {
        $this->iterable = iterAux\groupAdjacentByPropertyPath($this->iterable, $propertyPath, $propertyAccessor);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function groupAdjacentByPropertyPathWithKeys($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): self
    {
        $this->iterable = iterAux\groupAdjacentByPropertyPathWithKeys($this->iterable, $propertyPath, $propertyAccessor);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function reverse(): self
    {
        $this->iterable = iterAux\reverse($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function reverseList(): self
    {
        $this->iterable = iterAux\reverseList($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByField(string $field, $filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterByField($this->iterable, $field, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByColumn($column, $filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterByColumn($this->iterable, $column, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterPairByFirst($filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterByColumn($this->iterable, 0, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterPairBySecond($filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterByColumn($this->iterable, 1, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterPairNotByFirst($filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterNotByColumn($this->iterable, 0, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterPairNotBySecond($filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterNotByColumn($this->iterable, 1, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterBySize(int $size): self
    {
        $this->iterable = iterAux\filterBySize($this->iterable, $size);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotBySize(int $size): self
    {
        $this->iterable = iterAux\filterNotBySize($this->iterable, $size);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterBySizeGt(int $size): self
    {
        $this->iterable = iterAux\filterBySizeGt($this->iterable, $size);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterBySizeGte(int $size): self
    {
        return $this->filterBySizeGt($size - 1);
    }

    public function filterBySizeLt(int $size): self
    {
        $this->iterable = iterAux\filterBySizeLt($this->iterable, $size);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterBySizeLte(int $size): self
    {
        return $this->filterBySizeLt($size + 1);
    }

    public function filterByContainsArray(array $needle): self
    {
        $this->iterable = iterAux\filterByContainsArray($this->iterable, $needle);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByContainsArray(array $needle): self
    {
        $this->iterable = iterAux\filterNotByContainsArray($this->iterable, $needle);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByInArray(array $haystack, bool $strict = false): self
    {
        $this->iterable = iterAux\filterByInArray($this->iterable, $haystack, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByColumnInArray($column, array $haystack, bool $strict = false): self
    {
        $this->iterable = iterAux\filterByColumnInArray($this->iterable, $column, $haystack, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByColumnInArray($column, array $haystack, bool $strict = false): self
    {
        $this->iterable = iterAux\filterNotByColumnInArray($this->iterable, $column, $haystack, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByKeyInArray(array $haystack, bool $strict = false): self
    {
        $this->iterable = iterAux\filterByKeyInArray($this->iterable, $haystack, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByInArray(array $haystack, bool $strict = false): self
    {
        $this->iterable = iterAux\filterNotByInArray($this->iterable, $haystack, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByKeyInArray(array $haystack, bool $strict = false): self
    {
        $this->iterable = iterAux\filterNotByKeyInArray($this->iterable, $haystack, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByInMap(array $haystack): self
    {
        $this->iterable = iterAux\filterByInMap($this->iterable, $haystack);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByKeyInMap(array $haystack): self
    {
        $this->iterable = iterAux\filterByKeyInMap($this->iterable, $haystack);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByInMap(array $haystack): self
    {
        $this->iterable = iterAux\filterNotByInMap($this->iterable, $haystack);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByKeyInMap(array $haystack): self
    {
        $this->iterable = iterAux\filterNotByKeyInMap($this->iterable, $haystack);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function padTo(int $padSize, $elem, $key = null): self
    {
        if (\func_num_args() === 2) {
            $this->iterable = iterAux\padTo($this->iterable, $padSize, $elem);
        } else {
            $this->iterable = iterAux\padTo($this->iterable, $padSize, $elem, $key);
        }

        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function column($column): self
    {
        $this->iterable = iterAux\column($column, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function field(string $field): self
    {
        $this->iterable = iterAux\field($field, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function propertyPath($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): self
    {
        $this->iterable = iterAux\propertyPath($this->iterable, $propertyPath, $propertyAccessor);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByValue($filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterByValue($this->iterable, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @return static<K, V>
     */
    public function filterNot(callable $predicate): self
    {
        $this->iterable = iterAux\filterNot($predicate, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotNull(): self
    {
        $this->iterable = iterAux\filterNotNull($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByColumn($column, $filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterNotByColumn($this->iterable, $column, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByField(string $field, $filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterNotByField($this->iterable, $field, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterHasColumn($column): self
    {
        $this->iterable = iterAux\filterHasColumn($this->iterable, $column);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterHasNotColumn($column): self
    {
        $this->iterable = iterAux\filterHasNotColumn($this->iterable, $column);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByValue($filterValue, bool $strict = false): self
    {
        $this->iterable = iterAux\filterNotByValue($this->iterable, $filterValue, $strict);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByMatch(string $pattern, int $flags = 0, int $offset = 0): self
    {
        $this->iterable = iterAux\filterByMatch($this->iterable, $pattern, $flags, $offset);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByMatch(string $pattern, int $flags = 0, int $offset = 0): self
    {
        $this->iterable = iterAux\filterNotByMatch($this->iterable, $pattern, $flags, $offset);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByKeyMatch(string $pattern, int $flags = 0, int $offset = 0): self
    {
        $this->iterable = iterAux\filterByKeyMatch($this->iterable, $pattern, $flags, $offset);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByKeyMatch(string $pattern, int $flags = 0, int $offset = 0): self
    {
        $this->iterable = iterAux\filterNotByKeyMatch($this->iterable, $pattern, $flags, $offset);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNull(): self
    {
        $this->iterable = iterAux\filterNull($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterEmpty(): self
    {
        $this->iterable = iterAux\filterEmpty($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterHasEmptyColumnString($column): self
    {
        $this->iterable = iterAux\filterHasEmptyColumnString($this->iterable, $column);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotEmpty(): self
    {
        $this->iterable = iterAux\filterNotEmpty($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterHasNotEmptyColumnString($column): self
    {
        $this->iterable = iterAux\filterHasNotEmptyColumnString($this->iterable, $column);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotEmptyString(): self
    {
        $this->iterable = iterAux\filterNotEmptyString($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterIsInstance($class): self
    {
        $this->iterable = iterAux\filterIsInstance($this->iterable, $class);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterIsNotInstance($class): self
    {
        $this->iterable = iterAux\filterIsNotInstance($this->iterable, $class);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function onEach(callable $function): self
    {
        $this->iterable = iterAux\onEach($function, $this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterNotByPropertyPath($propertyPath, $filterValue, bool $strict = false, ?PropertyAccessorInterface $propertyAccessor = null): self
    {
        $this->iterable = iterAux\filterNotByPropertyPath($this->iterable, $propertyPath, $filterValue, $strict, $propertyAccessor);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterByPropertyPath($propertyPath, $filterValue, bool $strict = false, ?PropertyAccessorInterface $propertyAccessor = null): self
    {
        $this->iterable = iterAux\filterByPropertyPath($this->iterable, $propertyPath, $filterValue, $strict, $propertyAccessor);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapByTrim(string $charlist = " \t\n\r\0\x0B"): self
    {
        $this->iterable = iterAux\mapByTrim($this->iterable, $charlist);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapByApply(...$args): self
    {
        $this->iterable = iterAux\mapByApply($this->iterable, ...$args);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapToInt(): self
    {
        $this->iterable = iterAux\mapToInt($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapToString(): self
    {
        $this->iterable = iterAux\mapToString($this->iterable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapToClosure(?object $bindObject = null): self
    {
        $this->iterable = iterAux\mapToClosure($this->iterable, $bindObject);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapToMatches(string $pattern, array $mappedMatches, int $flags = 0, int $offset = 0): self
    {
        $this->iterable = iterAux\mapToMatches($this->iterable, $pattern, $mappedMatches, $flags, $offset);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function format(string $format): self
    {
        $this->iterable = iterAux\format($this->iterable, $format);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function flatMapToMatches(string $pattern, array $mappedMatches, int $flags = 0, int $offset = 0): self
    {
        $this->iterable = iterAux\flatMapToMatches($this->iterable, $pattern, $mappedMatches, $flags, $offset);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapToMatch(string $pattern, int $matchNumber = 1, int $flags = 0, int $offset = 0): self
    {
        $this->iterable = iterAux\mapToMatch($this->iterable, $pattern, $matchNumber, $flags, $offset);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function flatMapToMatch(string $pattern, int $matchNumber = 1, int $flags = 0, int $offset = 0): self
    {
        $this->iterable = iterAux\flatMapToMatch($this->iterable, $pattern, $matchNumber, $flags, $offset);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function uniqueAdjacent(?callable $comparator = null): self
    {
        $this->iterable = iterAux\uniqueAdjacent($this->iterable, $comparator);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function uniqByColumn($column): self
    {
        $this->iterable = iterAux\uniqueAdjacentByColumn($this->iterable, $column);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function uniqByField(string $field): self
    {
        $this->iterable = iterAux\uniqueAdjacentByField($this->iterable, $field);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function uniqByPropertyPath($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): self
    {
        $this->iterable = iterAux\uniqueAdjacentByPropertyPath($this->iterable, $propertyPath, $propertyAccessor);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterIsSetColumn($column): self
    {
        $this->iterable = iterAux\filterIsSetColumn($this->iterable, $column);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function filterIsNotSetColumn($column): self
    {
        $this->iterable = iterAux\filterIsNotSetColumn($this->iterable, $column);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function reindexByColumn($column): self
    {
        $this->iterable = iterAux\reindexByColumn($this->iterable, $column);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function reindexByField(string $field): self
    {
        $this->iterable = iterAux\reindexByField($this->iterable, $field);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function reindexByPropertyPath($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): self
    {
        $this->iterable = iterAux\reindexByPropertyPath($this->iterable, $propertyPath, $propertyAccessor);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @return array|float|int|null
     */
    public function sum()
    {
        return iterAux\sum($this->iterable);
    }

    /**
     * @return array|float|int|null
     */
    public function average()
    {
        return iterAux\average($this->iterable);
    }

    /**
     * @return float|int|null
     */
    public function min(?callable $comparator = null)
    {
        return iterAux\min($this->iterable, $comparator);
    }

    /**
     * @template C of (callable|null)
     * @param C $comparator
     * @return ($comparator is null ? (float|int) : V)|null
     */
    public function max(?callable $comparator = null)
    {
        return iterAux\max($this->iterable, $comparator);
    }

    public function bounds(?callable $comparator = null): array
    {
        return iterAux\bounds($this->iterable, $comparator);
    }

    public function mapToLower(?string $encoding = null): self
    {
        $this->iterable = iterAux\mapToLower($this->iterable, $encoding);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function mapToUpper(?string $encoding = null): self
    {
        $this->iterable = iterAux\mapToUpper($this->iterable, $encoding);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @return self[]
     */
    public function partition(callable $predicate): array
    {
        [$part1, $part2] = iterAux\partition($this->iterable, $predicate);

        return [new self($part1), new self($part2)];
    }

    /**
     * @return self[]
     */
    public function spanLazy(callable $predicate): array
    {
        [$part1, $part2] = iterAux\spanLazy($this->iterable, $predicate);

        return [new self($part1), new self($part2)];
    }

    public function span(callable $predicate): self
    {
        $this->iterable = iterAux\span($this->iterable, $predicate);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function spanWithKeys(callable $predicate): self
    {
        $this->iterable = iterAux\spanWithKeys($this->iterable, $predicate);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @return self[]
     */
    public function splitLazyAt(int ...$indexes): array
    {
        $iters = iterAux\splitLazyAt($this->iterable, ...$indexes);
        $result = [];

        foreach ($iters as $iter) {
            $result[] = new self($iter);
        }

        return $result;
    }

    public function splitAt(int ...$indexes): self
    {
        $this->iterable = iterAux\splitAt($this->iterable, ...$indexes);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function splitAtWithKeys(int ...$indexes): self
    {
        $this->iterable = iterAux\splitAtWithKeys($this->iterable, ...$indexes);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function indexOfValue($searchValue, bool $strict = false)
    {
        return iterAux\indexOfValue($this->iterable, $searchValue, $strict);
    }

    /**
     * @return mixed|null
     */
    public function indexOf($searchValue, bool $strict = false)
    {
        return iterAux\indexOfValue($this->iterable, $searchValue, $strict);
    }

    /**
     * @return mixed|null
     */
    public function lastIndexOfValue($searchValue, bool $strict = false)
    {
        return iterAux\lastIndexOfValue($this->iterable, $searchValue, $strict);
    }

    /**
     * @return mixed|null
     */
    public function lastIndexOf($searchValue, bool $strict = false)
    {
        return iterAux\lastIndexOfValue($this->iterable, $searchValue, $strict);
    }

    /**
     * @return mixed|null
     */
    public function elementAtIndex($filterIndex, bool $strict = false)
    {
        return iterAux\elementAtIndex($this->iterable, $filterIndex, $strict);
    }

    /**
     * @return mixed|null
     */
    public function lastElementAtIndex($filterIndex, bool $strict = false)
    {
        return iterAux\lastElementAtIndex($this->iterable, $filterIndex, $strict);
    }

    /**
     * @return V|null
     */
    public function first()
    {
        return iterAux\first($this->iterable);
    }

    /**
     * @return mixed|null
     */
    public function head()
    {
        return iterAux\first($this->iterable);
    }

    /**
     * @return K|null
     */
    public function firstIndex()
    {
        return iterAux\firstIndex($this->iterable);
    }

    /**
     * @return mixed|null
     */
    public function last()
    {
        return iterAux\last($this->iterable);
    }

    /**
     * @return mixed|null
     */
    public function lastIndex()
    {
        return iterAux\lastIndex($this->iterable);
    }

    public function offsetExists($offset)
    {
        $this->throwUnimplemented(__METHOD__);
    }

    public function offsetGet($column): self
    {
        return $this->column($column);
    }

    public function offsetSet($offset, $value)
    {
        $this->throwUnimplemented(__METHOD__);
    }

    public function offsetUnset($offset)
    {
        $this->throwUnimplemented(__METHOD__);
    }

    public function clone(): self
    {
        return clone $this;
    }

    public function jsonSerialize()
    {
        return $this->toArrayWithKeys();
    }

    /**
     * @return false|mixed|string
     */
    public function toJSON(int $jsonEncodeOptions = 0, int $jsonEncodeDepth = 512)
    {
        return \json_encode($this->toArray(), $jsonEncodeOptions, $jsonEncodeDepth);
    }

    /**
     * @return false|mixed|string
     */
    public function toJSONWithKeys(int $jsonEncodeOptions = 0, int $jsonEncodeDepth = 512)
    {
        return \json_encode($this->toArrayWithKeys(), $jsonEncodeOptions, $jsonEncodeDepth);
    }

    public function serialize()
    {
        return \serialize(
            \iter\toArray(
                \iter\toPairs($this->iterable)
            )
        );
    }

    public function unserialize($serialized)
    {
        $this->iterable = \iter\fromPairs(\unserialize($serialized));
        $this->innerIteratorInitialized = true;
    }

    public function matchingLazy(Criteria $criteria): self
    {
        $this->iterable = iterAux\matchingLazy($this->iterable, $criteria);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function matching(Criteria $criteria): self
    {
        $this->iterable = iterAux\matching($this->iterable, $criteria);
        $this->innerIteratorInitialized = false;

        return $this;
    }

    /**
     * @return static<int, V>
     */
    public function collect(): self
    {
        $this->iterable = iterAux\toArray($this->iterable);
        $this->innerIteratorInitialized = false;

        return $this;
    }

    public function collectWithCapacity(int $capacity)
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\toArrayWithCapacity($this->iterable, $capacity);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    /**
     * @return (K is int ? static<int, V> : (K is float ? static<int, V> : (K is bool ? static<int, V> : (K is null ? static<"", V> : (K is numeric-string ? static<int, V> : (K is scalar ? static<array-key, V> : never))))))
     */
    public function collectWithKeys(): self
    {
        $this->iterable = iterAux\toArrayWithKeys($this->iterable);
        $this->innerIteratorInitialized = false;

        return $this;
    }

    /**
     * @return (K is int ? static<int, list<V>> : (K is float ? static<int, list<V>> : (K is bool ? static<int, list<V>> : (K is null ? static<"", list<V>> : (K is numeric-string ? static<int, list<V>> : (K is scalar ? static<array-key, list<V>> : never))))))
     */
    public function collapseByKey(): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\collapseByKey($this->iterable);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function stat(): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\stat($this->iterable);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function statByKey(): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\statByKey($this->iterable);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function uasort(callable $comparator): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\uasortLazy($this->iterable, $comparator);
            $this->innerIteratorInitialized = true;
        } else {
            \uasort($this->iterable, $comparator);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function sort(?int $sortFlags = null): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\sortLazy($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = true;
        } else {
            \sort($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    /**
     * @param callable(V, V): int $comparator
     * @return (K is int ? static<int, V> : (K is float ? static<int, V> : (K is bool ? static<int, V> : (K is null ? static<"", V> : (K is numeric-string ? static<int, V> : (K is scalar ? static<array-key, V> : never))))))
     */
    public function usort(callable $comparator): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\usortLazy($this->iterable, $comparator);
            $this->innerIteratorInitialized = true;
        } else {
            \usort($this->iterable, $comparator);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function rsort(?int $sortFlags = null): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\rsortLazy($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = true;
        } else {
            \rsort($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function asort(?int $sortFlags = null): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\asortLazy($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = true;
        } else {
            \asort($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function arsort(?int $sortFlags = null): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\arsortLazy($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = true;
        } else {
            \arsort($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function ksort(?int $sortFlags = null): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\ksortLazy($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = true;
        } else {
            \ksort($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function krsort(?int $sortFlags = null): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\krsortLazy($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = true;
        } else {
            \krsort($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function unique(int $sortFlags = \SORT_STRING): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\uniqueLazy($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = true;
        } else {
            $this->iterable = \array_unique($this->iterable, $sortFlags);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function shuffle(): self
    {
        if (!\is_array($this->iterable)) {
            $this->iterable = iterAux\shuffleLazy($this->iterable);
            $this->innerIteratorInitialized = true;
        } else {
            \shuffle($this->iterable);
            $this->innerIteratorInitialized = false;
        }

        return $this;
    }

    public function timeout(int $milliseconds, ?\Throwable $throwable = null): self
    {
        $this->iterable = iterAux\timeout($this->iterable, $milliseconds, $throwable);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function throttle(string $key, \ThrottlerInterface $throttler, ?callable $delayListener = null): self
    {
        $this->iterable = iterAux\throttle($this->iterable, $key, $throttler, $delayListener);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function catch(callable $handler): self
    {
        $this->iterable = iterAux\autoCatchException($this->iterable, $handler);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function recover(callable $handler): self
    {
        $this->iterable = iterAux\autoRecoverException($this->iterable, $handler);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function finally(callable $handler): self
    {
        $this->iterable = iterAux\finalBlock($this->iterable, $handler);
        $this->innerIteratorInitialized = true;

        return $this;
    }

    public function decons(): array
    {
        [$head, $tail] = iterAux\decons($this->iterable);

        return [$head, new self($tail)];
    }

    protected function initialize()
    {
        $this->iterable = $this->iterable instanceof self ?
            $this->iterable->iterable :
            iterAux\toIterInternal($this->iterable);

        $this->innerIteratorInitialized = true;
    }

    protected function throwUnimplemented(string $method)
    {
        throw new \BadMethodCallException("Method '$method' intentionally unimplemented!");
    }
}
