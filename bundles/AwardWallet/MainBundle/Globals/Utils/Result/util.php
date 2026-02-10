<?php

namespace AwardWallet\MainBundle\Globals\Utils\Result;

use AwardWallet\MainBundle\Globals\Utils\None;

/**
 * @template A
 * @template AS of A[]
 * @param AS $args
 * @return (AS is non-empty-list ? Success<A> : Success<None>)
 */
function success(...$args): Success
{
    if ($args) {
        return new Success($args[0]);
    }

    return new Success(None::getInstance());
}

/**
 * @psalm-template S
 * @psalm-param S $success
 * @deprecated Wait for psalm templates support
 * @return Result<null, S>
 */
function successR($success)
{
    return Result::createSuccess($success);
}

/**
 * @return Result<null, None>
 * @deprecated Wait for psalm templates support
 */
function successEmptyR()
{
    return Result::createSuccess(None::getInstance());
}

/**
 * @template B
 * @template BS of B[]
 * @param BS $args
 * @return (BS is non-empty-list ? Fail<B> : Fail<None>)
 */
function fail(...$args): Fail
{
    if ($args) {
        return new Fail($args[0]);
    }

    return new Fail(None::getInstance());
}

/**
 * @psalm-template E
 * @psalm-param E $fail
 * @deprecated Wait for psalm templates support
 * @return Result<E, null>
 */
function failR($fail)
{
    return Result::createFail($fail);
}

/**
 * @deprecated Wait for psalm templates support
 * @return Result<None, null>
 */
function failEmptyR()
{
    return Result::createFail(None::getInstance());
}
