<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Repositories\RewardsTransferRepository;
use AwardWallet\MainBundle\Entity\RewardsTransfer;
use AwardWallet\MainBundle\Manager\RewardsTransferManager;

class RewardsTransferManagerTest extends BaseUserTest
{
    /** @var \Doctrine\Common\Persistence\ObjectManager */
    protected $entityManager;

    /** @var RewardsTransferManager */
    protected $rewardsTransferManager;

    /** @var ProviderRepository */
    protected $providerRepository;

    /** @var Provider */
    protected $testProvider;

    /** @var RewardsTransferRepository */
    protected $rewardsTransferRepository;

    /** @var \TAccountCheckerTestprovider */
    protected $testProviderChecker;

    protected $count;

    public function _before()
    {
        parent::_before();
        $this->rewardsTransferManager = $this->container->get('aw.manager.rewards_transfer');
        $this->providerRepository = $this->container->get('doctrine')->getRepository(Provider::class);
        $this->rewardsTransferRepository = $this->container->get('doctrine')->getRepository(RewardsTransfer::class);
        $this->entityManager = $this->container->get('doctrine')->getManager();
        $this->testProvider = $this->providerRepository->findOneBy(['code' => 'testprovider']);
        $this->testProviderChecker = new \TAccountCheckerTestprovider();
        $this->testProviderChecker->fakeRewardsTransfers = [];
        $this->count = 10;
    }

    public function _after()
    {
        $testProviderRewardsTransfers = $this->rewardsTransferRepository->findBy(['sourceProvider' => $this->testProvider]);

        foreach ($testProviderRewardsTransfers as $t) {
            $this->entityManager->remove($t);
        }
        $this->entityManager->flush();

        $this->testProviderChecker =
        $this->testProvider =
        $this->entityManager =
        $this->rewardsTransferRepository =
        $this->providerRepository =
        $this->rewardsTransferManager = null;
    }

