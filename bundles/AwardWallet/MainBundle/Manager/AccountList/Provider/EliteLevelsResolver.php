<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Entity\Elitelevel;
use AwardWallet\MainBundle\Manager\AccountList\Classes\AbstractResolver;
use AwardWallet\MainBundle\Manager\AccountList\Classes\ConverterInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class EliteLevelsResolver.
 *
 * @property Converter[] $items
 */
class EliteLevelsResolver extends AbstractResolver
{
    /**
     * @var EliteLevelProperty[][]
     */
    private $levels = [];

    public function __construct(
        EntityManager $em
    ) {
        $this->elitelevelRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class);
    }

    public function add(ConverterInterface $item)
    {
        if ($item instanceof Converter) {
            $this->items[] = $item;
        }
    }

    public function resolve()
    {
        $unresolved = [];

        foreach ($this->items as $item) {
            if ($item->getEntity()->getElitelevelscount()) {
                $id = $item->getEntity()->getProviderid();

                if (array_key_exists($id, $this->levels)) {
                    $item->setEliteLevels($this->levels[$id]);
                } else {
                    $unresolved[$id] = $item;
                }
            }
        }

        if (count($unresolved)) {
            $ids = array_keys($unresolved);
            /* @var EliteLevel[] */
            $levels = $this->elitelevelRep->findBy(['providerid' => $ids]);

            foreach ($levels as $level) {
                $id = $level->getProviderid()->getProviderid();
                $this->levels[$id][] = new EliteLevelProperty($level);
            }

            foreach ($unresolved as $id => $item) {
                if (array_key_exists($id, $this->levels)) {
                    if (count($this->levels[$id])) {
                        /** @var Converter $item */
                        $item->setEliteLevels($this->levels[$id]);
                    }
                } else {
                    // todo levelscount > 0 && count(elitelevels) == 0 ???
                    $this->levels[$id] = [];
                }
            }
        }
        $this->items = [];
    }
}
