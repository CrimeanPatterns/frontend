<?php

namespace AwardWallet\MainBundle\Service\FlightSearch;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\FlightSearch\Place\PlaceItem;

/**
 * @NoDI()
 */
class FormData
{
    /** @var array<{id: int, name: string}> */
    private array $types;

    /** @var array{id: int, name: string} */
    private $type;

    /** @var array<{id: int, name: string}> */
    private array $classes;

    /** @var array{id: int, name: string, list: array} */
    private array $class;

    private ?PlaceItem $from = null;
    private ?PlaceItem $to = null;

    public function __construct(array $types, array $classes, array $type, array $class)
    {
        $this->setTypes($types);
        $this->setType($type);
        $this->setClasses($classes);
        $this->setClass($class);
    }

    public function setTypes(array $types): self
    {
        $this->types = $types;

        return $this;
    }

    public function setType(array $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeId(): string
    {
        if (array_key_exists('id', $this->type)) {
            return $this->type['id'];
        }

        throw new \Exception('Undefined $type.id');
    }

    public function setClasses(array $classes): self
    {
        $this->classes = $classes;

        return $this;
    }

    public function getClassId(): string
    {
        if (array_key_exists('id', $this->class)) {
            return (string) $this->class['id'];
        }

        throw new \Exception('Undefined $class.list');
    }

    public function getClassList(): array
    {
        if (array_key_exists('list', $this->class)) {
            return $this->class['list'];
        }

        throw new \Exception('Undefined $class.list');
    }

    public function setClass(array $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function setFrom(PlaceItem $placeFrom): self
    {
        $this->from = $placeFrom;

        return $this;
    }

    public function getFrom(): ?PlaceItem
    {
        return $this->from;
    }

    public function setTo(PlaceItem $placeTo): self
    {
        $this->to = $placeTo;

        return $this;
    }

    public function getTo(): ?PlaceItem
    {
        return $this->to;
    }
}
