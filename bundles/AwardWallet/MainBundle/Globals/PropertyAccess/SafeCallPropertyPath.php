<?php

namespace AwardWallet\MainBundle\Globals\PropertyAccess;

use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\Exception\OutOfBoundsException;
use Symfony\Component\PropertyAccess\PropertyPathIterator;
use Symfony\Component\PropertyAccess\PropertyPathIteratorInterface;

class SafeCallPropertyPath implements \IteratorAggregate, SafeCallPropertyPathInterface
{
    /**
     * Character used for separating between plural and singular of an element.
     */
    public const SINGULAR_SEPARATOR = '|';

    /**
     * The elements of the property path.
     *
     * @var array
     */
    private $elements = [];

    /**
     * The number of elements in the property path.
     *
     * @var int
     */
    private $length;

    /**
     * Contains a Boolean for each property in $elements denoting whether this
     * element is an index. It is a property otherwise.
     *
     * @var array
     */
    private $isIndex = [];

    /**
     * String representation of the path.
     *
     * @var string
     */
    private $pathAsString;

    /**
     * @var bool[]
     */
    private $isSafeCall = [];

    /**
     * Constructs a property path from a string.
     *
     * @param SafeCallPropertyPath|string $propertyPath The property path as string or instance
     * @throws InvalidArgumentException     If the given path is not a string
     * @throws InvalidPropertyPathException If the syntax of the property path is not valid
     */
    public function __construct($propertyPath)
    {
        // Can be used as copy constructor
        if ($propertyPath instanceof self) {
            /* @var SafeCallPropertyPath $propertyPath */
            $this->elements = $propertyPath->elements;
            $this->length = $propertyPath->length;
            $this->isIndex = $propertyPath->isIndex;
            $this->pathAsString = $propertyPath->pathAsString;
            $this->isSafeCall = $propertyPath->isSafeCall;

            return;
        }

        if (!is_string($propertyPath)) {
            throw new InvalidArgumentException(sprintf('The property path constructor needs a string or an instance of "Symfony\Component\PropertyAccess\PropertyPath". Got: "%s"', is_object($propertyPath) ? get_class($propertyPath) : gettype($propertyPath)));
        }

        if ('' === $propertyPath) {
            throw new InvalidPropertyPathException('The property path should not be empty.');
        }

        $this->pathAsString = $propertyPath;
        $position = 0;
        $remaining = $propertyPath;

        // first element is evaluated differently - no leading dot for properties
        $pattern = /** @lang RegExp */ '/^((([^\.\[\?]++)(\??))|\[([^\]]++)\](\?)?)(.*)/';
        $safeCallExists = false;

        while (preg_match($pattern, $remaining, $matches)) {
            if ('' !== $matches[3]) {
                $element = $matches[3];
                $this->isIndex[] = false;
            } else {
                $element = $matches[5];
                $this->isIndex[] = true;
            }

            $safeCallExists = $safeCallExists || ('' !== $matches[4]) || ('' !== $matches[6]);
            $this->isSafeCall[] = $safeCallExists;
            $this->elements[] = $element;

            $position += strlen($matches[1]);
            $remaining = $matches[7];
            $pattern = /** @lang RegExp */ '/^(\.(([^\.|\[\?]++)(\?)?)|\[([^\]]++)\](\?)?)(.*)/';
        }

        if ('' !== $remaining) {
            throw new InvalidPropertyPathException(sprintf('Could not parse property path "%s". Unexpected token "%s" at position %d', $propertyPath, $remaining[0], $position));
        }

        $this->length = count($this->elements);
    }

    public function __toString()
    {
        return $this->pathAsString;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getParent()
    {
        if ($this->length <= 1) {
            return;
        }

        $parent = clone $this;

        --$parent->length;
        $parent->pathAsString = substr($parent->pathAsString, 0, max(strrpos($parent->pathAsString, '.'), strrpos($parent->pathAsString, '[')));
        array_pop($parent->elements);
        array_pop($parent->isIndex);
        array_pop($parent->isSafeCall);

        return $parent;
    }

    /**
     * Returns a new iterator for this path.
     *
     * @return PropertyPathIteratorInterface
     */
    public function getIterator()
    {
        return new PropertyPathIterator($this);
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function getElement($index)
    {
        if (!isset($this->elements[$index])) {
            throw new OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return $this->elements[$index];
    }

    public function isProperty($index)
    {
        if (!isset($this->isIndex[$index])) {
            throw new OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return !$this->isIndex[$index];
    }

    public function isIndex($index)
    {
        if (!isset($this->isIndex[$index])) {
            throw new OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return $this->isIndex[$index];
    }

    public function isSafeCall($index): bool
    {
        if (!isset($this->isSafeCall[$index])) {
            throw new OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return $this->isSafeCall[$index];
    }
}