    public function testRewardsTransferAdding()
    {
        // Prepare data
        $fakeRewardsTransfersArr = $this->createFakeRewardsTransfersArray();
        $this->loadRewardsTransfersToChecker($fakeRewardsTransfersArr);
        codecept_debug("Input dataset (in checker, DB is empty):\n" . print_r($fakeRewardsTransfersArr, true));

        // Test
        $result = $this->rewardsTransferManager->updateRewardsTransferRatesForProvider($this->testProvider, $this->testProviderChecker);
        codecept_debug("Rewards Transfer Manager returned:\n" . (($r = print_r($result, true)) ? $r : 'NOTHING'));
        $this->assertNotEquals(false, $result);
        $this->assertNotEquals([], $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(true, isset($result['Added']));
        $this->assertNotEquals(false, array_values($result)[0]);
        $this->assertEquals($this->count, count(array_values($result)[0]));
    }

    public function testRewardsTransferUpdating()
    {
        // Prepare data
        $fakeRewardsTransfersArr = $this->createFakeRewardsTransfersArray();
        $this->loadRewardsTransfersToChecker($fakeRewardsTransfersArr);
        $this->loadRewardsTransfersToDatabase($fakeRewardsTransfersArr);
        codecept_debug("Input dataset (both in checker and in DB):\n" . print_r($fakeRewardsTransfersArr, true));

        // Test
        $result = $this->rewardsTransferManager->updateRewardsTransferRatesForProvider($this->testProvider, $this->testProviderChecker);
        codecept_debug("Rewards Transfer Manager returned:\n" . (($r = print_r($result, true)) ? $r : 'NOTHING'));
        $this->assertNotEquals(false, $result);
        $this->assertNotEquals([], $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(true, isset($result['Updated']));
        $this->assertNotEquals(false, array_values($result)[0]);
        $this->assertEquals($this->count, count(array_values($result)[0]));
    }

    // Test for updating and removing all at once
    public function testRewardsTransferUpdatingAndRemoving()
    {
        // Prepare data
        $removedCount = floor($this->count / 3);
        $updatedCount = $this->count - $removedCount;
        $fakeRewardsTransfersArr = $this->createFakeRewardsTransfersArray($updatedCount + $removedCount);
        $this->loadRewardsTransfersToDatabase($fakeRewardsTransfersArr);
        $i = 0;
        $updatedRewardsTransfers = [];

        foreach ($fakeRewardsTransfersArr as $rt) {
            if ($i < $updatedCount) {
                $updatedRewardsTransfers[] = $rt;
            }
            $i++;
        }
        $this->loadRewardsTransfersToChecker($updatedRewardsTransfers);
        codecept_debug("Input dataset (all):\n" . print_r($fakeRewardsTransfersArr, true));
        codecept_debug("Input dataset (actual only, other will be removed):\n" . print_r($updatedRewardsTransfers, true));

        // Test
        $result = $this->rewardsTransferManager->updateRewardsTransferRatesForProvider($this->testProvider, $this->testProviderChecker);
        codecept_debug("Rewards Transfer Manager returned:\n" . (($r = print_r($result, true)) ? $r : 'NOTHING'));
        $this->assertNotEquals(false, $result);
        $this->assertNotEquals([], $result);
        $this->assertEquals(2, count($result));
        $this->assertEquals(true, isset($result['Updated']));
        $this->assertEquals($updatedCount, count($result['Updated']));
        $this->assertEquals(true, isset($result['Removed']));
        $this->assertEquals($removedCount, count($result['Removed']));
    }

    // Test for adding, updating and removing all at once
    public function testRewardsTransferAddingAndUpdatingAndRemoving()
    {
        // Prepare data
        $addedCount = floor($this->count / 5);
        $removedCount = floor($this->count / 3);
        $updatedCount = $this->count - $removedCount - $addedCount;
        $fakeRewardsTransfersArr = $this->createFakeRewardsTransfersArray($updatedCount + $removedCount + $addedCount);
        $i = 0;
        $addedRewardsTransfers = [];
        $updatedRewardsTransfers = [];
        $removedRewardsTransfers = [];

        foreach ($fakeRewardsTransfersArr as $rt) {
            if ($i < $addedCount) {
                $addedRewardsTransfers[] = $rt;
            } elseif ($i < $addedCount + $updatedCount) {
                $updatedRewardsTransfers[] = $rt;
            } else {
                $removedRewardsTransfers[] = $rt;
            }
            $i++;
        }
        $this->loadRewardsTransfersToDatabase(array_merge($updatedRewardsTransfers, $removedRewardsTransfers));
        $this->loadRewardsTransfersToChecker(array_merge($addedRewardsTransfers, $updatedRewardsTransfers));
        codecept_debug("Input dataset (all):\n" . print_r($fakeRewardsTransfersArr, true));
        codecept_debug("Input dataset (to add):\n" . print_r($addedRewardsTransfers, true));
        codecept_debug("Input dataset (to update):\n" . print_r($updatedRewardsTransfers, true));
        codecept_debug("Input dataset (to remove):\n" . print_r($removedRewardsTransfers, true));

        // Test
        $result = $this->rewardsTransferManager->updateRewardsTransferRatesForProvider($this->testProvider, $this->testProviderChecker);
        codecept_debug("Rewards Transfer Manager returned:\n" . (($r = print_r($result, true)) ? $r : 'NOTHING'));
        $this->assertNotEquals(false, $result);
        $this->assertNotEquals([], $result);
        $this->assertEquals(3, count($result));
        $this->assertEquals(true, isset($result['Updated']));
        $this->assertEquals($updatedCount, count($result['Updated']));
        $this->assertEquals(true, isset($result['Removed']));
        $this->assertEquals($removedCount, count($result['Removed']));
        $this->assertEquals(true, isset($result['Added']));
        $this->assertEquals($addedCount, count($result['Added']));
    }

    public function testRewardsTransferFullRemoving()
    {
        // Prepare dataset
        $fakeRewardsTransfersArr = $this->createFakeRewardsTransfersArray();
        $this->loadRewardsTransfersToDatabase($fakeRewardsTransfersArr);
        codecept_debug("Input dataset:\n" . print_r($fakeRewardsTransfersArr, true));
        $testRewardsTransfersCount = count($this->rewardsTransferRepository->findBy(['sourceProvider' => $this->testProvider]));
        codecept_debug("Loaded $testRewardsTransfersCount rewards transfers for test provider");
        // Test
        $result = $this->rewardsTransferManager->updateRewardsTransferRatesForProvider($this->testProvider, $this->testProviderChecker);
        codecept_debug("Rewards Transfer Manager returned:\n" . (($r = print_r($result, true)) ? $r : 'NOTHING'));
        $this->assertEquals(false, $result);
        $testRewardsTransfersCountAfterUpdate = count($this->rewardsTransferRepository->findBy(['sourceProvider' => $this->testProvider]));
        codecept_debug("$testRewardsTransfersCountAfterUpdate rewards transfers for test provider after update");
        $this->assertEquals($testRewardsTransfersCount, $testRewardsTransfersCountAfterUpdate);
    }

    private function createFakeRewardsTransfersArray()
    {
        $testProviderCode = $this->testProvider->getCode();
        /** @var Provider[] $providers */
        $providers = $this->providerRepository->findBy([], null, $this->count);
        $fakeRewardsTransfers = [];

        foreach ($providers as $p) {
            $fakeRewardsTransfer = [
                'SourceProviderCode' => $testProviderCode,
                'TargetProviderCode' => $p->getCode(),
                'SourceRate' => 1,
                'TargetRate' => 1,
            ];
            $fakeRewardsTransfers[] = $fakeRewardsTransfer;
        }

        return $fakeRewardsTransfers;
    }

    private function loadRewardsTransfersToChecker($rewardsTransfersArr)
    {
        $this->testProviderChecker->fakeRewardsTransfers = $rewardsTransfersArr;
        codecept_debug("Loaded to checker:\n" . print_r($rewardsTransfersArr, true));
    }

    private function loadRewardsTransfersToDatabase($rewardsTransfersArr)
    {
        foreach ($rewardsTransfersArr as $t) {
            $rt = new RewardsTransfer();
            $rt->setSourceProvider($this->providerRepository->findOneBy(['code' => $t['SourceProviderCode']]));
            $rt->setTargetProvider($this->providerRepository->findOneBy(['code' => $t['TargetProviderCode']]));
            $rt->setSourceRate($t['SourceRate']);
            $rt->setTargetRate($t['TargetRate']);
            $rt->setEnabled(true);
            $this->entityManager->persist($rt);
        }
        $this->entityManager->flush();
        codecept_debug("Loaded to database:\n" . print_r($rewardsTransfersArr, true));
    }
}
