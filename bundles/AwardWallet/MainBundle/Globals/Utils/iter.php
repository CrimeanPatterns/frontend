<?php

namespace AwardWallet\MainBundle\Globals\Utils\iter {
    use AwardWallet\MainBundle\Globals\PropertyAccess\SafeCallPropertyAccessor;
    use AwardWallet\MainBundle\Globals\Utils\ArrayCollectionExtended;
    use AwardWallet\MainBundle\Globals\Utils\ArrayContainsComparator;
    use AwardWallet\MainBundle\Globals\Utils\JsonParser\JsonExtractor;
    use AwardWallet\MainBundle\Globals\Utils\JsonParser\JsonStreamParser;
    use AwardWallet\MainBundle\Globals\Utils\JsonParser\ParsingException;
    use AwardWallet\MainBundle\Globals\Utils\LazyArrayCollection;
    use AwardWallet\MainBundle\Globals\Utils\MemoizableIterator;
    use Doctrine\Common\Collections\Criteria;
    use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;
    use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

    use function AwardWallet\MainBundle\Globals\Utils\f\orderBy;

    function scanLeft(iterable $iterable, $neutral, callable $operation)
    {
        yield $neutral;

        foreach ($iterable as $value) {
            $neutral = $operation($neutral, $value);

            yield $neutral;
        }
    }

    function scanLeftIndexed(iterable $iterable, $neutral, callable $operation)
    {
        yield $neutral;

        foreach ($iterable as $key => $value) {
            $neutral = $operation($neutral, $value, $key);

            yield $neutral;
        }
    }

    function groupAdjacentBy(iterable $iterable, callable $comparator): \Iterator
    {
        $buffer = [];
        $first = true;
        $lastValue = null;

        foreach ($iterable as $value) {
            if (!$first && ($comparator($value, $lastValue) !== 0)) {
                yield $buffer;

                $buffer = [];
            }

            $first = false;
            $buffer[] = $lastValue = $value;
        }

        if (!$first) {
            yield $buffer;
        }
    }

    /**
     * @return \Iterator<\Iterator>
     */
    function groupAdjacentByLazy(iterable $iterable, callable $comparator): \Iterator
    {
        $iterator = toIterInternal($iterable);
        $iterator->rewind();
        $subIteratorProducer = function () use (&$iterator, &$comparator) {
            $lastValue = null;
            $first = true;

            while ($iterator->valid()) {
                $value = $iterator->current();

                if (!$first && ($comparator($lastValue, $value) !== 0)) {
                    return;
                }

                yield $iterator->key() => $value;

                $first = false;
                $lastValue = $value;
                $iterator->next();
            }
        };

        while ($iterator->valid()) {
            $subIterator = $subIteratorProducer();

            yield $subIterator;

            while ($subIterator->valid()) {
                $subIterator->next();
            }
        }
    }

    function chunkLazy(iterable $iterable, int $chunkSize): \Iterator
    {
        if ($chunkSize <= 0) {
            throw new \InvalidArgumentException('Chunk size must be positive');
        }

        $currentChunkSize = 0;

        return groupAdjacentByLazy($iterable, function () use ($chunkSize, &$currentChunkSize) {
            $currentChunkSize++;

            if ($currentChunkSize === $chunkSize) {
                $currentChunkSize = 0;

                return 1;
            }

            return 0;
        });
    }

    function slidingPartial(iterable $iterable, int $windowSize, int $stepSize = 1): \Iterator
    {
        if ($stepSize <= 0) {
            throw new \InvalidArgumentException('Step size must be positive');
        }

        if ($windowSize <= 0) {
            throw new \InvalidArgumentException('Window size must be positive');
        }

        // common [last, current] window
        if ((2 === $windowSize) && (1 === $stepSize)) {
            $first = false;
            $second = false;
            $last = null;

            foreach ($iterable as $value) {
                if (!$first) {
                    $last = $value;
                    $first = true;

                    continue;
                }

                yield [$last, $value];

                $second = true;
                $last = $value;
            }

            if ($first && !$second) {
                yield [$last];
            }
        } else {
            $window = [];
            $currentWindowCount = 0;

            foreach ($iterable as $value) {
                $currentWindowCount++;

                if ($currentWindowCount > 0) {
                    $window[] = $value;
                }

                if ($currentWindowCount === $windowSize) {
                    yield $window;

                    $window = \array_slice($window, $stepSize);
                    $currentWindowCount -= $stepSize;
                }
            }

            if ($window) {
                yield $window;
            }
        }
    }

    function none(callable $predicate, iterable $iterable): bool
    {
        foreach ($iterable as $value) {
            if ($predicate($value)) {
                return false;
            }
        }

        return true;
    }

    function slidingPartialWithKeys(iterable $iterable, int $windowSize, int $stepSize = 1): \Iterator
    {
        if ($stepSize <= 0) {
            throw new \InvalidArgumentException('Step size must be positive');
        }

        if ($windowSize <= 0) {
            throw new \InvalidArgumentException('Window size must be positive');
        }

        $window = [];
        $currentWindowCount = 0;

        foreach ($iterable as $key => $value) {
            $currentWindowCount++;

            if ($currentWindowCount > 0) {
                $window[$key] = $value;
            }

            if ($currentWindowCount === $windowSize) {
                yield $window;

                $window = \array_slice($window, $stepSize, null, true);
                $currentWindowCount -= $stepSize;
            }
        }

        if ($window) {
            yield $window;
        }
    }

    function sliding(iterable $iterable, int $windowSize, int $stepSize = 1): \Iterator
    {
        if ($stepSize <= 0) {
            throw new \InvalidArgumentException('Step size must be positive');
        }

        if ($windowSize <= 0) {
            throw new \InvalidArgumentException('Window size must be positive');
        }

        // common [last, current] window
        if ((2 === $windowSize) && (1 === $stepSize)) {
            $first = false;
            $lastValue = null;

            foreach ($iterable as $value) {
                if (!$first) {
                    $lastValue = $value;
                    $first = true;

                    continue;
                }

                yield [$lastValue, $value];

                $lastValue = $value;
            }
        } else {
            $window = [];
            $currentWindowCount = 0;

            foreach ($iterable as $value) {
                $currentWindowCount++;

                if ($currentWindowCount > 0) {
                    $window[] = $value;
                }

                if ($currentWindowCount === $windowSize) {
                    yield $window;

                    $window = \array_slice($window, $stepSize);
                    $currentWindowCount -= $stepSize;
                }
            }
        }
    }

    function slidingWithKeys(iterable $iterable, int $windowSize, int $stepSize = 1): \Iterator
    {
        if ($stepSize <= 0) {
            throw new \InvalidArgumentException('Step size must be positive');
        }

        if ($windowSize <= 0) {
            throw new \InvalidArgumentException('Window size must be positive');
        }

        $window = [];
        $currentWindowCount = 0;

        foreach ($iterable as $key => $value) {
            $currentWindowCount++;

            if ($currentWindowCount > 0) {
                $window[$key] = $value;
            }

            if ($currentWindowCount === $windowSize) {
                yield $window;

                $window = \array_slice($window, $stepSize, null, true);
                $currentWindowCount -= $stepSize;
            }
        }
    }

    function groupAdjacentByKey(iterable $iterable, ?callable $comparator = null)
    {
        $buffer = [];
        $first = true;
        $lastKey = null;

        if ($comparator) {
            foreach ($iterable as $key => $value) {
                if (!$first && ($comparator($key, $lastKey) !== 0)) {
                    yield $lastKey => $buffer;

                    $buffer = [];
                }

                $first = false;
                $buffer[] = $value;
                $lastKey = $key;
            }
        } else {
            foreach ($iterable as $key => $value) {
                if (!$first && ($key !== $lastKey)) {
                    yield $lastKey => $buffer;

                    $buffer = [];
                }

                $first = false;
                $buffer[] = $value;
                $lastKey = $key;
            }
        }

        if (!$first) {
            yield $lastKey => $buffer;
        }
    }

    function groupAdjacentByWithKeys(iterable $iterable, callable $comparator): \Iterator
    {
        $buffer = [];
        $first = true;
        $lastValue = null;

        foreach ($iterable as $key => $value) {
            if (!$first && ($comparator($value, $lastValue) !== 0)) {
                yield $buffer;

                $buffer = [];
            }

            $first = false;
            $buffer[$key] = $lastValue = $value;
        }

        if (!$first) {
            yield $buffer;
        }
    }

    function groupAdjacentByColumnWithKeys($column, iterable $iterable): \Iterator
    {
        return groupAdjacentByWithKeys($iterable, function ($array, $lastArray) use ($column) {
            return $array[$column] <=> $lastArray[$column];
        });
    }

    function groupAdjacentByColumn($column, iterable $iterable): \Iterator
    {
        return groupAdjacentBy($iterable, function ($array, $lastArray) use ($column) {
            return $array[$column] <=> $lastArray[$column];
        });
    }

    function groupAdjacentByField(string $field, iterable $iterable): \Iterator
    {
        return groupAdjacentBy($iterable, function ($object, $lastObject) use ($field) {
            return $object->{$field} <=> $lastObject->{$field};
        });
    }

    function groupAdjacentByFieldWithKeys(string $field, iterable $iterable): \Iterator
    {
        return groupAdjacentByWithKeys($iterable, function ($object, $lastObject) use ($field) {
            return $object->{$field} <=> $lastObject->{$field};
        });
    }

