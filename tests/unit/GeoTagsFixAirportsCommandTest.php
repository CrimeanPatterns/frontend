<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Command\GeoTagsFixAirportsCommand;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group frontend-unit
 */
class GeoTagsFixAirportsCommandTest extends BaseContainerTest
{
    /**
     * @dataProvider fixAirportsDataProvider
     */
    public function testFixAirports($dryRun)
    {
        $app = new Application($this->container->get('kernel'));
        $app->add(new GeoTagsFixAirportsCommand(
            $this->container->get(Connection::class)
        ));

        /** @var GeoTagsFixAirportsCommand $command */
        $command = $app->find('aw:geotags:fixairports');
        $commandTester = new CommandTester($command);

        $this->db->haveInDatabase('AirCode', [
            'AirCode' => '__X',
            'Lat' => 20,
            'Lng' => 20,
        ]);
        $this->db->haveInDatabase('AirCode', [
            'AirCode' => '__Y',
            'Lat' => 20,
            'Lng' => 20,
        ]);

        $validTags = [];
        $validTags[] = $this->db->haveInDatabase('GeoTag', [
            'Address' => '__X',
            'AddressLine' => 'Valid',
            'Lat' => 20,
            'Lng' => 20,
        ]);

        $invalidTags = [];
        $invalidTags[] = $this->db->haveInDatabase('GeoTag', [
            'Address' => '__Y',
            'AddressLine' => 'Invalid',
            'Lat' => 30,
            'Lng' => 30,
        ]);

        $commandTester->execute([
            'command' => $command->getName(),
            '--dry-run' => $dryRun,
            '--min-dist' => 50,
        ]);

        foreach ($invalidTags as $invalidTag) {
            if ($dryRun) {
                $this->db->seeInDatabase('GeoTag', ['GeoTagID' => $invalidTag]);
            } else {
                $this->db->dontSeeInDatabase('GeoTag', ['GeoTagID' => $invalidTag]);
            }
        }

        foreach ($validTags as $validTag) {
            $this->db->seeInDatabase('GeoTag', ['GeoTagID' => $validTag]);
        }
    }

    public function fixAirportsDataProvider()
    {
        return [
            [true],
            [false],
        ];
    }
}
