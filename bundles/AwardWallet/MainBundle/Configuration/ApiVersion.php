<?php

namespace AwardWallet\MainBundle\Configuration;

use Herrera\Version\Parser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class ApiVersion extends ConfigurationAnnotation
{
    /**
     * Min version.
     *
     * @var string
     */
    protected $min;

    /**
     * @var string[]
     */
    protected $features;

    /**
     * Translation domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * Returns the alias name for an annotated configuration.
     *
     * @return string
     */
    public function getAliasName()
    {
        return 'api_version';
    }

    /**
     * Returns whether multiple annotations of this type are allowed.
     *
     * @return bool
     */
    public function allowArray()
    {
        return false;
    }

    /**
     * @return \string[]
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * @param \string[] $features
     */
    public function setFeatures($features)
    {
        $this->features = $features;
    }

    /**
     * @return string
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @param string $min
     */
    public function setMin($min)
    {
        Parser::toVersion($min); // throw on compiler pass

        $this->min = $min;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }
}