    function groupAdjacentByPropertyPath(iterable $iterable, $propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): \Iterator
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return groupAdjacentBy($iterable, function ($value, $lastValue) use ($propertyPath, $propertyAccessor) {
            return $propertyAccessor->getValue($value, $propertyPath) <=> $propertyAccessor->getValue($lastValue, $propertyPath);
        });
    }

    function groupAdjacentByPropertyPathWithKeys(iterable $iterable, $propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): \Iterator
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return groupAdjacentByWithKeys($iterable, function ($value, $lastValue) use ($propertyPath, $propertyAccessor) {
            return $propertyAccessor->getValue($value, $propertyPath) <=> $propertyAccessor->getValue($lastValue, $propertyPath);
        });
    }

    function column($column, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $array) {
            yield $key => $array[$column];
        }
    }

    function field(string $field, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $object) {
            yield $key => $object->{$field};
        }
    }

    function propertyPath(iterable $iterable, $propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): \Iterator
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        foreach ($iterable as $key => $value) {
            yield $key => $propertyAccessor->getValue($value, $propertyPath);
        }
    }

    function matchingLazy(iterable $iterable, Criteria $criteria): \Iterator
    {
        $iter = toIterInternal($iterable);
        $expr = $criteria->getWhereExpression();

        if ($expr) {
            $visitor = new ClosureExpressionVisitor();
            $filter = $visitor->dispatch($expr);
            $iter = \iter\filter($filter, $iter);
        }

        if ($orderings = $criteria->getOrderings()) {
            $next = orderBy($orderings);
            $iter = uasortLazy($iter, $next);
        }

        $offset = $criteria->getFirstResult();
        $length = $criteria->getMaxResults();

        if ($offset || $length) {
            $iter = slice($iter, (int) $offset, $length);
        }

        yield from $iter;
    }

    function matching(iterable $iterable, Criteria $criteria): array
    {
        $expr = $criteria->getWhereExpression();

        if ($expr) {
            $visitor = new ClosureExpressionVisitor();
            $filter = $visitor->dispatch($expr);

            if (!\is_array($iterable)) {
                $iterable = toArrayWithKeys($iterable);
            }

            $iterable = \iter\filter($filter, $iterable);
        }

        if ($orderings = $criteria->getOrderings()) {
            $next = orderBy($orderings);

            if (!\is_array($iterable)) {
                $iterable = toArrayWithKeys($iterable);
            }

            \uasort($iterable, $next);
        }

        $offset = $criteria->getFirstResult();
        $length = $criteria->getMaxResults();

        if ($offset || $length) {
            if (!\is_array($iterable)) {
                $iterable = toArrayWithKeys($iterable);
            }

            $iterable = slice($iterable, (int) $offset, $length);
        }

        if (!\is_array($iterable)) {
            $iterable = toArrayWithKeys($iterable);
        }

        return $iterable;
    }

    function filterNot(callable $predicate, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!$predicate($value)) {
                yield $key => $value;
            }
        }
    }

    function filterNull(iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\is_null($value)) {
                yield $key => $value;
            }
        }
    }

    function filterNotNull(iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!\is_null($value)) {
                yield $key => $value;
            }
        }
    }

    function filterByColumn(iterable $iterable, $column, $filterValue, bool $strict = false): \Iterator
    {
        if ($strict) {
            foreach ($iterable as $key => $array) {
                if ($array[$column] === $filterValue) {
                    yield $key => $array;
                }
            }
        } else {
            foreach ($iterable as $key => $array) {
                if ($array[$column] == $filterValue) {
                    yield $key => $array;
                }
            }
        }
    }

    function filterByField(iterable $iterable, string $field, $filterValue, bool $strict = false): \Iterator
    {
        if ($strict) {
            foreach ($iterable as $key => $object) {
                if ($object->{$field} === $filterValue) {
                    yield $key => $object;
                }
            }
        } else {
            foreach ($iterable as $key => $object) {
                if ($object->{$field} == $filterValue) {
                    yield $key => $object;
                }
            }
        }
    }

    function filterByPropertyPath(iterable $iterable, $propertyPath, $filterValue, bool $strict = false, ?PropertyAccessorInterface $propertyAccessor = null): \Iterator
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        if ($strict) {
            foreach ($iterable as $key => $value) {
                $actualValue = $propertyAccessor->getValue($value, $propertyPath);

                if ($actualValue === $filterValue) {
                    yield $key => $value;
                }
            }
        } else {
            foreach ($iterable as $key => $value) {
                $actualValue = $propertyAccessor->getValue($value, $propertyPath);

                if ($actualValue == $filterValue) {
                    yield $key => $value;
                }
            }
        }
    }

    function filterNotByPropertyPath(iterable $iterable, $propertyPath, $filterValue, bool $strict = false, ?PropertyAccessorInterface $propertyAccessor = null): \Iterator
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        if ($strict) {
            foreach ($iterable as $key => $value) {
                $actualValue = $propertyAccessor->getValue($value, $propertyPath);

                if ($actualValue !== $filterValue) {
                    yield $key => $value;
                }
            }
        } else {
            foreach ($iterable as $key => $value) {
                $actualValue = $propertyAccessor->getValue($value, $propertyPath);

                if ($actualValue != $filterValue) {
                    yield $key => $value;
                }
            }
        }
    }

    function filterNotByColumn(iterable $iterable, $column, $filterValue, bool $strict = false): \Iterator
    {
        if ($strict) {
            foreach ($iterable as $key => $array) {
                if ($array[$column] !== $filterValue) {
                    yield $key => $array;
                }
            }
        } else {
            foreach ($iterable as $key => $array) {
                if ($array[$column] != $filterValue) {
                    yield $key => $array;
                }
            }
        }
    }

    function filterNotByField(iterable $iterable, string $field, $filterValue, bool $strict = false): \Iterator
    {
        if ($strict) {
            foreach ($iterable as $key => $object) {
                if ($object->{$field} !== $filterValue) {
                    yield $key => $object;
                }
            }
        } else {
            foreach ($iterable as $key => $object) {
                if ($object->{$field} != $filterValue) {
                    yield $key => $object;
                }
            }
        }
    }

    function filterHasColumn(iterable $iterable, $column): \Iterator
    {
        foreach ($iterable as $key => $array) {
            if (\array_key_exists($column, $array)) {
                yield $key => $array;
            }
        }
    }

    function filterIsSetColumn(iterable $iterable, $column): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (isset($value[$column])) {
                yield $key => $value;
            }
        }
    }

    function filterIsNotSetColumn(iterable $iterable, $column): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!isset($value[$column])) {
                yield $key => $value;
            }
        }
    }

    function filterHasNotColumn(iterable $iterable, $column): \Iterator
    {
        foreach ($iterable as $key => $array) {
            if (!\array_key_exists($column, $array)) {
                yield $key => $array;
            }
        }
    }

    function filterByValue(iterable $iterable, $filterValue, bool $strict = false): \Iterator
    {
        if ($strict) {
            foreach ($iterable as $key => $value) {
                if ($value === $filterValue) {
                    yield $key => $value;
                }
            }
        } else {
            foreach ($iterable as $key => $value) {
                if ($value == $filterValue) {
                    yield $key => $value;
                }
            }
        }
    }

    function filterByKey(iterable $iterable, $filterIndex, bool $strict = false): \Iterator
    {
        if ($strict) {
            foreach ($iterable as $key => $value) {
                if ($key === $filterIndex) {
                    yield $key => $value;
                }
            }
        } else {
            foreach ($iterable as $key => $value) {
                if ($key == $filterIndex) {
                    yield $key => $value;
                }
            }
        }
    }

    function filterNotByValue(iterable $iterable, $filterValue, bool $strict = false): \Iterator
    {
        if ($strict) {
            foreach ($iterable as $key => $value) {
                if ($value !== $filterValue) {
                    yield $key => $value;
                }
            }
        } else {
            foreach ($iterable as $key => $value) {
                if ($value != $filterValue) {
                    yield $key => $value;
                }
            }
        }
    }

    function filterNotByKey(iterable $iterable, $filterIndex, bool $strict = false): \Iterator
    {
        if ($strict) {
            foreach ($iterable as $key => $value) {
                if ($key !== $filterIndex) {
                    yield $key => $value;
                }
            }
        } else {
            foreach ($iterable as $key => $value) {
                if ($key != $filterIndex) {
                    yield $key => $value;
                }
            }
        }
    }

    function filterBySize(iterable $iterable, int $size): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\count($value) === $size) {
                yield $key => $value;
            }
        }
    }

    function filterNotBySize(iterable $iterable, int $size): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\count($value) !== $size) {
                yield $key => $value;
            }
        }
    }

    function filterBySizeGt(iterable $iterable, int $size): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\count($value) > $size) {
                yield $key => $value;
            }
        }
    }

    function filterBySizeGte(iterable $iterable, int $size): \Iterator
    {
        return filterBySizeGt($iterable, $size - 1);
    }

    function filterBySizeLt(iterable $iterable, int $size): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\count($value) < $size) {
                yield $key => $value;
            }
        }
    }

    function filterBySizeLte(iterable $iterable, int $size): \Iterator
    {
        return filterBySizeLt($iterable, $size + 1);
    }

    function filterByInArray(iterable $iterable, array $haystack, bool $strict = false): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\in_array($value, $haystack, $strict)) {
                yield $key => $value;
            }
        }
    }

    function filterByColumnInArray(iterable $iterable, $column, array $haystack, bool $strict = false): \Iterator
    {
        foreach ($iterable as $key => $array) {
            if (\in_array($array[$column], $haystack, $strict)) {
                yield $key => $array;
            }
        }
    }

    function filterNotByColumnInArray(iterable $iterable, $column, array $haystack, bool $strict = false): \Iterator
    {
        foreach ($iterable as $key => $array) {
            if (!\in_array($array[$column], $haystack, $strict)) {
                yield $key => $array;
            }
        }
    }

    function filterByKeyInArray(iterable $iterable, array $haystack, bool $strict = false): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\in_array($key, $haystack, $strict)) {
                yield $key => $value;
            }
        }
    }

    function filterNotByInArray(iterable $iterable, array $haystack, bool $strict = false): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!\in_array($value, $haystack, $strict)) {
                yield $key => $value;
            }
        }
    }

    function filterNotByKeyInArray(iterable $iterable, array $haystack, bool $strict = false): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!\in_array($key, $haystack, $strict)) {
                yield $key => $value;
            }
        }
    }

    /**
     * @param array|\ArrayObject $haystack
     */
    function filterByInMap(iterable $iterable, $haystack): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\array_key_exists($value, $haystack)) {
                yield $key => $value;
            }
        }
    }

    /**
     * @param array|\ArrayObject $haystack
     */
    function filterNotByInMap(iterable $iterable, $haystack): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!\array_key_exists($value, $haystack)) {
                yield $key => $value;
            }
        }
    }

    /**
     * @param array|\ArrayObject $haystack
     */
    function filterByKeyInMap(iterable $iterable, $haystack): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\array_key_exists($key, $haystack)) {
                yield $key => $value;
            }
        }
    }

    /**
     * @param array|\ArrayObject $haystack
     */
    function filterNotByKeyInMap(iterable $iterable, $haystack): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!\array_key_exists($key, $haystack)) {
                yield $key => $value;
            }
        }
    }

    function filterByContainsArray(iterable $iterable, array $needle): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (ArrayContainsComparator::containsArray($needle, $value)) {
                yield $key => $value;
            }
        }
    }

    function filterNotByContainsArray(iterable $iterable, array $needle): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!ArrayContainsComparator::containsArray($needle, $value)) {
                yield $key => $value;
            }
        }
    }

    function filterByMatch(iterable $iterable, string $pattern, int $flags = 0, int $offset = 0): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\preg_match($pattern, $value, $matches, $flags, $offset)) {
                yield $key => $value;
            }
        }
    }

    function filterNotByMatch(iterable $iterable, string $pattern, int $flags = 0, int $offset = 0)
    {
        foreach ($iterable as $key => $value) {
            if (!\preg_match($pattern, $value, $matches, $flags, $offset)) {
                yield $key => $value;
            }
        }
    }

    function filterByKeyMatch(iterable $iterable, string $pattern, int $flags = 0, int $offset = 0): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (\preg_match($pattern, $key, $matches, $flags, $offset)) {
                yield $key => $value;
            }
        }
    }

    function filterNotByKeyMatch(iterable $iterable, string $pattern, int $flags = 0, int $offset = 0)
    {
        foreach ($iterable as $key => $value) {
            if (!\preg_match($pattern, $key, $matches, $flags, $offset)) {
                yield $key => $value;
            }
        }
    }

    function padTo(iterable $iterable, int $padSize, $padValue, $padKey = null): \Iterator
    {
        $counter = 0;

        foreach ($iterable as $key => $value) {
            yield $key => $value;

            $counter++;
        }

        if (\func_num_args() === 3) {
            while ($counter < $padSize) {
                yield $padValue;

                $counter++;
            }
        } else {
            while ($counter < $padSize) {
                yield $padKey => $padValue;

                $counter++;
            }
        }
    }

    function toArray(iterable $iterable): array
    {
        return \is_array($iterable) ? $iterable : \iter\toArray($iterable);
    }

    function toObject(iterable $iterable, ?string $class = null): object
    {
        $object = isset($class) ? new $class() : new \stdClass();

        foreach ($iterable as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }

    function toArrayWithKeys(iterable $iterable): array
    {
        return \is_array($iterable) ? $iterable : \iter\toArrayWithKeys($iterable);
    }

    function stat(iterable $iterable): array
    {
        $map = [];

        foreach ($iterable as $value) {
            if (isset($map[$value])) {
                $map[$value]++;
            } else {
                $map[$value] = 1;
            }
        }

        return $map;
    }

    function statByKey(iterable $iterable): array
    {
        $map = [];

        foreach ($iterable as $key => $value) {
            if (isset($map[$key])) {
                $map[$key]++;
            } else {
                $map[$key] = 1;
            }
        }

        return $map;
    }

    function collapseByKey(iterable $iterable): array
    {
        $map = [];

        foreach ($iterable as $key => $value) {
            if (isset($map[$key])) {
                $map[$key][] = $value;
            } else {
                $map[$key] = [$value];
            }
        }

        return $map;
    }

    function uasortLazy(iterable $iterable, callable $comparator): \Iterator
    {
        $array = toArrayWithKeys($iterable);
        \uasort($array, $comparator);

        yield from $array;
    }

    function uniqueLazy(iterable $iterable, int $sortFlags = \SORT_STRING): \Iterator
    {
        $array = toArrayWithKeys($iterable);
        $array = \array_unique($array, $sortFlags);

        yield from $array;
    }

    function shuffleLazy(iterable $iterable): \Iterator
    {
        $array = toArray($iterable);
        \shuffle($array);

        yield from $array;
    }

    function sortLazy(iterable $iterable, ?int $sortFlags = null): \Iterator
    {
        $array = toArray($iterable);
        \sort($array, $sortFlags);

        yield from $array;
    }

    function rsortLazy(iterable $iterable, ?int $sortFlags = null): \Iterator
    {
        $array = toArray($iterable);
        \rsort($array, $sortFlags);

        yield from $array;
    }

    function asortLazy(iterable $iterable, ?int $sortFlags = null): \Iterator
    {
        $array = toArray($iterable);
        \asort($array, $sortFlags);

        yield from $array;
    }

    function arsortLazy(iterable $iterable, ?int $sortFlags = null): \Iterator
    {
        $array = toArrayWithKeys($iterable);
        \arsort($array, $sortFlags);

        yield from $array;
    }

    function usortLazy(iterable $iterable, callable $comparator): \Iterator
    {
        $array = toArray($iterable);
        \usort($array, $comparator);

        yield from $array;
    }

    function ksortLazy(iterable $iterable, ?int $sortFlags = null): \Iterator
    {
        $array = toArrayWithKeys($iterable);
        \ksort($array, $sortFlags);

        yield from $array;
    }

    function krsortLazy(iterable $iterable, ?int $sortFlags = null): \Iterator
    {
        $array = toArrayWithKeys($iterable);
        \krsort($array, $sortFlags);

        yield from $array;
    }

    function filterEmpty(iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (empty($value)) {
                yield $key => $value;
            }
        }
    }

    function filterHasEmptyColumnString(iterable $iterable, $column): \Iterator
    {
        foreach ($iterable as $key => $array) {
            if (\is_null($array[$column]) || ('' === $array[$column])) {
                yield $key => $array;
            }
        }
    }

    function filterNotEmpty(iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!empty($value)) {
                yield $key => $value;
            }
        }
    }

    function filterHasNotEmptyColumnString(iterable $iterable, $column): \Iterator
    {
        foreach ($iterable as $key => $array) {
            if (!\is_null($array[$column]) && ('' !== $array[$column])) {
                yield $key => $array;
            }
        }
    }

    function filterNotEmptyString(iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!\is_null($value) && ('' !== $value)) {
                yield $key => $value;
            }
        }
    }

    function filterIsInstance(iterable $iterable, $class): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if ($value instanceof $class) {
                yield $key => $value;
            }
        }
    }

    function filterIsNotInstance(iterable $iterable, $class): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!($value instanceof $class)) {
                yield $key => $value;
            }
        }
    }

    function onEach(callable $function, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            $function($value);

            yield $key => $value;
        }
    }

    function mapByTrim(iterable $iterable, string $charlist = " \t\n\r\0\x0B"): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => \trim($value, $charlist);
        }
    }

    function mapByApply(iterable $iterable, ...$args): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => $value(...$args);
        }
    }

    /**
     * @param string|null $encoding use "UTF-8" for multibyte strings
     */
    function mapToLower(iterable $iterable, ?string $encoding = null): \Iterator
    {
        if (\is_null($encoding)) {
            foreach ($iterable as $key => $value) {
                yield $key => \strtolower($value);
            }
        } else {
            foreach ($iterable as $key => $value) {
                yield $key => \mb_strtolower($value, $encoding);
            }
        }
    }

    function mapToUpper(iterable $iterable, ?string $encoding = null): \Iterator
    {
        if (\is_null($encoding)) {
            foreach ($iterable as $key => $value) {
                yield $key => \strtoupper($value);
            }
        } else {
            foreach ($iterable as $key => $value) {
                yield $key => \mb_strtoupper($value, $encoding);
            }
        }
    }

    function mapToInt(iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => (int) $value;
        }
    }

    function mapToString(iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => (string) $value;
        }
    }

    function mapToClosure(iterable $iterable, ?object $bindObject = null): \Iterator
    {
        if ($bindObject) {
            $invoker = function ($callable, ...$args) {
                return $callable(...$args);
            };

            foreach ($iterable as $key => $callable) {
                yield $key => function (...$args) use ($bindObject, $invoker, $callable) {
                    return $invoker->call($bindObject, $callable, ...$args);
                };
            }
        } else {
            foreach ($iterable as $key => $callable) {
                yield $key => $callable instanceof \Closure ?
                        $callable :
                        \Closure::fromCallable($callable);
            }
        }
    }

    function mapToMatches(iterable $iterable, string $pattern, array $mappedMatches, int $flags = 0, int $offset = 0): \Iterator
    {
        $mappedMatches = \array_flip($mappedMatches);

        foreach ($iterable as $key => $value) {
            if (\preg_match($pattern, $value, $matches, $flags, $offset)) {
                yield $key => \array_intersect_key($matches, $mappedMatches);
            } else {
                yield $key => [];
            }
        }
    }

    function flatMapToMatches(iterable $iterable, string $pattern, array $mappedMatches, int $flags = 0, int $offset = 0): \Iterator
    {
        $mappedMatches = \array_flip($mappedMatches);

        foreach ($iterable as $key => $value) {
            if (\preg_match($pattern, $value, $matches, $flags, $offset)) {
                $matches = \array_filter(\array_intersect_key($matches, $mappedMatches));

                if ($matches) {
                    yield $key => $matches;
                }
            }
        }
    }

    function mapToMatch(iterable $iterable, string $pattern, int $matchNumber = 1, int $flags = 0, int $offset = 0): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (
                \preg_match($pattern, $value, $matches, $flags, $offset)
                && isset($matches[$matchNumber])
            ) {
                yield $key => $matches[$matchNumber];
            } else {
                yield $key => null;
            }
        }
    }

    function flatMapToMatch(iterable $iterable, string $pattern, int $matchNumber = 1, int $flags = 0, int $offset = 0): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (
                \preg_match($pattern, $value, $matches, $flags, $offset)
                && isset($matches[$matchNumber])
            ) {
                yield $key => $matches[$matchNumber];
            }
        }
    }

    function format(iterable $iterable, string $format): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => \sprintf($format, $value, $key);
        }
    }

    function reverse(iterable $iterable): \Iterator
    {
        if (\is_array($iterable)) {
            $keys = reverseList(\array_keys($iterable));

            foreach ($keys as $key) {
                yield $key => $iterable[$key];
            }
        } else {
            $pairs = \iter\toArray(\iter\toPairs($iterable));

            yield from \iter\fromPairs(reverseList($pairs));
        }
    }

    function explodeLazy(string $delimeter, ?string $haystack, ?string $encoding = null): \Iterator
    {
        if (\is_null($encoding)) {
            $delimeterLength = \strlen($delimeter);

            if (0 === $delimeterLength) {
                \trigger_error('explode(): Empty delimiter', E_USER_WARNING);

                return;
            }

            $bufferStart = 0;

            while (false !== ($delimeterPos = \strpos($haystack, $delimeter, $bufferStart))) {
                yield \substr($haystack, $bufferStart, $delimeterPos - $bufferStart);

                $bufferStart = $delimeterPos + $delimeterLength;
            }

            if (0 === $bufferStart) {
                yield $haystack;
            } else {
                yield \substr($haystack, $bufferStart);
            }
        } else {
            $delimeterLength = \mb_strlen($delimeter, $encoding);

            if (0 === $delimeterLength) {
                \trigger_error('explode(): Empty delimiter', E_USER_WARNING);

                return;
            }

            $bufferStart = 0;

            while (false !== ($delimeterPos = \mb_strpos($haystack, $delimeter, $bufferStart, $encoding))) {
                yield \mb_substr($haystack, $bufferStart, $delimeterPos - $bufferStart, $encoding);

                $bufferStart = $delimeterPos + $delimeterLength;
            }

            if (0 === $bufferStart) {
                yield $haystack;
            } else {
                yield \mb_substr($haystack, $bufferStart, null, $encoding);
            }
        }
    }

    function fromJSONLazy($source, $propertyPath = '', bool $assoc = true): \Iterator
    {
        $accessor = new JsonExtractor(new JsonStreamParser());

        return $accessor->extract($source, $propertyPath, $assoc);
    }

    function fromJSON($source, $propertyPath = '', bool $assoc = true, int $depth = 512, int $options = 0): \Iterator
    {
        if (\is_resource($source) && ('stream' === \get_resource_type($source))) {
            $source = \stream_get_contents($source);
        } elseif (!\is_string($source)) {
            $source = (string) $source;
        }

        @\json_decode('N;');
        $jsonData = @\json_decode($source, $assoc, $depth, $options);
        $errorCode = @\json_last_error();

        if ($errorCode !== \JSON_ERROR_NONE) {
            throw new ParsingException(0, 0, \sprintf("Error Code: %s, Messsage: %s", $errorCode, \json_last_error_msg()));
        }

        if (('' !== $propertyPath) && !\is_null($propertyPath)) {
            $propertyAccessor = new SafeCallPropertyAccessor();

            yield from $propertyAccessor->getValue($jsonData, $propertyPath);
        } else {
            yield from $jsonData;
        }
    }

    function uniqueAdjacent(iterable $iterable, ?callable $comparator = null): \Iterator
    {
        $previous = null;
        $first = true;

        if ($comparator) {
            foreach ($iterable as $key => $value) {
                if ($first) {
                    yield $key => $value;

                    $previous = $value;
                    $first = false;
                } elseif ($comparator($value, $previous) !== 0) {
                    yield $key => $value;

                    $previous = $value;
                }
            }
        } else {
            foreach ($iterable as $key => $value) {
                if ($first) {
                    yield $key => $value;

                    $previous = $value;
                    $first = false;
                } elseif ($previous !== $value) {
                    yield $key => $value;

                    $previous = $value;
                }
            }
        }
    }

    function uniqueAdjacentByColumn(iterable $iterable, $column): \Iterator
    {
        return uniqueAdjacent($iterable, function ($value, $previousValue) use ($column) {
            return $value[$column] <=> $previousValue[$column];
        });
    }

    function uniqueAdjacentByField(iterable $iterable, string $field): \Iterator
    {
        return uniqueAdjacent($iterable, function ($value, $previousValue) use ($field) {
            return $value->{$field} <=> $previousValue->{$field};
        });
    }

    function uniqueAdjacentByPropertyPath(iterable $iterable, $propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): \Iterator
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return uniqueAdjacent($iterable, function ($value, $previousValue) use ($propertyPath, $propertyAccessor) {
            return $propertyAccessor->getValue($value, $propertyPath) <=> $propertyAccessor->getValue($previousValue, $propertyPath);
        });
    }

    function reverseList($list): \Iterator
    {
        for ($i = \count($list) - 1; $i >= 0; $i--) {
            yield $i => $list[$i];
        }
    }

    function cycle(iterable $iterable, $num = INF): \Iterator
    {
        if ($iterable instanceof \Generator) {
            $pairs = [];
            $iterableInner = (function () use ($iterable, &$pairs) {
                foreach ($iterable as $key => $value) {
                    yield $key => $value;

                    $pairs[] = [$key, $value];
                }
            })();
        } else {
            $iterableInner = $iterable;
        }

        for ($i = 0; $i < $num; ++$i) {
            yield from $iterableInner;

            if ($iterable instanceof \Generator) {
                $iterableInner = \iter\fromPairs($pairs);
            } elseif ($iterable instanceof \Iterator) {
                $iterable->rewind();
            }
        }
    }

    function toIterInternal(iterable $iterable): \Iterator
    {
        if (\is_array($iterable)) {
            return toGenerator($iterable);
        }

        if ($iterable instanceof \Iterator) {
            return $iterable;
        }

        if ($iterable instanceof \IteratorAggregate) {
            return $iterable->getIterator();
        }

        // Traversable, but not Iterator or IteratorAggregate
        return toGenerator($iterable);
    }

    function toGenerator(iterable $iterable): \Iterator
    {
        yield from $iterable;
    }

    function zip(iterable ...$iterables): \Iterator
    {
        return \iter\zip(...\array_map(__NAMESPACE__ . '\\toIterInternal', $iterables));
    }

    function slice(iterable $iterable, int $start, $length = INF): \Iterator
    {
        if ($start < 0) {
            throw new \InvalidArgumentException('Start offset must be non-negative');
        }

        if ($length < 0) {
            throw new \InvalidArgumentException('Length must be non-negative');
        }

        $i = 0;

        if (0 === $length) {
            if (0 === $start) {
                return;
            }

            foreach ($iterable as $_) {
                if ($i++ < $start) {
                    continue;
                }
            }
        } else {
            foreach ($iterable as $key => $value) {
                if ($i++ < $start) {
                    continue;
                }

                yield $key => $value;

                if ($i >= $start + $length) {
                    return;
                }
            }
        }
    }

    function take(int $num, iterable $iterable): \Iterator
    {
        return slice($iterable, 0, $num);
    }

    function drop(int $num, iterable $iterable): \Iterator
    {
        return slice($iterable, $num);
    }

    function makeUnrewindable(iterable $iterable): \Iterator
    {
        if ($iterable instanceof \NoRewindIterator) {
            return $iterable;
        } else {
            return new \NoRewindIterator(toIterInternal($iterable));
        }
    }

    function orElse(iterable $iterable, iterable $elseIterable): \Iterator
    {
        $iter = toIterInternal($iterable);
        $iter->rewind();

        if ($iter->valid()) {
            yield from new \NoRewindIterator($iter);
        } else {
            $elseIter = toIterInternal($elseIterable);
            $elseIter->rewind();

            if ($elseIter->valid()) {
                yield from new \NoRewindIterator($elseIter);
            }
        }
    }

    function uniformPick(iterable ...$iterables): \Iterator
    {
        /** @var \Iterator[] $iterators */
        $iterators = \array_map(__NAMESPACE__ . '\\toIterInternal', $iterables);
        $first = true;

        do {
            $hasOneValid = false;

            foreach ($iterators as $i => $iterator) {
                if (!$first) {
                    $iterator->next();
                }

                if ($iterator->valid()) {
                    $hasOneValid = true;

                    yield $iterator->key() => $iterator->current();
                } else {
                    unset($iterators[$i]);
                }
            }

            $first = false;
        } while ($hasOneValid);
    }

    function reindexByColumn(iterable $iterable, $column): \Iterator
    {
        foreach ($iterable as $value) {
            yield $value[$column] => $value;
        }
    }

    function reindexByField(iterable $iterable, string $field): \Iterator
    {
        foreach ($iterable as $value) {
            yield $value->{$field} => $value;
        }
    }

    function reindexByPropertyPath(iterable $iterable, $propertyPath, ?PropertyAccessorInterface $propertyAccessor = null): \Iterator
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        foreach ($iterable as $value) {
            yield $propertyAccessor->getValue($value, $propertyPath) => $value;
        }
    }

    function flatMapColumn($column, iterable $iterable): \Iterator
    {
        foreach ($iterable as $array) {
            yield from $array[$column];
        }
    }

    function flatMapField(string $field, iterable $iterable): \Iterator
    {
        foreach ($iterable as $object) {
            yield from $object->{$field};
        }
    }

    function flatMapPropertyPath($propertyPath, iterable $iterable, ?PropertyAccessorInterface $propertyAccessor = null): \Iterator
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        foreach ($iterable as $value) {
            yield from $propertyAccessor->getValue($value, $propertyPath);
        }
    }

    function toPairsWithFirst($firstValue, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => [$firstValue, $value];
        }
    }

    function toPairsWithSecond($secondValue, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => [$value, $secondValue];
        }
    }

    /**
     * @template C of (callable|null)
     * @template V
     * @param iterable<V> $iterable
     * @param C $comparator
     * @return (C is null ? (float|int): V)|null
     */
    function max(iterable $iterable, ?callable $comparator = null)
    {
        $first = true;
        $max = null;

        if ($comparator) {
            foreach ($iterable as $value) {
                if ($first) {
                    $max = $value;
                    $first = false;
                } else {
                    if ($comparator($value, $max) > 0) {
                        $max = $value;
                    }
                }
            }
        } else {
            foreach ($iterable as $value) {
                if ($first) {
                    $max = $value;
                    $first = false;
                } else {
                    if ($value > $max) {
                        $max = $value;
                    }
                }
            }
        }

        return $max;
    }

    /**
     * @return int|float|null
     */
    function min(iterable $iterable, ?callable $comparator = null)
    {
        $first = true;
        $min = null;

        if ($comparator) {
            foreach ($iterable as $value) {
                if ($first) {
                    $min = $value;
                    $first = false;
                } else {
                    if ($comparator($value, $min) < 0) {
                        $min = $value;
                    }
                }
            }
        } else {
            foreach ($iterable as $value) {
                if ($first) {
                    $min = $value;
                    $first = false;
                } else {
                    if ($value < $min) {
                        $min = $value;
                    }
                }
            }
        }

        return $min;
    }

    function bounds(iterable $iterable, ?callable $comparator = null): array
    {
        $first = true;
        $min = null;
        $max = null;

        if ($comparator) {
            foreach ($iterable as $value) {
                if ($first) {
                    $min = $value;
                    $max = $value;
                    $first = false;
                } else {
                    if ($comparator($value, $min) < 0) {
                        $min = $value;
                    } elseif ($comparator($value, $max) > 0) {
                        $max = $value;
                    }
                }
            }
        } else {
            foreach ($iterable as $value) {
                if ($first) {
                    $min = $value;
                    $max = $value;
                    $first = false;
                } else {
                    if ($value < $min) {
                        $min = $value;
                    } elseif ($value > $max) {
                        $max = $value;
                    }
                }
            }
        }

        return [$min, $max];
    }

    /**
     * @return int|float|array|null
     */
    function sum(iterable $iterable)
    {
        if (\is_array($iterable)) {
            if (\count($iterable) === 0) {
                return null;
            }

            return \array_sum($iterable);
        }

        $sum = null;

        foreach ($iterable as $value) {
            if (\is_null($sum)) {
                $sum = $value;
            } else {
                $sum += $value;
            }
        }

        return $sum;
    }

    /**
     * @return float|int|null
     */
    function average(iterable $iterable)
    {
        $sum = null;
        $counter = 0;

        foreach ($iterable as $value) {
            if (\is_null($sum)) {
                $sum = $value;
            } else {
                $sum += $value;
            }

            $counter++;
        }

        return $counter ? $sum / $counter : null;
    }

    /**
     * @return mixed|null
     */
    function indexOfValue(iterable $iterable, $filterValue, bool $strict = false)
    {
        if (\is_array($iterable)) {
            if (false !== ($index = \array_search($filterValue, $iterable, $strict))) {
                return $index;
            }

            return null;
        }

        $iter = filterByValue($iterable, $filterValue, $strict);
        $iter->rewind();

        if ($iter->valid()) {
            return $iter->key();
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    function lastIndexOfValue(iterable $iterable, $filterValue, bool $strict = false)
    {
        $key = null;

        foreach (filterByValue($iterable, $filterValue, $strict) as $key => $_) {
        }

        return $key;
    }

    /**
     * @return mixed|null
     */
    function elementAtIndex(iterable $iterable, $filterIndex, bool $strict = false)
    {
        if (\is_array($iterable) && !$strict) {
            if (\array_key_exists($filterIndex, $iterable)) {
                return $iterable[$filterIndex];
            }

            return null;
        }

        return first(filterByKey($iterable, $filterIndex, $strict));
    }

    /**
     * @return mixed|null
     */
    function lastElementAtIndex(iterable $iterable, $filterIndex, bool $strict = false)
    {
        return last(filterByKey($iterable, $filterIndex, $strict));
    }

    /**
     * @return mixed|null
     */
    function first(iterable $iterable)
    {
        $iter = toIterInternal($iterable);
        $iter->rewind();

        if ($iter->valid()) {
            return $iter->current();
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    function firstIndex(iterable $iterable)
    {
        $iter = toIterInternal($iterable);
        $iter->rewind();

        if ($iter->valid()) {
            return $iter->key();
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    function last(iterable $iterable)
    {
        $value = null;

        foreach ($iterable as $value) {
        }

        return $value;
    }

    /**
     * @return mixed|null
     */
    function lastIndex(iterable $iterable)
    {
        $key = null;

        foreach ($iterable as $key => $_) {
        }

        return $key;
    }

    /**
     * @return \Iterator[]
     */
    function spanLazy(iterable $iterable, callable $predicate): array
    {
        $gen = createRewindOnceGeneratorsProvider($iterable);

        $part1 = \iter\takeWhile($predicate, $gen());
        $part2 = iteratorSequence(filterNot($predicate, $gen()), $part1);

        return [$part1, $part2];
    }

    function span(iterable $iterable, callable $predicate): \Iterator
    {
        foreach (spanLazy($iterable, $predicate) as $part) {
            yield \iter\toArray($part);
        }
    }

    function spanWithKeys(iterable $iterable, callable $predicate): \Iterator
    {
        foreach (spanLazy($iterable, $predicate) as $part) {
            yield \iter\toArrayWithKeys($part);
        }
    }

    /**
     * @return \Iterator[]
     */
    function splitLazyAt(iterable $iterable, int ...$indexes): array
    {
        $gen = createRewindOnceGeneratorsProvider($iterable);

        $lastIndex = null;
        $iterators = [];

        foreach ($indexes as $k => $index) {
            if (\is_null($lastIndex)) {
                if ($index < 0) {
                    throw new \InvalidArgumentException("Index ({$index}) should no less than previous index ({$lastIndex})");
                }

                $iterators[] = \iter\take($index, $gen());
            } else {
                if ($index < $lastIndex) {
                    throw new \InvalidArgumentException("Index ({$index}) should no less than previous index ({$lastIndex})");
                }

                $iterators[] = iteratorSequence(
                    \iter\take($index - $lastIndex, $gen()),
                    $iterators[$k - 1]
                );
            }

            $lastIndex = $index;
        }

        // remainings
        $iterators[] = iteratorSequence($gen(), \end($iterators));

        return $iterators;
    }

    function splitAt(iterable $iterable, int ...$indexes): \Iterator
    {
        foreach (splitLazyAt($iterable, ...$indexes) as $splitPart) {
            yield \iter\toArray($splitPart);
        }
    }

    function splitAtWithKeys(iterable $iterable, int ...$indexes): \Iterator
    {
        foreach (splitLazyAt($iterable, ...$indexes) as $splitPart) {
            yield \iter\toArrayWithKeys($splitPart);
        }
    }

    function createRewindOnceGeneratorsProvider(iterable $iterable): \Closure
    {
        /** @var \Iterator|null $innerIter */
        $innerIter = null;

        return function () use (&$innerIter, &$iterable): \Iterator {
            if (\is_null($innerIter)) {
                $iter = toIterInternal($iterable);
                $iter->rewind();
                $innerIter = new \NoRewindIterator($iter);

                yield from $innerIter;
            } elseif ($innerIter->valid()) {
                yield from $innerIter;
            }
        };
    }

    function iteratorSequence(\Iterator $current, \Iterator $previous): \Iterator
    {
        while ($previous->valid()) {
            $previous->next();
        }

        yield from $current;
    }

    /**
     * @return \Iterator[]
     */
    function partition(iterable $iterable, callable $predicate): array
    {
        $iter = toIterInternal($iterable);
        $part1 = [];
        $part2 = [];
        $rewinded = false;

        $gen = function (array &$selfPartition, array &$otherPartition, bool $predicateValue) use (&$rewinded, $iter, $predicate) {
            if (!$rewinded) {
                $iter->rewind();
                $rewinded = true;
            }

            while ($iter->valid()) {
                if ($selfPartition) {
                    yield from \iter\fromPairs($selfPartition);

                    $selfPartition = [];
                }

                $key = $iter->key();
                $value = $iter->current();

                if ($predicateValue == $predicate($value)) {
                    yield $key => $value;
                } else {
                    $otherPartition[] = [$key, $value];
                }

                $iter->next();
            }

            if ($selfPartition) {
                yield from \iter\fromPairs($selfPartition);
            }
        };

        return [
            $gen($part1, $part2, true),
            $gen($part2, $part1, false),
        ];
    }

    function increment(iterable $iterable, int &$counter): \Iterator
    {
        foreach ($iterable as $key => $value) {
            $counter++;

            yield $key => $value;
        }
    }

    function decrement(iterable $iterable, int &$counter): \Iterator
    {
        foreach ($iterable as $key => $value) {
            $counter--;

            yield $key => $value;
        }
    }

    function nth(iterable $iterable, int $n, ?int &$counter = null): \Iterator
    {
        if (!isset($counter)) {
            $counter = 0;
        }

        foreach ($iterable as $key => $value) {
            $counter++;

            if ($counter % $n === 0) {
                yield $key => $value;
            }
        }
    }

    function onNth(iterable $iterable, int $n, callable $block, ?int &$counter = null): \Iterator
    {
        if (!isset($counter)) {
            $counter = 0;
        }

        foreach ($iterable as $key => $value) {
            $counter++;

            if ($counter % $n === 0) {
                $block($counter, $value, $key);
            }

            yield $key => $value;
        }
    }

    function onNthAndLast(iterable $iterable, int $n, callable $block, ?int &$counter = null): \Iterator
    {
        if (!isset($counter)) {
            $counter = 0;
        }

        $lastStopCounter = $counter;

        foreach ($iterable as $key => $value) {
            $counter++;

            if ($counter % $n === 0) {
                $block($counter, $value, $key, false);
                $lastStopCounter = $counter;
            }

            yield $key => $value;
        }

        if ($lastStopCounter !== $counter) {
            $block($counter, $value, $key, true);
        }
    }

    /**
     * @param callable $block - function(int $millisFromStart, int $iteration, $currentValue, $currentKey)
     */
    function onNthMillis(iterable $iterable, int $nMillis, callable $block): \Iterator
    {
        // stop - when block called
        // check - when time is checked
        $startMillis = $lastStopMillis = (\microtime(true) * 1000);
        $nextTicksCheck = 1;
        $lastTickCheck = $ticksCounter = 0;

        foreach ($iterable as $key => $value) {
            $ticksCounter++;

            if (($ticksCounter <= 10) || ($nextTicksCheck === $ticksCounter)) {
                $currMillis = (\microtime(true) * 1000);
                $millisFromLastStop = $currMillis - $lastStopMillis;

                if ($millisFromLastStop >= $nMillis) {
                    // stop
                    $block($currMillis - $startMillis, $ticksCounter, $value, $key);
                    $nextTicksCheckOffset = \max(1, (int) \floor(($ticksCounter - $lastTickCheck) / 8));
                    $lastStopMillis = $currMillis;
                } else {
                    $nextTicksCheckOffset = \max(1, (int) \ceil(($ticksCounter - $lastTickCheck) * 1.125));
                }

                $nextTicksCheck = $ticksCounter + $nextTicksCheckOffset;
                $lastTickCheck = $ticksCounter;
            }

            yield $key => $value;
        }
    }

    /**
     * @param callable $block - function(int $millisFromStart, int $iteration, $currentValue, $currentKey, bool $isLast)
     */
    function onNthMillisAndLast(iterable $iterable, int $nMillis, callable $block): \Iterator
    {
        // stop - when block called
        // check - when time is checked
        $startMillis = $lastStopMillis = (\microtime(true) * 1000);
        $nextTicksCheck = 1;
        $lastTickCheck = $ticksCounter = $lastStopCounter = 0;

        foreach ($iterable as $key => $value) {
            $ticksCounter++;

            if (($ticksCounter <= 10) || ($nextTicksCheck === $ticksCounter)) {
                $currMillis = (\microtime(true) * 1000);
                $millisFromLastStop = $currMillis - $lastStopMillis;

                if ($millisFromLastStop >= $nMillis) {
                    // stop
                    $block($currMillis - $startMillis, $ticksCounter, $value, $key, false);
                    $nextTicksCheckOffset = \max(1, (int) \floor(($ticksCounter - $lastTickCheck) / 8));
                    $lastStopCounter = $ticksCounter;
                    $lastStopMillis = $currMillis;
                } else {
                    $nextTicksCheckOffset = \max(1, (int) \ceil(($ticksCounter - $lastTickCheck) * 1.125));
                }

                $nextTicksCheck = $ticksCounter + $nextTicksCheckOffset;
                $lastTickCheck = $ticksCounter;
            }

            yield $key => $value;
        }

        if ($lastStopCounter !== $ticksCounter) {
            $block((\microtime(true) * 1000) - $startMillis, $ticksCounter, $value, $key, true);
        }
    }

    function mapVariadic(callable $function, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => $function(...$value);
        }
    }

    function flatMapVariadic(callable $function, iterable $iterable): \Iterator
    {
        foreach ($iterable as $value) {
            yield from $function(...$value);
        }
    }

    function mapIndexed(callable $function, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => $function($value, $key);
        }
    }

    function filterIndexed(callable $function, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if ($function($value, $key)) {
                yield $key => $value;
            }
        }
    }

    function filterNotIndexed(callable $function, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            if (!$function($value, $key)) {
                yield $key => $value;
            }
        }
    }

    function flatMapIndexed(callable $function, iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield from $function($value, $key);
        }
    }

    function applyIndexed(callable $function, iterable $iterable): void
    {
        foreach ($iterable as $key => $value) {
            $function($value, $key);
        }
    }

    function transform(iterable $iterable, callable $transformer): \Iterator
    {
        yield from $transformer($iterable);
    }

    function append(iterable $iterable, $value, $key = 0): \Iterator
    {
        yield from $iterable;

        yield $key => $value;
    }

    function appendNotNull(iterable $iterable, $value, $key = 0): \Iterator
    {
        yield from $iterable;

        if (!\is_null($value)) {
            yield $key => $value;
        }
    }

    function prepend(iterable $iterable, $value, $key = 0): \Iterator
    {
        yield $key => $value;

        yield from $iterable;
    }

    function prependNotNull(iterable $iterable, $value, $key = 0): \Iterator
    {
        if (!\is_null($value)) {
            yield $key => $value;
        }

        yield from $iterable;
    }

    function drain(iterable $iterable): void
    {
        foreach ($iterable as $_) {
        }
    }

    function memoize(iterable $iterable): \Iterator
    {
        return new MemoizableIterator(toIterInternal($iterable));
    }

    function nop(...$_): array
    {
        return [];
    }

    function coll(array $elements = []): ArrayCollectionExtended
    {
        return new ArrayCollectionExtended($elements);
    }

    function lazyColl(iterable $iterable, bool $withKeys = true): LazyArrayCollection
    {
        return new LazyArrayCollection($iterable, $withKeys);
    }

    function rangeWithLast($start, $end, $step = null): \Iterator
    {
        yield from \iter\range($start, $end, $step);

        if ($end % ($step ?? 1) !== 0) {
            yield $end;
        }
    }

    function sequence($start, callable $increment): \Iterator
    {
        yield $start;

        while (true) {
            yield $start = $increment($start);
        }
    }

    function randomInt(int $min, int $max): \Iterator
    {
        while (true) {
            yield \random_int($min, $max);
        }
    }

    function randomOf($alphabet, ?string $encoding = null): \Iterator
    {
        if (\is_array($alphabet)) {
            $count = \count($alphabet);

            if (!$count) {
                throw new \InvalidArgumentException('Alphabet should not be empty!');
            }

            $max = $count - 1;

            if (0 === $max) {
                $single = $alphabet[0];

                while (true) {
                    yield $single;
                }
            } else {
                while (true) {
                    $random = \random_int(0, $max);

                    yield $random => $alphabet[$random];
                }
            }
        } elseif (\is_string($alphabet)) {
            if (\is_null($encoding)) {
                $count = \strlen($alphabet);

                if (!$count) {
                    throw new \InvalidArgumentException('Alphabet should not be empty!');
                }

                $max = $count - 1;

                if (0 === $max) {
                    $single = $alphabet[0];

                    while (true) {
                        yield $single;
                    }
                } else {
                    while (true) {
                        $random = \random_int(0, $max);

                        yield $random => $alphabet[$random];
                    }
                }
            } else {
                $count = \mb_strlen($alphabet, $encoding);

                if (!$count) {
                    throw new \InvalidArgumentException('Alphabet should not be empty!');
                }

                $max = $count - 1;

                if (0 === $max) {
                    $single = \mb_substr($alphabet, 0, 1, $encoding);

                    while (true) {
                        yield $single;
                    }
                } else {
                    while (true) {
                        $random = \random_int(0, $max);

                        yield $random => \mb_substr($alphabet, $random, 1, $encoding);
                    }
                }
            }
        } else {
            throw new \InvalidArgumentException('Alphabet should be array or string');
        }
    }

    function timeout(iterable $iterable, int $milliseconds, ?\Throwable $throwable = null): \Iterator
    {
        $start = \microtime(true) * 1000;

        foreach ($iterable as $key => $value) {
            if ((\microtime(true) * 1000 - $start) > $milliseconds) {
                if ($throwable) {
                    throw $throwable;
                } else {
                    return;
                }
            } else {
                yield $key => $value;
            }
        }
    }

    function catchException(iterable $iterable, $class, callable $handler): \Iterator
    {
        try {
            yield from $iterable;
        } catch (\Throwable $e) {
            foreach ((array) $class as $klass) {
                if ($e instanceof $klass) {
                    $handler($e);

                    return;
                }
            }

            throw $e;
        }
    }

    function autoCatchException(iterable $iterable, callable $handler): \Iterator
    {
        yield from catchException($iterable, _extractExceptionClassFromHandler($handler), $handler);
    }

    function _extractExceptionClassFromHandler(callable $handler): string
    {
        $closure = \Closure::fromCallable($handler);
        $refl = new \ReflectionFunction($closure);
        $reflParameters = $refl->getParameters();
        $klass = \Throwable::class;

        if ($reflParameters) {
            $param = $reflParameters[0];
            $reflParamType = $param->getClass();

            if ($reflParamType) {
                $klass = $reflParamType->getName();
            }
        }

        return $klass;
    }

    function autoRecoverException(iterable $iterable, callable $handler): \Iterator
    {
        yield from recoverException($iterable, _extractExceptionClassFromHandler($handler), $handler);
    }

    function recoverException(iterable $iterable, $class, callable $handler): \Iterator
    {
        try {
            yield from $iterable;
        } catch (\Throwable $e) {
            foreach ((array) $class as $klass) {
                if ($e instanceof $klass) {
                    yield from $handler($e);

                    return;
                }
            }

            throw $e;
        }
    }

    function finalBlock(iterable $iterable, callable $handler): \Iterator
    {
        $catchedException = null;

        try {
            yield from $iterable;
        } catch (\Throwable $e) {
            $catchedException = $e;

            throw $e;
        } finally {
            if (isset($catchedException)) {
                $handler($catchedException);
            } else {
                $handler();
            }
        }
    }

    function toArrayWithCapacity(iterable $iterable, int $capacity): array
    {
        if ($capacity < 0) {
            throw new \InvalidArgumentException('Capacity should be non-negative');
        }

        $array = \array_fill(0, $capacity, null);
        $arrayIndex = 0;

        foreach ($iterable as $value) {
            if ($arrayIndex < $capacity) {
                $array[$arrayIndex] = $value;
            } else {
                $array[] = $value;
            }

            $arrayIndex++;
        }

        if ($arrayIndex < $capacity) {
            return \array_slice($array, 0, $arrayIndex);
        }

        return $array;
    }

    function decons(iterable $iterable): array
    {
        foreach ($iterable as $key => $value) {
            $tailIter = makeUnrewindable($iterable);

            if (\iter\isEmpty($tailIter)) {
                return [$value, null];
            }

            return [$value, $tailIter];
        }

        throw new \InvalidArgumentException('Empty iterable');
    }

    function fromCallable(callable $callable): \Iterator
    {
        yield from $callable();
    }

    function throttle(iterable $iterable, string $throttleKey, \ThrottlerInterface $throttler, ?callable $delayListener = null): \Iterator
    {
        foreach ($iterable as $key => $value) {
            while ($delay = $throttler->getDelay($throttleKey, true)) {
                if ($delayListener) {
                    $delayListener($delay);
                }

                \sleep($delay);
            }

            $throttler->increment($throttleKey);

            yield $key => $value;
        }
    }
}

