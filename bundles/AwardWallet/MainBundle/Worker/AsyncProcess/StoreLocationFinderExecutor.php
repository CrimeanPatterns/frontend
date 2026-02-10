<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Service\StoreLocationFinder\StoreFilter;
use AwardWallet\MainBundle\Service\StoreLocationFinder\StoreLocationFinder;
use Doctrine\ORM\EntityManagerInterface;

class StoreLocationFinderExecutor implements ExecutorInterface
{
    /**
     * @var StoreLocationFinder
     */
    private $storeLocationFinder;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        StoreLocationFinder $storeLocationFinder,
        EntityManagerInterface $entityManager
    ) {
        $this->storeLocationFinder = $storeLocationFinder;
        $this->entityManager = $entityManager;
    }

    public function execute(Task $task, $delay = null): Response
    {
        $this->doExecute($task);

        return new Response();
    }

    public function doExecute(Task $task)
    {
        if (!$task instanceof StoreLocationFinderTask) {
            return;
        }

        if ('user' === $task->type) {
            $filter = (new StoreFilter())
                ->setUserIds([$task->id])
                ->setLocationsLimit(20)
                ->setRadius(10 * 1600);

            if ($task->clearExistingPoints) {
                $this->entityManager->getConnection()->executeUpdate('
                    delete l
                    from Account a
                    join Location l on a.AccountID = l.AccountID
                    where 
                      a.UserID = ? and
                      l.IsGenerated = 1',
                    [$task->id],
                    [\PDO::PARAM_INT]
                );
            }
        } else {
            $filter = (new StoreFilter())
                ->setAccountIds('account' === $task->type ? [$task->id] : [])
                ->setCouponIds('coupon' === $task->type ? [$task->id] : [])
                ->setLocationsLimit(20)
                ->setRadius(10 * 1600);
        }

        $this->storeLocationFinder->findLocationsNearZipArea($filter);
    }
}
