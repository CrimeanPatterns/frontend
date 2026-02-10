<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries\ItineraryProcessorInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Service\Locker;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class ItinerariesProcessorTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    public function testSaveItineraries()
    {
        $schemaObject1 = 'schema1';
        $schemaObject2 = 'schema2';
        $processedLog = ['processor1' => ['schema1' => 0, 'schema2' => 0], 'processor2' => ['schema1' => 0, 'schema2' => 0]];
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->makeEmpty(EntityManagerInterface::class, [
            'flush' => Stub::atLeastOnce(),
        ]);
        $processor1 = $this->makeEmpty(ItineraryProcessorInterface::class, [
            'process' => Stub::exactly(2, function ($schema) use ($schemaObject1, $schemaObject2, &$processedLog) {
                if ($schema === $schemaObject1) {
                    $processedLog['processor1']['schema1']++;
                }

                if ($schema === $schemaObject2) {
                    $processedLog['processor1']['schema2']++;
                }

                return $this->makeEmpty(ProcessingReport::class);
            }),
        ]);
        $processor2 = $this->makeEmpty(ItineraryProcessorInterface::class, [
            'process' => Stub::exactly(2, function ($schema) use ($schemaObject1, $schemaObject2, &$processedLog) {
                if ($schema === $schemaObject1) {
                    $processedLog['processor2']['schema1']++;
                }

                if ($schema === $schemaObject2) {
                    $processedLog['processor2']['schema2']++;
                }

                return $this->makeEmpty(ProcessingReport::class);
            }),
        ]);
        $processors = [$processor1, $processor2];
        $locker = $this->createMock(Locker::class);
        $locker
            ->method('acquire')
            ->willReturn(true)
        ;
        $itinerariesProcessor = new ItinerariesProcessor($entityManager, $locker, $this->createMock(LoggerInterface::class), $processors);
        /** @var Account $account */
        $account = $this->makeEmpty(Account::class, [
            'getOwner' => $this->makeEmpty(Owner::class),
            'getId' => 1,
        ]);
        $itinerariesProcessor->save([$schemaObject1, $schemaObject2],
            SavingOptions::savingByAccount($account, SavingOptions::INITIALIZED_BY_USER));
        $this->assertSame(1, $processedLog['processor1']['schema1']);
        $this->assertSame(1, $processedLog['processor1']['schema2']);
        $this->assertSame(1, $processedLog['processor2']['schema1']);
        $this->assertSame(1, $processedLog['processor2']['schema2']);
    }
}
