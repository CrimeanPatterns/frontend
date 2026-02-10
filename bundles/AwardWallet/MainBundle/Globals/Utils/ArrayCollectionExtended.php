<?php

namespace AwardWallet\MainBundle\Globals\Utils;

use Doctrine\Common\Collections\ArrayCollection;

class ArrayCollectionExtended extends ArrayCollection
{
    public function unique(int $sortFlags = \SORT_STRING): self
    {
        return $this->createFrom(\array_unique($this->toArray(), $sortFlags));
    }

    public function sort(?int $sortFlags = null): self
    {
        $array = $this->toArray();
        \sort($array, $sortFlags);

        return $this->createFrom($array);
    }

    public function rsort(?int $sortFlags = null): self
    {
        $array = $this->toArray();
        \rsort($array, $sortFlags);

        return $this->createFrom($array);
    }

    public function asort(?int $sortFlags = null): self
    {
        $array = $this->toArray();
        \asort($array, $sortFlags);

        return $this->createFrom($array);
    }

    public function arsort(?int $sortFlags = null): self
    {
        $array = $this->toArray();
        \arsort($array, $sortFlags);

        return $this->createFrom($array);
    }

    public function ksort(?int $sortFlags = null): self
    {
        $array = $this->toArray();
        \ksort($array, $sortFlags);

        return $this->createFrom($array);
    }

    public function krsort(?int $sortFlags = null): self
    {
        $array = $this->toArray();
        \krsort($array, $sortFlags);

        return $this->createFrom($array);
    }

    public function usort(callable $comparator): self
    {
        $array = $this->toArray();
        \usort($array, $comparator);

        return $this->createFrom($array);
    }

    public function uksort(callable $comparator): self
    {
        $array = $this->toArray();
        \uksort($array, $comparator);

        return $this->createFrom($array);
    }

    public function uasort(callable $comparator): self
    {
        $array = $this->toArray();
        \uasort($array, $comparator);

        return $this->createFrom($array);
    }

    public function shuffle(): self
    {
        $array = $this->toArray();
        \shuffle($array);

        return $this->createFrom($array);
    }

    public function join(string $glue = ''): string
    {
        return \implode($glue, $this->toArray());
    }

    public function toFluent(): IteratorFluent
    {
        return new IteratorFluent($this->toArray());
    }

    public function it(): IteratorFluent
    {
        return new IteratorFluent($this->toArray());
    }
}