namespace AwardWallet\MainBundle\Globals\Utils\iterFluent
{
    use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;

    /**
     * @template K
     * @template V
     * @param iterable<K, V> $iterable
     * @return IteratorFluent<K, V>
     */
    function it(iterable $iterable = []): IteratorFluent
    {
        return new IteratorFluent($iterable);
    }

    function mapToIter(iterable $iterable): \Iterator
    {
        foreach ($iterable as $key => $value) {
            yield $key => new IteratorFluent($value);
        }
    }

    function chain(iterable ...$iterables): IteratorFluent
    {
        return new IteratorFluent(\iter\chain(...$iterables));
    }
}

namespace AwardWallet\MainBundle\Globals\Utils\f {
    use AwardWallet\MainBundle\Globals\PropertyAccess\SafeCallPropertyAccessor;
    use AwardWallet\MainBundle\Globals\Utils\Criteria;
    use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;
    use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

    function column($column)
    {
        return function ($array) use ($column) {
            return $array[$column];
        };
    }

    function compareColumns($column)
    {
        return function ($a, $b) use ($column) {
            return $a[$column] <=> $b[$column];
        };
    }

    function field($propertyName)
    {
        return function ($object) use ($propertyName) {
            return $object->$propertyName;
        };
    }

    function compareFields($field)
    {
        return function ($a, $b) use ($field) {
            return $a->$field <=> $b->$field;
        };
    }

