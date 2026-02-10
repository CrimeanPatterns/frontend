<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Classes;

abstract class AbstractConverter implements ConverterInterface
{
    public $id;

    public $type = 'Abstract';

    protected $entity;

    /**
     * @var BuilderInterface
     */
    protected $builder;

    public function getEntity()
    {
        return $this->entity;
    }

    public function remove()
    {
        if ($this->builder) {
            $this->builder->remove($this);
        }
        $this->id = 0;
    }
}
