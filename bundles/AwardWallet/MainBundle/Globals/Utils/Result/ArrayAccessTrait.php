<?php

namespace AwardWallet\MainBundle\Globals\Utils\Result;

trait ArrayAccessTrait
{
    public function offsetExists($offset)
    {
        $this->throwUnimplemented();
    }

    public function offsetSet($offset, $value)
    {
        $this->throwUnimplemented();
    }

    public function offsetUnset($offset)
    {
        $this->throwUnimplemented();
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

    private function throwUnimplemented()
    {
        throw new \LogicException('Unimplemeted!');
    }
}
