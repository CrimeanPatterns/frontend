<?php

namespace AwardWallet\MainBundle\Globals\BinarySearchResult;

interface BinarySearchResultInterface
{
    /**
     * Returns length of the prefix of the list, where all elements are less than the searched value.
     */
    public function getPrefixLength(): int;

    /**
     * Returns length of the suffix of the list, where all elements are greater than or equal to the searched value.
     */
    public function getSuffixLength(): int;
}
