<?php

namespace AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MultiRegexBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Pattern
{
    public string $pattern;
    public $key;
    public int $priority;
    public int $popularity;
    public string $shrunkPattern;

    public function __construct(
        string $pattern,
        string $shrunkPattern,
        $key,
        int $priority,
        int $popularity
    ) {
        $this->pattern = $pattern;
        $this->key = $key;
        $this->priority = $priority;
        $this->popularity = $popularity;
        $this->shrunkPattern = $shrunkPattern;
    }
}
