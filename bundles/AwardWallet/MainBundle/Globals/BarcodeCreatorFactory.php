<?php

namespace AwardWallet\MainBundle\Globals;

class BarcodeCreatorFactory
{
    /**
     * @var string
     */
    private $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function createBarcodeCreator(): BarcodeCreator
    {
        return new BarcodeCreator($this->rootDir . '/../web');
    }
}
