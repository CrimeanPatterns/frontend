<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Entity\Alliance;
use AwardWallet\MainBundle\Entity\Allianceelitelevel;
use AwardWallet\MainBundle\Manager\AccountList\Classes\AbstractResolver;
use AwardWallet\MainBundle\Manager\AccountList\Classes\ConverterInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class AllianceResolver.
 *
 * @property Converter[] $items
 */
class AllianceResolver extends AbstractResolver
{
    /**
     * @var AllianceProperty[]
     */
    private $alliances = [];

    public function __construct(
        EntityManager $em
    ) {
        $this->allianceRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Alliance::class);
        $this->levelsRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Allianceelitelevel::class);
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
            if ($item->getEntity()->getAllianceid()) {
                $id = $item->getEntity()->getAllianceid()->getAllianceid();

                if (array_key_exists($id, $this->alliances)) {
                    $item->setAlliance($this->alliances[$id]);
                } else {
                    if (!array_key_exists($id, $unresolved)) {
                        $unresolved[$id] = [];
                    }
                    $unresolved[$id][] = $item;
                }
            }
        }

        if (count($unresolved)) {
            $ids = array_keys($unresolved);
            /* @var Alliance[] */
            $alliances = $this->allianceRep->findBy(['allianceid' => $ids]);
            /* @var Allianceelitelevel[] */
            $levels = $this->levelsRep->findBy(['allianceid' => $ids]);

            foreach ($alliances as $alliance) {
                $id = $alliance->getAllianceid();
                $this->alliances[$id] = new AllianceProperty($alliance);

                foreach ($unresolved[$id] as $item) {
                    /* @var $item Converter */
                    $item->setAlliance($this->alliances[$id]);
                }
            }
        }
        $this->items = [];
    }
}
