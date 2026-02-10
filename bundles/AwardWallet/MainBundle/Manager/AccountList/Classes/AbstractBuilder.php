<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Classes;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractBuilder implements BuilderInterface
{
    /**
     * Data set.
     *
     * @var ConverterInterface[]
     */
    protected $items;

    /**
     * Allowed resolvers.
     *
     * @var string[]
     */
    protected $resolvers = [];

    /**
     * Resolver services cache.
     *
     * @var ResolverInterface[]
     */
    protected $resolverServices = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $servicePrefix = 'aw.account_list.resolver';

    /**
     * @var string
     */
    protected $builderPrefix = 'abstract';

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    final public function getResolver($resolver)
    {
        if ($resolver instanceof ResolverInterface) {
            return $resolver;
        }

        if ($this->builderPrefix && strpos($resolver, $this->builderPrefix . '.') === 0) {
            $resolver = substr($resolver, strlen($this->builderPrefix) + 1);
        }

        if (!in_array($resolver, $this->resolvers)) {
            throw new UnknownResolverException();
        }

        if (!array_key_exists($resolver, $this->resolverServices)) {
            $this->resolverServices[$resolver] = $this->container->get($this->servicePrefix . '.' . $this->builderPrefix . '.' . $resolver);
        }

        return $this->resolverServices[$resolver];
    }

    public function resolve($resolver)
    {
        $resolver = $this->getResolver($resolver);

        if ($resolver->isEmpty()) {
            $resolver->set($this->items);
        }

        $resolver->resolve();

        if ($resolver instanceof AccessResolverInterface) {
            $this->cleanItems();
        }
    }

    public function set($items)
    {
        $this->items = [];

        if (is_array($items) || $items instanceof \Traversable) {
            $this->items = $items;
        }
    }

    public function add($item)
    {
        $this->items[] = $item;
    }

    public function get()
    {
        $this->cleanItems();

        return $this->items;
    }

    public function remove($item)
    {
        if ($id = array_search($item, $this->items)) {
            array_splice($this->items, $id, 1);
        }
    }

    public function build($resolvers = null)
    {
        if (!is_array($resolvers)) {
            if (is_string($resolvers)) {
                $resolvers = array_map('trim', explode(',', $resolvers));
            } elseif (!empty($resolvers)) {
                $resolvers = [$resolvers];
            }
        }

        if (!empty($resolvers)) {
            foreach ($resolvers as $resolver) {
                $this->resolve($resolver);
            }
        }

        return $this->get();
    }

    /**
     * Clean up data from empty elements.
     */
    protected function cleanItems()
    {
        $this->items = array_filter($this->items, function (ConverterInterface $item) {return !empty($item->id); });
    }
}
