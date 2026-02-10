<?php

namespace AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class UnsupportedProvider
{
    /**
     * @var string
     */
    private $error;

    public function __construct(string $error)
    {
        $this->error = $error;
    }

    public function getError(): string
    {
        return $this->error;
    }
}
