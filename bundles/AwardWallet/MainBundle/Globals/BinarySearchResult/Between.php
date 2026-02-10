<?php

namespace AwardWallet\MainBundle\Globals\BinarySearchResult;

class Between implements BinarySearchResultInterface
{
    private int $low;
    private int $high;
    private int $prefix;
    private int $suffix;

    public function __construct(int $low, int $high, int $prefix, int $suffix)
    {
        $this->low = $low;
        $this->high = $high;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }

    public function getLowIndex(): int
    {
        return $this->low;
    }

    public function getHighIndex(): int
    {
        return $this->high;
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
