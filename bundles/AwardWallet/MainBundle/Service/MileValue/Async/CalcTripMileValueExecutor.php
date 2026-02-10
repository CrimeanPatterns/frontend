<?php

namespace AwardWallet\MainBundle\Service\MileValue\Async;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CalcTripMileValueExecutor implements ExecutorInterface
{
    private ContextAwareLoggerWrapper $logger;
    private CalcMileValueCommand $command;

    public function __construct(LoggerInterface $logger, CalcMileValueCommand $command)
    {
        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->command = $command;
    }

    /**
     * @param CalcTripMileValueTask $task
     */
    public function execute(Task $task, $delay = null)
    {
        $this->logger->pushContext(["tripId" => $task->getTripId()]);

        try {
            $this->logger->info("calculating mile value for trip");
            $input = new ArrayInput([
                '--extra-sources' => true,
                '--tripId' => $task->getTripId(),
            ]);
            $output = new BufferedOutput();
            $this->command->run($input, $output);
            $this->logger->info("done processing mile value");
        } finally {
            $this->logger->popContext();
        }

        return new Response(Response::STATUS_READY);
    }
}