    function valueEq($valueExpected, bool $strict = false)
    {
        if ($strict) {
            return function ($value) use ($valueExpected) {
                return $value === $valueExpected;
            };
        } else {
            return function ($value) use ($valueExpected) {
                return $value == $valueExpected;
            };
        }
    }

    function valueNeq($valueExpected, bool $strict = false)
    {
        if ($strict) {
            return function ($value) use ($valueExpected) {
                return $value !== $valueExpected;
            };
        } else {
            return function ($value) use ($valueExpected) {
                return $value != $valueExpected;
            };
        }
    }

    function valueGt($valueExpected)
    {
        return function ($value) use ($valueExpected) {
            return $value > $valueExpected;
        };
    }

    function valueLt($valueExpected)
    {
        return function ($value) use ($valueExpected) {
            return $value < $valueExpected;
        };
    }

    function valueGte($valueExpected)
    {
        return function ($value) use ($valueExpected) {
            return $value >= $valueExpected;
        };
    }

    function valueLte($valueExpected)
    {
        return function ($value) use ($valueExpected) {
            return $value <= $valueExpected;
        };
    }

    function keyEq($keyExpected, bool $strict = false)
    {
        if ($strict) {
            return function ($_, $key) use ($keyExpected) {
                return $key === $keyExpected;
            };
        } else {
            return function ($_, $key) use ($keyExpected) {
                return $key == $keyExpected;
            };
        }
    }

