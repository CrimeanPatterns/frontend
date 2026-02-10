<?php

namespace AwardWallet\MainBundle\Globals\Utils\Result;

/**
 * @template A
 * @template B
 * @template-implemets ArrayAccess<int, A|B>.
 */
interface ResultInterface extends \ArrayAccess
{
    /**
     * @return ($this is Success<A> ? A : B)
     */
    public function unwrap();

    /**
     * @psalm-assert-if-true Success<A> $this
     * @psalm-assert-if-false Fail<B> $this
     */
    public function isSuccess(): bool;

    /**
     * @psalm-assert-if-true Fail<B> $this
     * @psalm-assert-if-false Success<A> $this
     */
    public function isFail(): bool;
}
