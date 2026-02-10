<?php

namespace AwardWallet\MainBundle\Service\MerchantLookup;

class IncoherentResultException extends \RuntimeException
{
    /**
     * @var array[]
     */
    private array $merchants;

    /**
     * @param array[] $merchants
     */
    public function __construct(array $merchants, $message = "", $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->merchants = $merchants;
    }

    public function getMerchants(): array
    {
        return $this->merchants;
    }
}