    function keyNeq($keyExpected, bool $strict = false)
    {
        if ($strict) {
            return function ($_, $key) use ($keyExpected) {
                return $key !== $keyExpected;
            };
        } else {
            return function ($_, $key) use ($keyExpected) {
                return $key != $keyExpected;
            };
        }
    }

    function keyLt($keyExpected)
    {
        return function ($_, $key) use ($keyExpected) {
            return $key < $keyExpected;
        };
    }

    function keyLte($keyExpected)
    {
        return function ($_, $key) use ($keyExpected) {
            return $key <= $keyExpected;
        };
    }

    function keyGt($keyExpected)
    {
        return function ($_, $key) use ($keyExpected) {
            return $key > $keyExpected;
        };
    }

    function keyGte($keyExpected)
    {
        return function ($_, $key) use ($keyExpected) {
            return $key >= $keyExpected;
        };
    }

    function columnEq($column, $valueExpected, bool $strict = false)
    {
        if ($strict) {
            return function ($array) use ($column, $valueExpected) {
                return $array[$column] === $valueExpected;
            };
        } else {
            return function ($array) use ($column, $valueExpected) {
                return $array[$column] == $valueExpected;
            };
        }
    }

    function columnIsNull($column)
    {
        return columnEq($column, null, true);
    }

