<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Manager\AccountList\Classes\AbstractBuilder;
use AwardWallet\MainBundle\Manager\AccountList\Classes\ConverterInterface;
use AwardWallet\MainBundle\Manager\AccountList\Classes\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Builder.
 *
 * @property Converter[] $items
 */
class Builder extends AbstractBuilder
{
    public function __construct(
        ContainerInterface $container
    ) {
        parent::__construct($container);

        $this->resolvers = [
            'access',       /* @see AccessResolver */
            'accounts',     /* @see AccountsResolver */
            'stats',        /* @see StatsResolver */
            'alliance',     /* @see AllianceResolver */
            'elitelevels',  /* @see EliteLevelsResolver */
        ];

        $this->builderPrefix = 'provider';
    }

    public function set($items)
    {
        $this->items = [];

        if (is_array($items) || $items instanceof \Traversable) {
            foreach ($items as $item) {
                if ($item instanceof Provider) {
                    $this->items[] = new Converter($item, $this);
                }
            }
        }
    }

    /**
     * @param array|ResolverInterface|string $resolvers
     * @return ConverterInterface[]
     */
    public function build($resolvers = null)
    {
        $ret = parent::build($resolvers);
        $ret = array_merge($ret, $this->container->get('aw.account_list.builder.kind')->get());

        return $ret;
    }

    /**
     * @return ConverterInterface[]
     */
    public function buildAccountAdd()
    {
        return $this->build([
            'access',
            'accounts',
            'stats',
        ]);
    }

    /**
     * @return ConverterInterface[]
     */
    public function buildAccountList()
    {
        return $this->build([
            'access',
            'alliance',
            'elitelevels',
        ]);
    }
}
