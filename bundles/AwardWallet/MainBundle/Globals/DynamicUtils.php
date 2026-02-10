<?php

namespace AwardWallet\MainBundle\Globals;

class DynamicUtils
{
    public static function createArrayAccessImpl(?callable $get = null, ?callable $set = null, ?callable $unset = null, ?callable $exists = null): \ArrayAccess
    {
        return new class($get, $set, $unset, $exists) implements \ArrayAccess {
            /**
             * @var callable
             */
            private $get;
            /**
             * @var callable
             */
            private $set;
            /**
             * @var callable
             */
            private $unset;
            /**
             * @var callable
             */
            private $exists;

            public function __construct(?callable $get = null, ?callable $set = null, ?callable $unset = null, ?callable $exists = null)
            {
                $this->get = $get;
                $this->set = $set;
                $this->unset = $unset;
                $this->exists = $exists;
            }

            public function offsetExists($offset)
            {
                return ($this->exists)($offset);
            }

            public function offsetGet($offset)
            {
                return ($this->get)($offset);
            }

            public function offsetSet($offset, $value)
            {
                return ($this->set)($offset, $value);
            }

            public function offsetUnset($offset)
            {
                return ($this->unset)($offset);
            }
        };
    }

    public static function createToStringImpl(callable $toString): object
    {
        return new class($toString) {
            /**
             * @var callable
             */
            private $toString;

            public function __construct(callable $toString)
            {
                $this->toString = $toString;
            }

            public function __toString()
            {
                return (string) ($this->toString)();
            }
        };
    }
}