    function columnIsNotNull($column)
    {
        return columnNeq($column, null, true);
    }

    function columnNeq($column, $valueExpected, bool $strict = false)
    {
        if ($strict) {
            return function ($array) use ($column, $valueExpected) {
                return $array[$column] !== $valueExpected;
            };
        } else {
            return function ($array) use ($column, $valueExpected) {
                return $array[$column] != $valueExpected;
            };
        }
    }

    function columnGt($column, $valueExpected)
    {
        return function ($array) use ($column, $valueExpected) {
            return $array[$column] > $valueExpected;
        };
    }

    function columnGte($column, $valueExpected)
    {
        return function ($array) use ($column, $valueExpected) {
            return $array[$column] >= $valueExpected;
        };
    }

    function columnLt($column, $valueExpected)
    {
        return function ($array) use ($column, $valueExpected) {
            return $array[$column] < $valueExpected;
        };
    }

    function columnLte($column, $valueExpected)
    {
        return function ($array) use ($column, $valueExpected) {
            return $array[$column] <= $valueExpected;
        };
    }

    function columnIsSet($column)
    {
        return function ($array) use ($column) {
            return isset($array[$column]);
        };
    }

    function columnExists($column)
    {
        return function ($array) use ($column) {
            return \array_key_exists($column, $array);
        };
    }

