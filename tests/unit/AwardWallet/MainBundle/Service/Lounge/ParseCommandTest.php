<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\LoungeAction;
use AwardWallet\MainBundle\Service\Lounge\Action\FreezeAction;
use AwardWallet\MainBundle\Service\Lounge\Finder;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\ParseCommand;
use AwardWallet\Tests\Modules\DbBuilder\Lounge as DbLounge;
use AwardWallet\Tests\Modules\DbBuilder\LoungeSource as DBLoungeSource;

class ParseCommandTest extends AbstractParseCommandTest
{
    /**
     * @var ParseCommand
     */
    protected $command;

    protected ?Finder $finder;

    public function _before()
    {
        parent::_before();

        $this->finder = $this->container->get(Finder::class);
        $this->clearDb();
    }

    public function _after()
    {
        $this->finder = null;

        parent::_after();
    }

    public function testItsNotTimeForScan()
    {
        $this->runCommand([$this->createParser()], 10, null, false, false);
        $this->seeLoungesParsingStarts();
        $this->seeOptimalModeButNotTimeForScan();
    }

    public function testOptimalMode()
    {
        $parser = $this->createParser([
            $lounge = $this->source('$AA', 'Test A')->setIsAvailable(true),
        ]);

        // add lounge to db
        $this->dbBuilder->makeLoungeSource(
            new DBLoungeSource('$AA', 'Test A', $parser->getCode()),
        );
        $this->assertNotNull($found = $this->findLoungeSource(['airportCode' => '$AA', 'name' => 'Test A']));
        // save source id, because it will be changed after parsing
        $id = $found->getSourceId();

        // parser return 1 lounge
        $this->runCommand([$parser], 10);
        $this->seeOptimalMode(10);
        $this->logContains('total aircodes:');
        $this->logContains('+ new airports');
        $this->seeRetrievingData($parser);
        $this->seeLoungeWasNotFoundAndTryingFindSimilar($parser, $lounge);
        $this->seeSimilarLoungeWasFound($parser, $lounge, $found->setSourceId($id));
        $this->seeSavedLoungesInAirport($parser, '$AA', 1, 0);
        $this->seeClusters('$AA', 1);

        // check that the lounge was added
        $this->assertNotEmpty($lounge = $this->findLounge(['airportCode' => '$AA', 'name' => 'Test A', 'isAvailable' => 1, 'visible' => 1]));
        $this->db->seeInDatabase('LoungeSource', ['AirportCode' => '$AA', 'Name' => 'Test A', 'LoungeID' => $lounge->getId()]);
    }

    public function testOnlyOneAirport()
    {
        $this->runCommand([$parser = $this->createParser()], null, ['$AA']);
        $this->logContains('search for these airports: $AA');
        $this->logNotContains('+ new airports');
        $this->seeRetrievingData($parser);
        $this->seeDeletedLounges($parser, 0);
        $this->seeClusters('$AA', 0);
        $this->seeEndMerging(1);
    }

    public function testFromDb()
    {
        $this->runCommand([$parser = $this->createParser()], null, null, true);
        $this->seeSearchFromDB();
        $this->seeRetrievingData($parser);
        $this->seeEndMerging(0);
    }

    public function testFilterSource()
    {
        $parser = $this->createParser([
            $lounge = $this->source('$AA', 'Test A'),
        ]);
        $this->runCommand([$parser], null, ['$AA']);

        $this->seeLoungeWasNotFoundAndTryingFindSimilar($parser, $lounge);
        $this->seeCreateNewLounge($parser, $lounge);
        $this->seeSavedLoungesInAirport($parser, '$AA', 1, 1);
        $this->seeDeletedLoungesInAirport($parser, '$AA', 0);
        $this->seeMergedLounge($lounge->createLounge());

        $parser->setLounges([
            $lounge2 = $this->source('$BB', 'Test B'),
        ]);
        $this->runCommand([$parser], null, ['$BB']);
        $this->seeSavedLoungesInAirport($parser, '$BB', 1, 1);
        $this->seeMergedLounge($lounge2->createLounge());
        $this->seeEndMerging(1);
        $this->seeLoungesInDb(2);
    }

