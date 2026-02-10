<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Service\Lounge\Finder;
use AwardWallet\Tests\Modules\DbBuilder\Lounge as DbLounge;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class FinderTest extends BaseContainerTest
{
    private ?Finder $finder;

    public function _before()
    {
        parent::_before();

        $this->finder = $this->container->get(Finder::class);
        $this->db->executeQuery("DELETE FROM Lounge WHERE AirportCode = 'ABC'");
        $this->db->executeQuery("DELETE FROM LoungeSource WHERE AirportCode = 'ABC'");
    }

    public function _after()
    {
        $this->finder = null;

        parent::_after();
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(array $expectedLounges, array $dbLounges)
    {
        foreach ($dbLounges as $dbLounge) {
            $this->dbBuilder->makeLounge($dbLounge);
        }

        $this->assertEquals(count($expectedLounges), $this->finder->getNumberAirportLounges('ABC'));
        $lounges = $this->finder->getLounges('ABC');
        $this->assertEquals(count($expectedLounges), count($lounges));
        $lounges = array_map(fn (Lounge $lounge) => $lounge->getName(), $lounges);
        sort($lounges);
        sort($expectedLounges);
        $this->assertEquals($expectedLounges, $lounges);
    }

    public function dataProvider(): array
    {
        return [
            'one lounge' => [
                ['Test 1'],
                [new DbLounge('ABC', 'Test 1')],
            ],
            'not available' => [
                [],
                [new DbLounge('ABC', 'Test 1', ['IsAvailable' => false])],
            ],
            'not visible' => [
                [],
                [new DbLounge('ABC', 'Test 1', ['IsAvailable' => true, 'Visible' => false])],
            ],
            'not valid, not available' => [
                [],
                [new DbLounge('ABC', 'Test 1', ['IsAvailable' => false, 'Visible' => false])],
            ],
            'multiple lounges' => [
                ['Test 3', 'Test 4'],
                [
                    new DbLounge('ABC', 'Test 1', ['IsAvailable' => true, 'Visible' => false]),
                    new DbLounge('ABC', 'Test 2', ['IsAvailable' => false, 'Visible' => true]),
                    new DbLounge('ABC', 'Test 3', ['IsAvailable' => true, 'Visible' => true]),
                    new DbLounge('ABC', 'Test 4', ['IsAvailable' => true, 'Visible' => true]),
                ],
            ],
        ];
    }
}
