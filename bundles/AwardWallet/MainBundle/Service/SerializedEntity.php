<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class SerializedEntity
{
    /**
     * @var string
     */
    private $class;
    /**
     * @var array
     */
    private $data;
    /**
     * @var array
     */
    private $hints;
    /**
     * @var string
     */
    private $singleIdentifierName;

    /**
     * @param string $class
     * @param string $singleIdentifierName
     */
    public function __construct($class, $singleIdentifierName, array $data, array $hints = [])
    {
        $this->class = $class;
        $this->data = $data;
        $this->hints = $hints;
        $this->singleIdentifierName = $singleIdentifierName;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getHints()
    {
        return $this->hints;
    }

    /**
     * @return string
     */
    public function getSingleIdentifierName()
    {
        return $this->singleIdentifierName;
    }
}
