<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Classes;

interface BuilderInterface
{
    /**
     * Set entities for build.
     *
     * @param array $items
     * @return void
     */
    public function set($items);

    /**
     * Add entity to data set.
     *
     * @return void
     */
    public function add($item);

    /**
     * Remove element from data set.
     *
     * @param ConverterInterface $item
     * @return void
     */
    public function remove($item);

    /**
     * Return result set.
     *
     * @return ConverterInterface[]
     */
    public function get();

    /**
     * Return resolver by ID.
     *
     * @param string|ResolverInterface $resolver
     * @return ResolverInterface
     * @throws UnknownResolverException
     */
    public function getResolver($resolver);

    //	public function getResolvers();

    /**
     * Apply resolver to data set.
     *
     * @param string|ResolverInterface $resolver
     * @return void
     */
    public function resolve($resolver);

    /**
     * Apply resolvers to data set, and return result set.
     *
     * @param string|array|ResolverInterface $resolvers
     * @return ConverterInterface[]
     */
    public function build($resolvers);
}
