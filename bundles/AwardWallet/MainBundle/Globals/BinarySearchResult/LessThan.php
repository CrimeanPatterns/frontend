<?php

namespace AwardWallet\MainBundle\Globals\BinarySearchResult;

final class LessThan implements BinarySearchResultInterface
{
    private int $suffix;

    public function __construct(int $suffix)
    {
        $this->suffix = $suffix;
    }

    public function getPrefixLength(): int
    {
        return 0;
    }

    public function getSuffixLength(): int
    {
        return $this->suffix;
    }
}
