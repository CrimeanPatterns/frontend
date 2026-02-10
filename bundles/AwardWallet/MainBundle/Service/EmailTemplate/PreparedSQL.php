<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class PreparedSQL
{
    /**
     * @var string
     */
    private $sql;

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var array
     */
    private $types = [];

    public function __construct($sql, $params = [], $types = [])
    {
        $this->setSql($sql);
        $this->setParams($params);
        $this->setTypes($types);
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param string $sql
     * @return PreparedSQL
     */
    public function setSql($sql)
    {
        $this->sql = $sql;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return PreparedSQL
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param array $types
     * @return PreparedSQL
     */
    public function setTypes($types)
    {
        $this->types = $types;

        return $this;
    }
}
