<?php

namespace AwardWallet\MainBundle\Globals\BinarySearchResult;

final class Exact implements BinarySearchResultInterface
{
    private int $index;
    private int $prefix;
    private int $suffix;

    public function __construct(int $index, int $prefix, int $suffix)
    {
        $this->index = $index;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getPrefixLength(): int
    {
        return $this->prefix;
    }

    public function getSuffixLength(): int
    {
        return $this->suffix;
    }
}
