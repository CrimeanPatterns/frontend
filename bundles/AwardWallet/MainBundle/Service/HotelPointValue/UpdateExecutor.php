<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\Repositories\ReservationRepository;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;

class UpdateExecutor implements ExecutorInterface
{
    private LoggerInterface $logger;

    private PointValueCalculator $calculator;

    private ReservationRepository $reservationRepository;

    private LockFactory $lockFactory;

    private Process $asyncProcess;

    public function __construct(
        LoggerInterface $logger,
        PointValueCalculator $calculator,
        ReservationRepository $reservationRepository,
        LockFactory $lockFactory,
        Process $asyncProcess
    ) {
        $this->logger = $logger;
        $this->calculator = $calculator;
        $this->reservationRepository = $reservationRepository;
        $this->lockFactory = $lockFactory;
        $this->asyncProcess = $asyncProcess;
    }

    /**
     * @param UpdateTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $this->logger->info("calculating point value for reservation {$task->getReservationId()}");

        // we will acquire lock, because price search will take up to 90 seconds
        // we do not want to start another search/update while searching
        $lock = $this->lockFactory->createLock("hpv-calc-{$task->getReservationId()}", 90);

        if (!$lock->acquire()) {
            $this->logger->info("failed to acquire hpv lock for reservation {$task->getReservationId()}");

            if ($task->getAttempt() >= 3) {
                $this->logger->critical("too much hpv update attempts for reservation {$task->getReservationId()}");

                return new Response();
            }

            $this->asyncProcess->execute(new UpdateTask($task->getReservationId(), $task->getAttempt() + 1), 180);

            return new Response();
        }

        try {
            $reservation = $this->reservationRepository->find($task->getReservationId());

            if ($reservation === null) {
                $this->logger->info("reservation {$task->getReservationId()} not found");

                return new Response();
            }

            $this->calculator->updateItinerary($reservation, false);

            return new Response();
        } finally {
            $lock->release();
        }
    }
}