    function columnIsEmpty($column)
    {
        return function ($array) use ($column) {
            return empty($array[$column]);
        };
    }

    function columnIsEmptyString($column)
    {
        return function ($array) use ($column) {
            $value = $array[$column];

            return
                \is_null($value)
                || ('' === $value);
        };
    }

    function columnIsNotEmptyString($column)
    {
        return function ($array) use ($column) {
            $value = $array[$column];

            return
                !\is_null($value)
                && ('' !== $value);
        };
    }

    function columnInArray($column, array $array, bool $strict = false)
    {
        return function ($value) use ($array, $strict, $column) {
            return \in_array($value[$column], $array, $strict);
        };
    }

    function columnNotInArray($column, array $array, bool $strict = false)
    {
        return function ($value) use ($array, $strict, $column) {
            return !\in_array($value[$column], $array, $strict);
        };
    }

    function columnInMap($column, array $map)
    {
        return function ($value) use ($map, $column) {
            return \array_key_exists($value[$column], $map);
        };
    }

    function columnNotInMap($column, array $map)
    {
        return function ($value) use ($map, $column) {
            return !\array_key_exists($value[$column], $map);
        };
    }

    function valueInArray(array $array, bool $strict = false)
    {
        return function ($value) use ($array, $strict) {
            return \in_array($value, $array, $strict);
        };
    }