    public function testOverrideLoungeProperties()
    {
        // add lounge
        $parser = $this->createParser([
            $lounge = $this->source('$AA', 'Business Test A')
                ->setTerminal('T1')
                ->setGate(1)
                ->setGate2(null)
                ->setOpeningHours(new RawOpeningHours('Mon-Fri'))
                ->setIsAvailable(true)
                ->setLocation('Test location')
                ->setAdditionalInfo('Test additional info')
                ->setAmenities(null)
                ->setRules('Test rules')
                ->setPageBody('Test page body')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(null)
                ->setDragonPassAccess(true)
                ->setLoungeKeyAccess(false)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                ]),
        ]);
        $this->runCommand([$parser], null, ['$AA']);
        $this->seeCreateNewLounge($parser, $lounge);
        $this->seeSavedLoungesInAirport($parser, '$AA', 1, 1);
        $this->seeClusters('$AA', 1);
        $this->seeMergedLounge($lounge->createLounge());
        /** @var Lounge $merged */
        $merged = $this->findLounge(['airportCode' => '$AA', 'name' => 'Business Test A']);
        $this->assertEquals($lounge->getTerminal(), $merged->getTerminal());
        $this->assertEquals($lounge->getGate(), $merged->getGate());
        $this->assertNull($merged->getGate2());
        $this->assertInstanceOf(RawOpeningHours::class, $merged->getOpeningHours());
        $this->assertEquals('Mon-Fri', $merged->getOpeningHours()->getRaw());
        $this->assertTrue($merged->isAvailable());
        $this->assertEquals($lounge->getLocation(), $merged->getLocation());
        $this->assertEquals($lounge->getAdditionalInfo(), $merged->getAdditionalInfo());
        $this->assertNull($merged->getAmenities());
        $this->assertEquals($lounge->getRules(), $merged->getRules());
        $this->assertTrue($merged->isPriorityPassAccess());
        $this->assertNull($merged->isAmexPlatinumAccess());
        $this->assertTrue($merged->isDragonPassAccess());
        $this->assertFalse($merged->isLoungeKeyAccess());
        $this->assertEquals(1, $merged->getAirlines()->count());
        $this->assertEquals(0, $merged->getAlliances()->count());
        $this->assertTrue($merged->isVisible());

        // change properties
        $lounge = $this->source('$AA', 'Business Test Lounge')
            ->setSourceCode($parser->getCode())
            ->setSourceId($lounge->getSourceId())
            ->setName('Business Test Lounge')
            ->setTerminal('T1')
            ->setGate(null)
            ->setGate2(1)
            ->setOpeningHours(new RawOpeningHours('10-20'))
            ->setIsAvailable(null)
            ->setLocation('Test 2 location')
            ->setAdditionalInfo('Test 2 additional info')
            ->setAmenities('Test amenities')
            ->setRules(null)
            ->setPageBody('Test 2 page body')
            ->setPriorityPassAccess(false)
            ->setAmexPlatinumAccess(true)
            // not changed property
            ->setDragonPassAccess(true)
            ->setLoungeKeyAccess(null)
            ->setAirlines([
                $this->findAirline(['fsCode' => 'AA']),
                $this->findAirline(['fsCode' => 'OZ']),
            ])
            ->setAlliances([
                $this->findAlliance(['alias' => 'skyteam']),
            ]);
        $parser->setLounges([$lounge]);
        $this->runCommand([$parser], null, ['$AA']);
        $this->seeLoungeWasFound($parser, $lounge);
        $this->seeSavedLoungesInAirport($parser, '$AA', 1, 0);
        $this->seeClusters('$AA', 1);
        $this->seeMergedLounge($merged);
        $this->logContains('updated props: [Name, Gate, Gate2, OpeningHours, IsAvailable, Location, AdditionalInfo, Amenities, Rules, PriorityPassAccess, AmexPlatinumAccess, LoungeKeyAccess, Airlines, Alliances]');
        $this->em->refresh($merged);
        $this->assertEquals($lounge->getName(), $merged->getName());
        $this->assertEquals($lounge->getTerminal(), $merged->getTerminal());
        $this->assertNull($merged->getGate());
        $this->assertEquals($lounge->getGate2(), $merged->getGate2());
        $this->assertInstanceOf(RawOpeningHours::class, $merged->getOpeningHours());
        $this->assertEquals('10-20', $merged->getOpeningHours()->getRaw());
        $this->assertNull($merged->isAvailable());
        $this->assertEquals($lounge->getLocation(), $merged->getLocation());
        $this->assertEquals($lounge->getAdditionalInfo(), $merged->getAdditionalInfo());
        $this->assertEquals($lounge->getAmenities(), $merged->getAmenities());
        $this->assertNull($merged->getRules());
        $this->assertFalse($merged->isPriorityPassAccess());
        $this->assertTrue($merged->isAmexPlatinumAccess());
        $this->assertTrue($merged->isDragonPassAccess());
        $this->assertNull($merged->isLoungeKeyAccess());
        $this->assertEquals(2, $merged->getAirlines()->count());
        $this->assertEquals(1, $merged->getAlliances()->count());

        $lounge = $this->findLoungeSource(['airportCode' => '$AA', 'name' => 'Business Test Lounge']);
        $this->seeChangedPropertyInDb('Name', $lounge->getId());
        $this->seeChangedPropertyInDb('Gate2', $lounge->getId());
        $this->seeChangedPropertyInDb('OpeningHours', $lounge->getId());
        $this->seeChangedPropertyInDb('Location', $lounge->getId());
        $this->seeChangedPropertyInDb('AdditionalInfo', $lounge->getId());
        $this->seeChangedPropertyInDb('Amenities', $lounge->getId());
        $this->seeChangedPropertyInDb('PriorityPassAccess', $lounge->getId());
        $this->seeChangedPropertyInDb('AmexPlatinumAccess', $lounge->getId());
        $this->seeChangedPropertyInDb('Airlines', $lounge->getId());
        $this->seeChangedPropertyInDb('Alliances', $lounge->getId());
    }

    public function testOverrideLoungePropertiesFromMultipleSources()
    {
        $parser1 = $this->createParser([
            $lounge1 = $this->source('$AA', 'Super Business Lounge')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('Mon-Fri'))
                ->setIsAvailable(true)
                ->setLocation('Test location')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(null)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                ]),
        ]);
        $parser2 = $this->createParser([
            $lounge2 = $this->source('$AA', 'Super Business Lounge')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('10-20'))
                ->setIsAvailable(null)
                ->setLocation('Test 2 location')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(true)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                    $this->findAirline(['fsCode' => 'OZ']),
                ]),
        ]);
        $parser3 = $this->createParser([
            $lounge3 = $this->source('$AA', 'Super Business Lounge #1')
                ->setTerminal(2)
                ->setGate(8)
                ->setGate2(9)
                ->setDragonPassAccess(true)
                ->setIsAvailable(false),
        ]);

        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers = [
            $parser2, $parser1, $parser3,
        ], $patches = [
            'OpeningHours' => [$parser1],
            'DragonPassAccess' => [$parser3],
        ]));

        $this->seeCreateNewLounge($parser1, $lounge1);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 1);
        $this->seeCreateNewLounge($parser2, $lounge2);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 1);
        $this->seeCreateNewLounge($parser3, $lounge3);
        $this->seeSavedLoungesInAirport($parser3, '$AA', 1, 1);
        $this->seeClusters('$AA', 1);
        $this->seeCreateMergedLounge('$AA');
        $this->seeMergedLounge($lounge1->createLounge());
        $this->assertNotNull($merged = $this->findLounge(['airportCode' => '$AA', 'name' => 'Super Business Lounge']));
        $this->assertEquals($lounge2->getTerminal(), $merged->getTerminal());
        $this->assertEquals($lounge2->getGate(), $merged->getGate());
        $this->assertEquals($lounge2->getGate2(), $merged->getGate2());
        $this->assertEquals($lounge1->getOpeningHours(), $merged->getOpeningHours());
        $this->assertEquals($lounge2->isAvailable(), $merged->isAvailable());
        $this->assertEquals($lounge2->getLocation(), $merged->getLocation());
        $this->assertEquals($lounge2->isPriorityPassAccess(), $merged->isPriorityPassAccess());
        $this->assertEquals($lounge2->isAmexPlatinumAccess(), $merged->isAmexPlatinumAccess());
        $this->assertEquals($lounge3->isDragonPassAccess(), $merged->isDragonPassAccess());
        $this->assertCount(\count($lounge2->getAirlines()), $merged->getAirlines());

        // update properties
        $parser2->setLounges([
            (clone $lounge2)
                ->setName('Super Business Lounge #2')
                ->setGate('A8')
                ->setOpeningHours(null)
                ->setIsAvailable(false)
                ->setLocation('Test 3 location'),
        ]);
        $parser3->setLounges([
            (clone $lounge3)
                ->setIsAvailable(true),
        ]);
        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeLoungeWasFound($parser1, $lounge1);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 0);
        $this->seeLoungeWasFound($parser2, $lounge2);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 0);
        $this->seeLoungeWasFound($parser3, $lounge3);
        $this->seeSavedLoungesInAirport($parser3, '$AA', 1, 0);
        $this->seeClusters('$AA', 1);
        $this->seeMergedLounge($merged);
        $this->logContains('updated props: [Name, Gate, IsAvailable, Location]');
        $this->assertEquals($lounge2->getName(), $merged->getName());
        $this->assertEquals($lounge2->getGate(), $merged->getGate());
        $this->assertEquals($lounge1->getOpeningHours(), $merged->getOpeningHours());
        $this->assertEquals($lounge2->isAvailable(), $merged->isAvailable());

        // update visible lounge
        $merged->setVisible(false);
        $this->em->flush();
        $parser2->setLounges([
            (clone $lounge2)
                ->setIsAvailable(true),
        ]);
        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeLoungeWasFound($parser1, $lounge1);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 0);
        $this->seeLoungeWasFound($parser2, $lounge2);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 0);
        $this->seeLoungeWasFound($parser3, $lounge3);
        $this->seeSavedLoungesInAirport($parser3, '$AA', 1, 0);
        $this->seeClusters('$AA', 1);
        $this->seeMergedLounge($merged);
        $this->logContains('updated props: [IsAvailable]');
        $this->assertEquals($lounge2->isAvailable(), $merged->isAvailable());
        $this->assertTrue($merged->isVisible());

        $merged->setCheckedBy($this->user);
        $merged->setVisible(false);
        $this->em->flush();
        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeClusters('$AA', 1);
        $this->seeMergedLounge($merged);
        $this->logContains('updated props: none');
        $this->assertFalse($merged->isVisible());

        // freeze property
        $action = new LoungeAction();
        $action->setAction(new FreezeAction(['IsAvailable'], [], true));
        $action->setLounge($merged);
        $merged->setActions([$action]);
        $this->em->flush();
        $parser2->setLounges([
            (clone $lounge2)
                ->setIsAvailable(false),
        ]);
        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeClusters('$AA', 1);
        $this->seeFreezedProperty($merged, 'IsAvailable');
        $this->seeMergedLounge($merged);
        $this->logContains('updated props: none');
        $this->logContains('send slack notification');
        $this->db->seeInDatabase('Lounge', ['LoungeID' => $merged->getId(), 'IsAvailable' => 1]);

        sleep(1);
        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeClusters('$AA', 1);
        $this->logNotContains('freeze action');
        $this->seeMergedLounge($merged);
        $this->logContains('updated props: none');
    }

    public function testMarkAsDeleteLounges()
    {
        $parser1 = $this->createParser([
            $lounge11 = $this->source('$AA', 'Business Lounge')
                ->setTerminal(2)
                ->setGate(10)
                ->setOpeningHours(new RawOpeningHours('Mon-Fri'))
                ->setIsAvailable(true)
                ->setLocation('Test location')
                ->setPriorityPassAccess(true)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                ]),
            $lounge12 = $this->source('$AA', 'Economy Lounge')
                ->setTerminal(1)
                ->setIsAvailable(true)
                ->setLocation('Economy location')
                ->setDragonPassAccess(true),
            $lounge13 = $this->source('$BB', 'YYY Lounge')
                ->setGate(2)
                ->setIsAvailable(true)
                ->setPriorityPassAccess(true),
        ]);
        $parser2 = $this->createParser([
            $lounge21 = $this->source('$AA', 'Super Business Lounge')
                ->setTerminal(2)
                ->setGate(10)
                ->setOpeningHours(new RawOpeningHours('10-20'))
                ->setIsAvailable(null)
                ->setLocation('Test 2 location')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(true)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                    $this->findAirline(['fsCode' => 'OZ']),
                ]),
        ]);
        $this->runCommand([$parser1, $parser2], null, ['$AA', '$BB'], false, true, $this->createPriorityManager($parsers = [
            $parser2, $parser1,
        ], $patches = [
            'IsAvailable' => [$parser1],
        ]));
        $this->seeCreateNewLounge($parser1, $lounge11);
        $this->seeCreateNewLounge($parser1, $lounge12);
        $this->seeCreateNewLounge($parser1, $lounge13);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 2, 2);
        $this->seeSavedLoungesInAirport($parser1, '$BB', 1, 1);
        $this->seeCreateNewLounge($parser2, $lounge21);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 1);
        $this->seeClusters('$AA', 2);
        $this->seeCreateMergedLounge('$AA');
        $this->seeMergedLounge($lounge11->createLounge());
        $this->seeMergedLounge($lounge12->createLounge());
        $this->seeClusters('$BB', 1);
        $this->seeMergedLounge($lounge21->createLounge());
        $this->seeEndMerging(2);
        $this->seeLoungesInDb(3);
        $this->assertEquals(3, $this->db->grabCountFromDatabase('LoungeSource', ['AirportCode' => '$AA', 'DeleteDate' => null]));
        $this->assertEquals(1, $this->db->grabCountFromDatabase('LoungeSource', ['AirportCode' => '$BB', 'DeleteDate' => null]));

        // auto visible lounges
        $this->assertEquals(2, $this->finder->getNumberAirportLounges('$AA'));
        $this->assertEquals(2, $this->db->grabCountFromDatabase('Lounge', ['AirportCode' => '$AA', 'IsAvailable' => 1]));
        $this->assertEquals(1, $this->finder->getNumberAirportLounges('$BB'));
        $this->assertEquals(1, $this->db->grabCountFromDatabase('Lounge', ['AirportCode' => '$BB', 'IsAvailable' => 1]));

        // simulate a broken parser, one lounge was not found
        $parser1->setLounges([
            clone $lounge11,
            clone $lounge13,
        ]);
        $this->runCommand([$parser1, $parser2], null, ['$AA', '$BB'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 0);
        $this->seeDeletedLoungesInAirport($parser1, '$AA', 1);
        $this->seeSavedLoungesInAirport($parser1, '$BB', 1, 0);
        $this->seeDeletedLoungesInAirport($parser1, '$BB', 0);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 0);
        $this->seeDeletedLoungesInAirport($parser2, '$AA', 0);
        $this->seeClusters('$AA', 2);
        $this->seeClusters('$BB', 1);
        $this->seeLoungesInDb(3);
        $this->assertNotNull($loungeSource = $this->findLoungeSource(['airportCode' => '$AA', 'name' => $lounge12->getName()]));
        $this->assertNotNull($loungeSource->getDeleteDate());
        $this->assertNotNull($merged = $this->findLounge(['airportCode' => '$AA', 'name' => $lounge12->getName()]));
        $this->assertFalse($merged->isAvailable());

        // parser is fixed
        $parser1->setLounges([
            clone $lounge11,
            clone $lounge12,
            clone $lounge13,
        ]);
        $this->runCommand([$parser1, $parser2], null, ['$AA', '$BB'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeSavedLoungesInAirport($parser1, '$AA', 2, 0);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 0);
        $this->assertNull($loungeSource->getDeleteDate());
        $this->assertTrue($merged->isAvailable());

        // parser does not return one airport
        $parser1->setLounges([
            clone $lounge11,
            clone $lounge12,
        ]);
        $this->runCommand([$parser1, $parser2], null, ['$AA', '$BB'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeSavedLoungesInAirport($parser1, '$AA', 2, 0);
        $this->seeDeletedLounges($parser1, 1);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 0);
        $this->seeClusters('$AA', 2);
        $this->seeClusters('$BB', 1);
        $this->seeLoungesInDb(3);
        $this->assertNotNull($merged = $this->findLounge(['airportCode' => '$BB', 'name' => $lounge13->getName()]));
        $this->assertNotNull($loungeSource = $this->findLoungeSource(['airportCode' => '$BB', 'name' => $lounge13->getName()]));
        $this->seeMergedLounge($merged);
        $this->logContains('updated props: [IsAvailable]');
        $this->assertNotNull($loungeSource->getDeleteDate());
        $this->assertFalse($merged->isAvailable());

        // delete from db
        $this->em->remove($loungeSource);
        $this->em->flush();
        $parser1->setLounges([
            clone $lounge11,
            clone $lounge12,
        ]);
        $merged = clone $merged;
        $this->runCommand([$parser1, $parser2], null, ['$AA', '$BB'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeSavedLoungesInAirport($parser1, '$AA', 2, 0);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 0);
        $this->seeClusters('$AA', 2);
        $this->seeClusters('$BB', 1);
        $this->seeLoungesInDb(2);
        $this->seeDeleteLoungeWithoutSources($merged);

        // all parsers are broken
        $parser1->setLounges([]);
        $parser2->setLounges([]);
        $this->runCommand([$parser1, $parser2], null, ['$AA', '$BB'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->dontSeeNoAffectedAirportsFound($parser1);
        $this->seeDeletedLounges($parser1, 2);
        $this->dontSeeNoAffectedAirportsFound($parser2);
        $this->seeDeletedLounges($parser2, 1);
        $this->assertEquals(3, $this->db->query("SELECT COUNT(*) FROM LoungeSource WHERE AirportCode IN ('\$AA', '\$BB') AND DeleteDate IS NOT NULL")->fetchColumn());
        $this->seeClusters('$AA', 2);
        $this->seeClusters('$BB', 0);
        $this->seeEndMerging(2);
        $this->logContains('updated props: [IsAvailable]');
        $this->assertEquals(2, $this->db->query("SELECT COUNT(*) FROM Lounge WHERE AirportCode IN ('\$AA', '\$BB') AND IsAvailable = 0")->fetchColumn());
    }

    public function testPropertiesAreUpdatedInPriorityOrder()
    {
        $parser1 = $this->createParser([
            $lounge11 = $this->source('$AA', 'VIP Lounge')
                ->setTerminal(2)
                ->setGate(10)
                ->setIsAvailable(true)
                ->setLocation('Test location'),
        ]);
        $parser2 = $this->createParser([
            $lounge21 = $this->source('$AA', 'VIP Lounge')
                ->setTerminal(2)
                ->setGate(10)
                ->setIsAvailable(true)
                ->setLocation('Test location'),
        ]);
        $this->runCommand([$parser1, $parser2], null, ['$AA'], false, true, $this->createPriorityManager($parsers = [
            $parser1, $parser2,
        ], $patches = [
            'IsAvailable' => [$parser1, $parser2],
        ]));

        $this->seeCreateNewLounge($parser1, $lounge11);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 1);
        $this->seeCreateNewLounge($parser2, $lounge21);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 1);
        $this->seeClusters('$AA', 1);
        $this->seeCreateMergedLounge('$AA');
        $this->seeMergedLounge($lounge11->createLounge());
        $this->seeMergedLounge($lounge21->createLounge());
        $this->seeEndMerging(1);
        $this->seeLoungesInDb(1);
        $this->assertEquals(2, $this->db->grabCountFromDatabase('LoungeSource', ['AirportCode' => '$AA', 'DeleteDate' => null]));

        $this->assertEquals(1, $this->finder->getNumberAirportLounges('$AA'));
        $this->assertEquals(1, $this->db->grabCountFromDatabase('Lounge', ['AirportCode' => '$AA', 'IsAvailable' => 1]));
        $this->assertNotNull($merged = $this->findLounge(['airportCode' => '$AA', 'name' => $lounge21->getName()]));
        $this->assertTrue($merged->isAvailable());

        $parser2->setLounges([]);
        $this->runCommand([$parser1, $parser2], null, ['$AA'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->em->refresh($merged);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 0);
        $this->seeDeletedLounges($parser2, 1);
        $this->seeClusters('$AA', 1);

        $this->assertNotNull($loungeSource = $this->findLoungeSource(['airportCode' => '$AA', 'name' => $lounge21->getName(), 'sourceCode' => $parser2->getCode()]));
        $this->assertNotNull($loungeSource->getDeleteDate());
        $this->assertTrue($merged->isAvailable());
    }

    public function testLooksLikeParserIsBroken()
    {
        $parser1 = $this->createParser([
            $lounge11 = $this->source('$AA', 'Business Lounge')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('Mon-Fri'))
                ->setIsAvailable(true)
                ->setLocation('Test location')
                ->setPriorityPassAccess(true)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                ]),
        ]);
        $parser2 = $this->createParser([
            $lounge21 = $this->source('$AA', 'Business Lounge')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('Mon-Fri'))
                ->setIsAvailable(true)
                ->setLocation('Test location')
                ->setPriorityPassAccess(true)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                ]),
        ]);
        $this->runCommand([$parser1, $parser2], null, ['$AA'], false, true, $this->createPriorityManager($parsers = [
            $parser1, $parser2,
        ], []));
        $this->seeCreateNewLounge($parser1, $lounge11);
        $this->seeCreateNewLounge($parser2, $lounge21);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 1);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 1);
        $this->seeClusters('$AA', 1);
        $this->seeCreateMergedLounge('$AA');
        $this->seeMergedLounge($lounge11->createLounge());

        $parser1->setLounges([]);
        $this->runCommand([$parser1, $parser2], null, ['$AA'], false, true, $this->createPriorityManager($parsers, []));
        $this->dontSeeNoAffectedAirportsFound($parser1);
        $this->seeDeletedLounges($parser1, 1);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 0);
        $this->seeClusters('$AA', 1);
        $this->assertNotNull($merged = $this->findLounge(['airportCode' => '$AA', 'name' => $lounge11->getName()]));
        $this->seeProbablyLoungeIsAvailable($merged, $parser1);
    }

    public function testComplex1()
    {
        // Parser #1 return 2 lounges
        $parser1 = $this->createParser([
            $lounge11 = $this->source('$AA', 'Business Lounge')
                ->setAvailable(true)
                ->setTerminal('T2')
                ->setGate(8)
                ->setRules('Test rules'),
            $lounge12 = $this->source('$AA', 'Prime Lounge')
                ->setAvailable(true)
                ->setTerminal('T1')
                ->setGate(2)
                ->setRules('Random rules'),
        ]);
        $this->runCommand([$parser1], null, ['$AA']);
        $this->seeCreateNewLounge($parser1, $lounge11);
        $this->seeCreateNewLounge($parser1, $lounge12);
        $this->seeStartMerging();
        $this->seeClusters('$AA', 2);
        $this->seeMergedLounge($lounge11->createLounge());
        $this->seeMergedLounge($lounge12->createLounge());
        $this->seeEndMerging(1);
        // new lounges are auto visible
        $this->assertEquals(2, $this->finder->getNumberAirportLounges('$AA'));

        // Parser #1 return 3 lounges, 1 - new, 2 - update
        $parser1->addLounges([
            $lounge13 = $this->source('$AA', 'Serenity Haven')
                ->setAvailable(true)
                ->setTerminal('T1')
                ->setGate(4)
                ->setRules('Random rules 2'),
        ]);
        $lounge11->setOpeningHours(new RawOpeningHours('10:00-20:00'));
        $lounge12->setAvailable(false);
        $this->runCommand([$parser1], null, ['$AA']);
        $this->seeLoungeWasFound($parser1, $lounge11);
        $this->seeLoungeWasFound($parser1, $lounge12);
        $this->seeCreateNewLounge($parser1, $lounge13);
        $this->seeClusters('$AA', 3);

        // without update lounges
        $this->runCommand([$parser1], null, ['$AA']);
        $this->seeLoungeWasFound($parser1, $lounge11);
        $this->seeLoungeWasFound($parser1, $lounge12);
        $this->seeLoungeWasFound($parser1, $lounge13);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 3, 0);
        $this->seeClusters('$AA', 3);
    }

    public function testSkipParsing()
    {
        $parser = $this->createParser();
        $this->runCommand([$parser], 10, null, false, true, null, [
            'get' => time(),
        ]);
        $this->seeSkipParsing($parser);
    }

    public function testParserException()
    {
        $parser = $this->createParser([], true);
        $this->runCommand([$parser], 10);
        $this->seeParserException($parser, 'Test exception');
    }

    public function testDisableParser()
    {
        $parser1 = $this->createParser([
            $lounge1 = $this->source('$AA', 'Super Business Lounge #1')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('Mon-Fri'))
                ->setIsAvailable(true)
                ->setLocation('Test location')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(null)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                ]),
        ]);
        $parser2 = $this->createParser([
            $lounge2 = $this->source('$AA', 'Super Business Lounge #2')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('10-20'))
                ->setIsAvailable(null)
                ->setLocation('Test 2 location')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(true)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                    $this->findAirline(['fsCode' => 'OZ']),
                ]),
        ]);
        $parser3 = $this->createParser([
            $lounge3 = $this->source('$AA', 'Super Business Lounge #3')
                ->setTerminal(2)
                ->setGate(8)
                ->setGate2(9)
                ->setDragonPassAccess(true)
                ->setIsAvailable(false),
        ]);

        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers = [
            $parser2, $parser1, $parser3,
        ], $patches = [
            'OpeningHours' => [$parser1],
            'DragonPassAccess' => [$parser3],
        ]));

        $this->seeCreateNewLounge($parser1, $lounge1);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 1);
        $this->seeCreateNewLounge($parser2, $lounge2);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 1);
        $this->seeCreateNewLounge($parser3, $lounge3);
        $this->seeSavedLoungesInAirport($parser3, '$AA', 1, 1);
        $this->seeClusters('$AA', 1);
        $this->seeCreateMergedLounge('$AA');
        $this->seeMergedLounge($lounge1->createLounge());
        $this->assertNotNull($merged = $this->findLounge(['airportCode' => '$AA', 'name' => 'Super Business Lounge #2']));
        $this->assertEquals($lounge2->getName(), $merged->getName());
        $this->assertEquals($lounge2->getTerminal(), $merged->getTerminal());
        $this->assertEquals($lounge2->getGate(), $merged->getGate());
        $this->assertEquals($lounge2->getGate2(), $merged->getGate2());
        $this->assertEquals($lounge1->getOpeningHours(), $merged->getOpeningHours());
        $this->assertEquals($lounge2->isAvailable(), $merged->isAvailable());
        $this->assertEquals($lounge2->getLocation(), $merged->getLocation());
        $this->assertEquals($lounge2->isPriorityPassAccess(), $merged->isPriorityPassAccess());
        $this->assertEquals($lounge2->isAmexPlatinumAccess(), $merged->isAmexPlatinumAccess());
        $this->assertEquals($lounge3->isDragonPassAccess(), $merged->isDragonPassAccess());
        $this->assertCount(\count($lounge2->getAirlines()), $merged->getAirlines());

        // disable parser
        $parser2->setEnabled(false);

        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager([$parser1, $parser3], $patches));
        $this->seeLoungeWasFound($parser1, $lounge1);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 0);
        $this->seeDeletedLounges($parser2, 1);
        $this->seeLoungeWasFound($parser3, $lounge3);
        $this->seeSavedLoungesInAirport($parser3, '$AA', 1, 0);
        $this->seeClusters('$AA', 1);
        $this->seeMergedLounge($merged);
        $this->logContains('updated props: [Name, IsAvailable, Location, AmexPlatinumAccess, Airlines]');
        $this->assertEquals($lounge1->getName(), $merged->getName());
        $this->assertEquals($lounge1->getTerminal(), $merged->getTerminal());
        $this->assertEquals($lounge1->getGate(), $merged->getGate());
        $this->assertEquals($lounge1->getGate2(), $merged->getGate2());
        $this->assertEquals($lounge1->getOpeningHours(), $merged->getOpeningHours());
        $this->assertEquals($lounge1->isAvailable(), $merged->isAvailable());
        $this->assertEquals($lounge3->isDragonPassAccess(), $merged->isDragonPassAccess());
    }

    public function testParsingFrozen()
    {
        $parser1 = $this->createParser([
            $lounge1 = $this->source('$AA', 'Super Business Lounge #1')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('Mon-Fri'))
                ->setIsAvailable(true)
                ->setLocation('Test location')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(null)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                ]),
        ]);
        $parser2 = $this->createParser([
            $lounge2 = $this->source('$AA', 'Super Business Lounge #2')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('10-20'))
                ->setIsAvailable(null)
                ->setLocation('Test 2 location')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(true)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                    $this->findAirline(['fsCode' => 'OZ']),
                ]),
        ]);
        $parser3 = $this->createParser([
            $lounge3 = $this->source('$AA', 'Super Business Lounge #3')
                ->setTerminal(2)
                ->setGate(8)
                ->setGate2(9)
                ->setDragonPassAccess(true)
                ->setIsAvailable(false),
        ]);

        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers = [
            $parser2, $parser1, $parser3,
        ], $patches = [
            'OpeningHours' => [$parser1],
            'DragonPassAccess' => [$parser3],
        ]));

        $this->seeCreateNewLounge($parser1, $lounge1);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 1);
        $this->seeCreateNewLounge($parser2, $lounge2);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 1);
        $this->seeCreateNewLounge($parser3, $lounge3);
        $this->seeSavedLoungesInAirport($parser3, '$AA', 1, 1);
        $this->seeClusters('$AA', 1);
        $this->seeCreateMergedLounge('$AA');
        $this->seeMergedLounge($lounge1->createLounge());
        $this->assertNotNull($merged = $this->findLounge(['airportCode' => '$AA', 'name' => 'Super Business Lounge #2']));
        $this->assertEquals($lounge2->getName(), $merged->getName());
        $this->assertEquals($lounge2->getTerminal(), $merged->getTerminal());
        $this->assertEquals($lounge2->getGate(), $merged->getGate());
        $this->assertEquals($lounge2->getGate2(), $merged->getGate2());
        $this->assertEquals($lounge1->getOpeningHours(), $merged->getOpeningHours());
        $this->assertEquals($lounge2->isAvailable(), $merged->isAvailable());
        $this->assertEquals($lounge2->getLocation(), $merged->getLocation());
        $this->assertEquals($lounge2->isPriorityPassAccess(), $merged->isPriorityPassAccess());
        $this->assertEquals($lounge2->isAmexPlatinumAccess(), $merged->isAmexPlatinumAccess());
        $this->assertEquals($lounge3->isDragonPassAccess(), $merged->isDragonPassAccess());
        $this->assertCount(\count($lounge2->getAirlines()), $merged->getAirlines());

        // frozen parser
        $parser2->setIsParsingFrozen(true);

        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeLoungeWasFound($parser1, $lounge1);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 0);
        $this->seeSkipFrozenParser($parser2);
        $this->seeLoungeWasFound($parser3, $lounge3);
        $this->seeSavedLoungesInAirport($parser3, '$AA', 1, 0);
        $this->seeClusters('$AA', 1);
        $this->seeMergedLounge($merged);
        $this->assertEquals($lounge2->getName(), $merged->getName());
        $this->assertEquals($lounge2->getTerminal(), $merged->getTerminal());
        $this->assertEquals($lounge2->getGate(), $merged->getGate());
        $this->assertEquals($lounge2->getGate2(), $merged->getGate2());
        $this->assertEquals($lounge1->getOpeningHours(), $merged->getOpeningHours());
        $this->assertEquals($lounge2->isAvailable(), $merged->isAvailable());
        $this->assertEquals($lounge2->getLocation(), $merged->getLocation());
        $this->assertEquals($lounge2->isPriorityPassAccess(), $merged->isPriorityPassAccess());
        $this->assertEquals($lounge2->isAmexPlatinumAccess(), $merged->isAmexPlatinumAccess());
        $this->assertEquals($lounge3->isDragonPassAccess(), $merged->isDragonPassAccess());
        $this->assertCount(\count($lounge2->getAirlines()), $merged->getAirlines());

        $this->db->seeInDatabase('LoungeSource', [
            'LoungeSourceID' => $lounge2->getId(),
        ]);
    }

    public function testDuplicateRemoval()
    {
        $parser1 = $this->createParser([
            $lounge1 = $this->source('$AA', 'Super Business Lounge #1')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('Mon-Fri'))
                ->setIsAvailable(true)
                ->setLocation('Test location')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(null)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                ]),
        ]);
        $parser2 = $this->createParser([
            $lounge2 = $this->source('$AA', 'Super Business Lounge #2')
                ->setTerminal(2)
                ->setOpeningHours(new RawOpeningHours('10-20'))
                ->setIsAvailable(null)
                ->setLocation('Test 2 location')
                ->setPriorityPassAccess(true)
                ->setAmexPlatinumAccess(true)
                ->setAirlines([
                    $this->findAirline(['fsCode' => 'AA']),
                    $this->findAirline(['fsCode' => 'OZ']),
                ]),
        ]);
        $parser3 = $this->createParser([
            $lounge3 = $this->source('$AA', 'Super Business Lounge #3')
                ->setTerminal(2)
                ->setGate(8)
                ->setGate2(9)
                ->setDragonPassAccess(true)
                ->setIsAvailable(false),
        ]);

        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers = [
            $parser2, $parser1, $parser3,
        ], $patches = [
            'OpeningHours' => [$parser1],
            'DragonPassAccess' => [$parser3],
        ]));

        $this->seeCreateNewLounge($parser1, $lounge1);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 1);
        $this->seeCreateNewLounge($parser2, $lounge2);
        $this->seeSavedLoungesInAirport($parser2, '$AA', 1, 1);
        $this->seeCreateNewLounge($parser3, $lounge3);
        $this->seeSavedLoungesInAirport($parser3, '$AA', 1, 1);
        $this->seeClusters('$AA', 1);
        $this->seeCreateMergedLounge('$AA');
        $this->seeMergedLounge($lounge1->createLounge());

        $this->assertNotNull($merged = $this->findLounge(['airportCode' => '$AA', 'name' => 'Super Business Lounge #2']));
        $this->assertEquals($lounge2->getName(), $merged->getName());
        $this->assertEquals($lounge2->getTerminal(), $merged->getTerminal());
        $this->assertEquals($lounge2->getGate(), $merged->getGate());
        $this->assertEquals($lounge2->getGate2(), $merged->getGate2());
        $this->assertEquals($lounge1->getOpeningHours(), $merged->getOpeningHours());
        $this->assertEquals($lounge2->isAvailable(), $merged->isAvailable());
        $this->assertEquals($lounge2->getLocation(), $merged->getLocation());
        $this->assertEquals($lounge2->isPriorityPassAccess(), $merged->isPriorityPassAccess());
        $this->assertEquals($lounge2->isAmexPlatinumAccess(), $merged->isAmexPlatinumAccess());
        $this->assertEquals($lounge3->isDragonPassAccess(), $merged->isDragonPassAccess());
        $this->assertCount(\count($lounge2->getAirlines()), $merged->getAirlines());

        // add duplicate lounge
        $dupLoungeId = $this->dbBuilder->makeLounge(
            new DbLounge('$AA', $merged->getName(), [
                'Terminal' => $merged->getTerminal(),
                'Gate' => $merged->getGate(),
                'IsAvailable' => 1,
            ])
        );

        $this->runCommand([$parser1, $parser2, $parser3], null, ['$AA'], false, true, $this->createPriorityManager($parsers, $patches));
        $this->seeLoungeWasFound($parser1, $lounge1);
        $this->seeSavedLoungesInAirport($parser1, '$AA', 1, 0);
        $this->seeLoungeWasFound($parser3, $lounge3);
        $this->seeSavedLoungesInAirport($parser3, '$AA', 1, 0);
        $this->seeClusters('$AA', 2);
        $this->seeMergedLounge($merged);
        $this->assertEquals($lounge2->getName(), $merged->getName());
        $this->assertEquals($lounge2->getTerminal(), $merged->getTerminal());
        $this->assertEquals($lounge2->getGate(), $merged->getGate());
        $this->assertEquals($lounge2->getGate2(), $merged->getGate2());
        $this->assertEquals($lounge1->getOpeningHours(), $merged->getOpeningHours());
        $this->assertEquals($lounge2->isAvailable(), $merged->isAvailable());
        $this->assertEquals($lounge2->getLocation(), $merged->getLocation());
        $this->assertEquals($lounge2->isPriorityPassAccess(), $merged->isPriorityPassAccess());
        $this->assertEquals($lounge2->isAmexPlatinumAccess(), $merged->isAmexPlatinumAccess());
        $this->assertEquals($lounge3->isDragonPassAccess(), $merged->isDragonPassAccess());
        $this->assertCount(\count($lounge2->getAirlines()), $merged->getAirlines());

        $this->db->seeInDatabase('LoungeSource', [
            'LoungeSourceID' => $lounge2->getId(),
        ]);
        $this->db->seeInDatabase('Lounge', [
            'LoungeID' => $merged->getId(),
        ]);
        $this->db->dontSeeInDatabase('Lounge', [
            'LoungeID' => $dupLoungeId,
        ]);
    }
}
