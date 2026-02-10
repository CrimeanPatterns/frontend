<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Kind;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Manager\AccountList\Classes\AbstractBuilder;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Builder extends AbstractBuilder
{
    /**
     * @var Translator
     */
    protected $translator;

    public function __construct(
        ContainerInterface $container
    ) {
        parent::__construct($container);

        $this->translator = $this->container->get('translator');

        $order = 1;

        foreach (Provider::getKinds() as $id => $name) {
            $kind = (object) [
                'id' => $id,
                'order' => $order++,
                'name' => $this->translator->trans(/** @Ignore */ $name),
                'items' => $name . '.items',
            ];
            $this->items[] = new Converter($kind);
        }
        //		return $providerKinds;
    }

    public function set($items)
    {
    }

    public function add($item)
    {
    }

    public function remove($item)
    {
    }

    public function build($resolvers = null)
    {
        return $this->get();
    }
}
