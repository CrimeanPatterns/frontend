<?php

namespace AwardWallet\MainBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class Reauthentication extends ConfigurationAnnotation
{
    /**
     * @var string[]
     */
    protected $methods = [];
    /**
     * @var bool
     */
    protected $autoReset = true;
    /**
     * @var bool
     */
    protected $checkDeviceSupport = false;

    public function getAliasName()
    {
        return 'reauthentication';
    }

    public function allowArray()
    {
        return false;
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param string[] $methods
     */
    public function setMethods(array $methods)
    {
        $this->methods = \array_map('\\strtoupper', $methods);
    }

    public function isAutoReset(): bool
    {
        return $this->autoReset;
    }

    public function setAutoReset(bool $autoReset): Reauthentication
    {
        $this->autoReset = $autoReset;

        return $this;
    }

    public function getCheckDeviceSupport(): bool
    {
        return $this->checkDeviceSupport;
    }

    public function setCheckDeviceSupport(bool $checkDeviceSupport): Reauthentication
    {
        $this->checkDeviceSupport = $checkDeviceSupport;

        return $this;
    }
}
