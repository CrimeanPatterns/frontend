<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

/**
 * Generated resource DTO for 'Input'.
 */
class Input
{
    /**
     * @var string
     * @Type("string")
     */
    private $code;

    /**
     * @var string
     * @Type("string")
     */
    private $title;

    /**
     * @var PropertyInfo[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\PropertyInfo>")
     */
    private $options;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $required;

    /**
     * @var string
     * @Type("string")
     */
    private $defaultValue;

    /**
     * @param string
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param array
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param bool
     * @return $this
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setDefaultvalue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getDefaultvalue()
    {
        return $this->defaultValue;
    }
}
