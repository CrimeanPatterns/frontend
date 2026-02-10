<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\LoungeNormalized;
use AwardWallet\MainBundle\Service\Lounge\Predictor\Normalizer;
use AwardWallet\Tests\Unit\BaseContainerTest;

abstract class AbstractComparatorTest extends BaseContainerTest
{
    private ?Normalizer $normalizer;

    public function _before()
    {
        parent::_before();

        $this->normalizer = $this->container->get(Normalizer::class);
    }

    public function _after()
    {
        $this->normalizer = null;

        parent::_after();
    }

    protected function normalize(LoungeInterface $lounge): LoungeNormalized
    {
        return $this->normalizer->normalize($lounge);
    }

    protected static function lounge(
        string $name,
        ?string $terminal = null,
        ?string $gate1 = null,
        ?string $gate2 = null
    ): Lounge {
        return (new Lounge())
            ->setAirportCode('JFK')
            ->setName($name)
            ->setTerminal($terminal)
            ->setGate($gate1)
            ->setGate2($gate2);
    }
}
