<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

class ResultResponse
{
    /**
     * @var bool
     */
    public $success;

    /**
     * @var string|null
     */
    public $error;

    /**
     * @var bool
     */
    public $canRetryOnError;

    public static function create(bool $success, ?string $error = null, bool $canRetryOnError = true): self
    {
        $result = new static();
        $result->success = $success;
        $result->error = $error;
        $result->canRetryOnError = $canRetryOnError;

        return $result;
    }
}