    function valueNotInArray(array $array, bool $strict = false)
    {
        return function ($value) use ($array, $strict) {
            return !\in_array($value, $array, $strict);
        };
    }

    function valueInMap(array $map)
    {
        return function ($value) use ($map) {
            return \array_key_exists($value, $map);
        };
    }

    function valueNotInMap(array $map)
    {
        return function ($value) use ($map) {
            return !\array_key_exists($value, $map);
        };
    }

    function keyInArray(array $array, bool $strict = false)
    {
        return function ($_, $key) use ($array, $strict) {
            return \in_array($key, $array, $strict);
        };
    }

    function keyNotInArray(array $array, bool $strict = false)
    {
        return function ($_, $key) use ($array, $strict) {
            return !\in_array($key, $array, $strict);
        };
    }

    function keyInMap(array $map)
    {
        return function ($_, $key) use ($map) {
            return \array_key_exists($key, $map);
        };
    }

    function keyNotInMap(array $map)
    {
        return function ($_, $key) use ($map) {
            return !\array_key_exists($key, $map);
        };
    }

    function format(string $pattern)
    {
        return function ($value, $key = null) use ($pattern) {
            return \strtr($pattern, ['%k%' => $key, '%v%' => $value]);
        };
    }

    function propertyPath($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($value) use ($propertyPath, $propertyAccessor) {
            return $propertyAccessor->getValue($value, $propertyPath);
        };
    }

    function comparePropertyPaths($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($a, $b) use ($propertyPath, $propertyAccessor) {
            return $propertyAccessor->getValue($a, $propertyPath) <=> $propertyAccessor->getValue($b, $propertyPath);
        };
    }

    function propertyPathEq($propertyPath, $expectedValue, bool $strict = false, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        if ($strict) {
            return function ($value) use ($propertyPath, $propertyAccessor, $expectedValue) {
                return $propertyAccessor->getValue($value, $propertyPath) === $expectedValue;
            };
        } else {
            return function ($value) use ($propertyPath, $propertyAccessor, $expectedValue) {
                return $propertyAccessor->getValue($value, $propertyPath) == $expectedValue;
            };
        }
    }

    function propertyPathNeq($propertyPath, $expectedValue, bool $strict = false, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        if ($strict) {
            return function ($value) use ($propertyPath, $propertyAccessor, $expectedValue) {
                return $propertyAccessor->getValue($value, $propertyPath) !== $expectedValue;
            };
        } else {
            return function ($value) use ($propertyPath, $propertyAccessor, $expectedValue) {
                return $propertyAccessor->getValue($value, $propertyPath) != $expectedValue;
            };
        }
    }

    function propertyPathIsNull($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        return propertyPathEq($propertyPath, null, true, $propertyAccessor);
    }

    function propertyPathIsEmpty($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($value) use ($propertyPath, $propertyAccessor) {
            return empty($propertyAccessor->getValue($value, $propertyPath));
        };
    }

    function propertyPathIsNotEmpty($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($value) use ($propertyPath, $propertyAccessor) {
            return !empty($propertyAccessor->getValue($value, $propertyPath));
        };
    }

    function propertyPathIsNotNull($propertyPath, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        return propertyPathNeq($propertyPath, null, true, $propertyAccessor);
    }

    function propertyPathGt($propertyPath, $expectedValue, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($value) use ($propertyPath, $propertyAccessor, $expectedValue) {
            return $propertyAccessor->getValue($value, $propertyPath) > $expectedValue;
        };
    }

    function propertyPathGte($propertyPath, $expectedValue, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($value) use ($propertyPath, $propertyAccessor, $expectedValue) {
            return $propertyAccessor->getValue($value, $propertyPath) >= $expectedValue;
        };
    }

    function propertyPathLt($propertyPath, $expectedValue, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($value) use ($propertyPath, $propertyAccessor, $expectedValue) {
            return $propertyAccessor->getValue($value, $propertyPath) < $expectedValue;
        };
    }

    function propertyPathLte($propertyPath, $expectedValue, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($value) use ($propertyPath, $propertyAccessor, $expectedValue) {
            return $propertyAccessor->getValue($value, $propertyPath) <= $expectedValue;
        };
    }

    function propertyPathInArray($propertyPath, array $array, bool $strict = false, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($value) use ($propertyPath, $propertyAccessor, $array, $strict) {
            return \in_array($propertyAccessor->getValue($value, $propertyPath), $array, $strict);
        };
    }

    function propertyPathInMap($propertyPath, array $map, ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor = $propertyAccessor ?: new SafeCallPropertyAccessor();

        return function ($value) use ($propertyPath, $propertyAccessor, $map) {
            return \array_key_exists($propertyAccessor->getValue($value, $propertyPath), $map);
        };
    }

    // ## operators

    function andX(...$operations)
    {
        if (!$operations) {
            throw new \InvalidArgumentException('No operations provided!');
        }

        return function ($value, $key = null) use ($operations) {
            foreach ($operations as $operation) {
                if (!$operation($value, $key)) {
                    return false;
                }
            }

            return true;
        };
    }

    function orX(...$operations)
    {
        if (!$operations) {
            throw new \InvalidArgumentException('No operations provided!');
        }

        return function ($value, $key = null) use ($operations) {
            foreach ($operations as $operation) {
                if ($operation($value, $key)) {
                    return true;
                }
            }

            return false;
        };
    }

    function not($operation)
    {
        return function ($value, $key = null) use ($operation) {
            return !$operation($value, $key);
        };
    }

    function compose(...$operations)
    {
        if (!$operations) {
            throw new \InvalidArgumentException('No operations provided!');
        }

        return function ($value, $key = null) use ($operations) {
            $first = true;
            $res = null;

            foreach ($operations as $operation) {
                if ($first) {
                    $res = $operation($value, $key);
                    $first = false;

                    continue;
                }

                $res = $operation($res, $key);
            }

            return $res;
        };
    }

    function compareBy($valueProvider)
    {
        return function ($a, $b) use ($valueProvider) {
            return $valueProvider($a) <=> $valueProvider($b);
        };
    }

    function orderBy(...$comparators): \Closure
    {
        if (!$comparators) {
            throw new \InvalidArgumentException('Empty comparators!');
        }

        $next = null;

        foreach (\array_reverse($comparators) as $field => $ordering) {
            if (\is_object($ordering) && $ordering instanceof \Closure) {
                $next = linkComparators($ordering, /** ASC */ 1, $next);
            } elseif (\is_array($ordering)) {
                [$comparator, $ordering] = $ordering;
                $next = linkComparators($comparator, $ordering == Criteria::DESC ? -1 : 1, $next);
            } else {
                $next = ClosureExpressionVisitor::sortByField($field, $ordering == Criteria::DESC ? -1 : 1, $next);
            }
        }

        return $next;
    }

    function linkComparators($comparator, $orientation = 1, ?\Closure $nextComparator = null)
    {
        return function ($a, $b) use ($comparator, $nextComparator, $orientation): int {
            $comparationResult = $comparator($a, $b);

            if (0 === $comparationResult) {
                return $nextComparator ? $nextComparator($a, $b) : 0;
            }

            return $comparationResult * $orientation;
        };
    }

    function coalesce(...$valueProviders)
    {
        if (!$valueProviders) {
            throw new \InvalidArgumentException('Empty value providers!');
        }

        return function ($value) use ($valueProviders) {
            foreach ($valueProviders as $valueProvider) {
                if (\is_object($valueProvider) && $valueProvider instanceof \Closure) {
                    $providedValue = $valueProvider($value);
                } else {
                    $providedValue = $valueProvider;
                }

                if (!\is_null($providedValue)) {
                    return $providedValue;
                }
            }

            return null;
        };
    }

    function call(callable $callable)
    {
        return $callable();
    }
}
