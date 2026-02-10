<?php

namespace AwardWallet\MainBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class AwSecureToken extends ConfigurationAnnotation
{
    /**
     * @var string
     */
    protected $service;
    /**
     * @var int
     */
    protected $lifetime = 5 * 60;
    /**
     * @var string[]
     */
    protected $triggerFeatures = [];
    /**
     * @var string[]
     */
    protected $methods = [];

    /**
     * @return string|null
     */
    public function getService()
    {
        return $this->service;
    }

    public function setService(string $service)
    {
        $this->service = $service;
    }

    /**
     * @return int|null
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * @param int $lifetime
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;
    }

    /**
     * @return string[]
     */
    public function getTriggerFeatures(): array
    {
        return $this->triggerFeatures;
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
        $this->methods = array_map('strtoupper', $methods);
    }

    /**
     * @param string[] $triggerFeatures
     */
    public function setTriggerFeatures(array $triggerFeatures)
    {
        $this->triggerFeatures = $triggerFeatures;
    }

    public function getAliasName()
    {
        return 'aw_secure_token';
    }

    public function allowArray()
    {
        return false;
    }
}
