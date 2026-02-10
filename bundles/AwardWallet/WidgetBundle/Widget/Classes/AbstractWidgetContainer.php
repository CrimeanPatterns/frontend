<?php

namespace AwardWallet\WidgetBundle\Widget\Classes;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractWidgetContainer extends AbstractWidget implements WidgetContainerInterface, \ArrayAccess, \Iterator, \Countable
{
    /**
     * @var WidgetInterface[]
     */
    protected $widgets;

    /**
     * @var WidgetInterface[]
     */
    protected $widgetsHash;

    public function __construct()
    {
        parent::__construct();
        $this->widgets = [];
        $this->widgetsHash = [];
    }

    public function setContainer(?ContainerInterface $container = null)
    {
        parent::setContainer($container);

        foreach ($this->widgets as $widget) {
            $widget->setContainer($container);
        }
    }

    public function isVisible()
    {
        if (!parent::isVisible()) {
            return false;
        }

        if (!$this->count()) {
            return false;
        }
        $visible = false;

        foreach ($this->widgets as $widget) {
            $visible = $visible || $widget->isVisible();
        }

        return $visible;
    }

    public function addItem(WidgetInterface $widget, $key = null)
    {
        $this->widgets[] = $widget;

        if (!is_empty($key) && is_string($key)) {
            $this->widgetsHash[$key] = $widget;
        }
    }

    public function moveItem(WidgetInterface $widget, $position = null)
    {
        $key = array_search($widget, $this->widgets);

        if ($key === false) {
            return;
        }

        if ($position instanceof WidgetInterface) {
            $position = array_search($position, $this->widgets);

            if ($position === false) {
                $position = count($this->widgets) - 1;
            }
        } elseif (is_string($position)) {
            if ($position == 'top' || $position == 'first') {
                $position = 0;
            } else {
                $position = count($this->widgets) - 1;
            }
        }

        if (is_int($position)) {
            array_splice($this->widgets, $key, 1);
            array_splice($this->widgets, $position, 0, [$widget]);
        }
    }

    public function deleteItem($offset)
    {
        $items = array_splice($this->widgets, $offset, 1);

        if (count($items)) {
            $item = $items[0];

            if ($key = array_search($item, $this->widgetsHash)) {
                unset($this->widgetsHash[$key]);
            }
        }
    }

    /**
     * @return array|WidgetInterface[]
     */
    public function getItems()
    {
        return $this->widgets;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->widgets) || array_key_exists($offset, $this->widgetsHash);
    }

    /**
     * @return WidgetInterface
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            if (!is_string($offset) && array_key_exists($offset, $this->widgets)) {
                return $this->widgets[$offset];
            } else {
                return $this->widgetsHash[$offset];
            }
        }

        return null;
    }

    /**
     * @param WidgetInterface $value
     * @throws \Exception
     */
    public function offsetSet($offset, $value)
    {
        if (!($value instanceof WidgetInterface)) {
            throw new \Exception('WidgetCollection accept only WidgetInterface items');
        }

        if (is_string($offset)) {
            $this->widgetsHash[$offset] = $value;

            if (!array_search($value, $this->widgets)) {
                $this->widgets[] = $value;
            }
        } else {
            if (array_key_exists($offset, $this->widgets) && $key = array_search($this->widgets[$offset], $this->widgetsHash)) {
                $this->widgetsHash[$key] = $value;
            }
            $this->widgets[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        if (array_key_exists($offset, $this->widgets) && $key = array_search($this->widgets[$offset], $this->widgetsHash)) {
            unset($this->widgetsHash[$key]);
        }
        unset($this->widgets[$offset]);
    }

    /**
     * @return WidgetInterface
     */
    public function current()
    {
        return current($this->widgets);
    }

    public function next()
    {
        next($this->widgets);
    }

    public function key()
    {
        return key($this->widgets);
    }

    public function valid()
    {
        return $this->current() !== false;
    }

    public function rewind()
    {
        reset($this->widgets);
    }

    public function count()
    {
        return count($this->widgets);
    }

    public function setActiveItem($offset)
    {
        $activeWidget = $this->offsetGet($offset);

        if (!empty($activeWidget)) {
            foreach ($this->widgets as $widget) {
                if (method_exists($widget, 'setIsActive')) {
                    $widget->setIsActive(spl_object_hash($widget) == spl_object_hash($activeWidget));
                }
            }
        }
    }

    public function setActiveNone()
    {
        foreach ($this->widgets as $widget) {
            if (method_exists($widget, 'setIsActive')) {
                $widget->setIsActive(false);
            }
        }
    }
}
