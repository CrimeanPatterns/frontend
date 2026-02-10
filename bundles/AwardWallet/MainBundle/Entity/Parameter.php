<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Parameter.
 *
 * @ORM\Table(name="Param")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\ParameterRepository")
 */
class Parameter
{
    /**
     * @var int
     * @ORM\Column(name="ParamID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $paramid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=40, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Val", type="string", length=250, nullable=true)
     */
    protected $val;

    /**
     * @var string
     * @ORM\Column(name="BigData", type="text", nullable=true)
     */
    protected $bigdata;

    /**
     * Get paramid.
     *
     * @return int
     */
    public function getParamid()
    {
        return $this->paramid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Param
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set val.
     *
     * @param string $val
     * @return Param
     */
    public function setVal($val)
    {
        if (strlen($val) > 250) {
            throw new \Exception("Too long value for Val");
        }
        $this->val = $val;

        return $this;
    }

    /**
     * Get val.
     *
     * @return string
     */
    public function getVal()
    {
        return $this->val;
    }

    /**
     * Set bigdata.
     *
     * @param string $text
     * @return Param
     */
    public function setBigdata($text)
    {
        $this->bigdata = $text;

        return $this;
    }

    /**
     * Get bigdata.
     *
     * @return string
     */
    public function getBigdata()
    {
        return $this->bigdata;
    }
}
