<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\Tests\Unit\BaseContainerTest;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

abstract class AbstractClustererTest extends BaseContainerTest
{
    protected function source(
        string $name,
        string $code,
        string $airport,
        ?string $terminal = null,
        ?string $gate1 = null,
        ?string $gate2 = null
    ): LoungeSource {
        return (new LoungeSource())
            ->setName($name)
            ->setSourceCode($code)
            ->setAirportCode($airport)
            ->setTerminal($terminal)
            ->setGate($gate1)
            ->setGate2($gate2);
    }

    protected function lounge(
        string $name,
        string $airport,
        ?string $terminal = null,
        ?string $gate1 = null,
        ?string $gate2 = null,
        ?int $id = null
    ): Lounge {
        $lounge = (new Lounge())
            ->setName($name)
            ->setAirportCode($airport)
            ->setTerminal($terminal)
            ->setGate($gate1)
            ->setGate2($gate2);

        if (!is_null($id)) {
            $reflection = new \ReflectionClass($lounge);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($lounge, $id);
        }

        return $lounge;
    }

    protected function mapLounges(array $groups): array
    {
        return it($groups)
            ->map(function (array $group) {
                return it($group)
                    ->map(function (LoungeInterface $lounge) {
                        return (string) $lounge;
                    })
                    ->sort()
                    ->toArray();
            })
            ->toArray();
    }
}
