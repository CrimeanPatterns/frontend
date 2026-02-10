<?php

namespace AwardWallet\MainBundle\Globals;

/**
 * Class ImmutableFluent.
 *
 * The Magic lives here
 */
trait ImmutableFluent
{
    /**
     * @var bool
     */
    private $locked = false;

    public function __call($name, $arguments)
    {
        switch (true) {
            case ($prefix = 'set') === substr($name, 0, 3):
                $property = $this->getPropertyName($prefix, $name);
                $this->checkArgumentsCount($arguments, 1, $name);

                if ($this->locked) {
                    $cloned = clone $this;
                    $cloned->$property = $arguments[0];

                    return $cloned;
                } else {
                    $this->$property = $arguments[0];

                    return $this;
                }

                // no break
            case ($prefix = 'get') === substr($name, 0, 3):
                $this->throwIfUnlocked($name);
                $this->checkArgumentsCount($arguments, 0, $name);
                $property = $this->getPropertyName($prefix, $name);

                return $this->$property;

            case ($prefix = 'is') === substr($name, 0, 2):
                $this->throwIfUnlocked($name);
                $this->checkArgumentsCount($arguments, 0, $name);
                $property = $this->getPropertyName($prefix, $name);

                return (bool) $this->$property;

            case ($prefix = 'has') === substr($name, 0, 3):
                $this->throwIfUnlocked($name);
                $this->checkArgumentsCount($arguments, 0, $name);
                $property = $this->getPropertyName($prefix, $name);

                return null !== $this->$property;

            default:
                throw new \RuntimeException(sprintf('Call to undefined method "%s"', $name));
        }
    }

    public function __set($name, $value)
    {
        throw new \RuntimeException(sprintf('Undefined property "%s" or non-fluent access', $name));
    }

    public function __get($name)
    {
        throw new \RuntimeException(sprintf('Undefined property "%s" or non-fluent access', $name));
    }

    /**
     * @return $this
     */
    public function lock()
    {
        $this->locked = true;

        $cloned = clone $this;

        return $cloned;
    }

    /**
     * @return bool
     */
    protected function isLocked()
    {
        return $this->locked;
    }

    /**
     * @param string $method
     * @throws \RuntimeException
     */
    private function throwIfUnlocked($method)
    {
        if (!$this->locked) {
            throw new \RuntimeException(sprintf('Invoking method "%s" on non-locked object', $method));
        }
    }

    /**
     * @param array $arguments
     * @param int $count
     */
    private function checkArgumentsCount($arguments, $count, $name)
    {
        if (count($arguments) !== $count) {
            throw new \RuntimeException(sprintf('Invalid arguments count %d for method "%s", must be 1', count($arguments), $name));
        }
    }

    private function getPropertyName($prefix, $name)
    {
        if (strlen($prefix) === strlen($name)) {
            throw new \RuntimeException(sprintf('Call to undefined method "%s"', $name));
        }

        $property = lcfirst(substr($name, strlen($prefix)));

        if ('locked' === $property) {
            throw new \RuntimeException('Access violation on property "locked"');
        }

        if (!property_exists($this, $property)) {
            throw new \RuntimeException(sprintf('Access to undefined property "%s"', $property));
        }

        return $property;
    }
}
