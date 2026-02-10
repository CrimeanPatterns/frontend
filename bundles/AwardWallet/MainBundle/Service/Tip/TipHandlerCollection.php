<?php

namespace AwardWallet\MainBundle\Service\Tip;

use AwardWallet\MainBundle\Service\Tip\Definition\TipDefinitionInterface;

/**
 * Work with classes marked as "tags: [tip.handler]".
 */
class TipHandlerCollection
{
    /**
     * @var TipDefinitionInterface[]
     */
    private $collection = [];

    public function __construct(iterable $tipDefinitions)
    {
        $this->collection = $tipDefinitions;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Search by constant ELEMENT in classes \Definition.
     *
     * @return mixed|null
     */
    public function findByElement($elementName)
    {
        /** @var TipDefinitionInterface $item */
        foreach ($this->collection as $item) {
            if ($elementName === $item->getElementId()) {
                return $item;
            }
        }

        return null;
    }

    public function getElements()
    {
        $result = [];

        foreach ($this->collection as $item) {
            $result[] = $item->getElementId();
        }

        return $result;
    }
}
