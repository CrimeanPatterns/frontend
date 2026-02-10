<?php

namespace AwardWallet\MainBundle\Globals\Paginator;

abstract class AbstractPagination implements \Countable, \Iterator, \ArrayAccess
{
    protected $currentPageNumber;
    protected $numItemsPerPage;
    protected $items = [];
    protected $totalCount;
    protected $paginatorOptions;
    protected $customParameters = [];

    public function rewind()
    {
        reset($this->items);
    }

    public function current()
    {
        return current($this->items);
    }

    public function key()
    {
        return key($this->items);
    }

    public function next()
    {
        next($this->items);
    }

    public function valid()
    {
        return key($this->items) !== null;
    }

    public function count()
    {
        return count($this->items);
    }

    public function setCustomParameters(array $parameters)
    {
        $this->customParameters = $parameters;
    }

    public function getCustomParameter($name)
    {
        return $this->customParameters[$name] ?? null;
    }

    public function setCurrentPageNumber($pageNumber)
    {
        $this->currentPageNumber = $pageNumber;
    }

    /**
     * Get currently used page number.
     *
     * @return int
     */
    public function getCurrentPageNumber()
    {
        return $this->currentPageNumber;
    }

    public function setItemNumberPerPage($numItemsPerPage)
    {
        $this->numItemsPerPage = $numItemsPerPage;
    }

    /**
     * Get number of items per page.
     *
     * @return int
     */
    public function getItemNumberPerPage()
    {
        return $this->numItemsPerPage;
    }

    public function setTotalItemCount($numTotal)
    {
        $this->totalCount = $numTotal;
    }

    /**
     * Get total item number available.
     *
     * @return int
     */
    public function getTotalItemCount()
    {
        return $this->totalCount;
    }

    public function setPaginatorOptions($options)
    {
        $this->paginatorOptions = $options;
    }

    /**
     * Get pagination alias.
     *
     * @return string
     */
    public function getPaginatorOption($name)
    {
        return $this->paginatorOptions[$name] ?? null;
    }

    public function setItems($items)
    {
        if (!is_array($items) && !$items instanceof \Traversable) {
            throw new \UnexpectedValueException("Items must be an array type");
        }
        $this->items = $items;
    }

    /**
     * Get current items.
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }
}
