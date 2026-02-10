<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Kind;

use AwardWallet\MainBundle\Manager\AccountList\Classes\AbstractConverter;

/**
 * Class Converter.
 *
 * @property object $entity
 * @method object getEntity()
 */
class Converter extends AbstractConverter
{
    /**
     * @var string
     */
    public $type = 'Kind';

    /**
     * @var int
     */
    public $order;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $items;

    public function __construct($kind, ?Builder $builder = null)
    {
        $this->entity = $kind;
        $this->builder = $builder;

        $this->id = $this->entity->id;
        $this->order = $this->entity->order;
        $this->name = $this->entity->name;
        $this->items = $this->entity->items;
    }
}
