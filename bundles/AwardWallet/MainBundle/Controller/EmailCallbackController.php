<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Email\CallbackProcessor;
use AwardWallet\MainBundle\Worker\AsyncProcess\EmailCallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use PhpAmqpLib\Connection\AbstractConnection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailCallbackController extends AbstractController
{
    private string $emailCallbackPassword;

    public function __construct(string $emailCallbackPassword)
    {
        $this->emailCallbackPassword = $emailCallbackPassword;
    }

    /**
     * @Route("/api/awardwallet/email", name="aw_emailcallback_save")
     * @return Response
     */
    public function saveAction(Request $request, LoggerInterface $logger, Process $asyncProcess, AbstractConnection $rabbitConnection)
    {
        if ($request->getUser() != 'awardwallet' || $request->getPassword() != $this->emailCallbackPassword) {
            $logger->warning("access denied for " . $request->getUser());

            return new Response('access denied', 403);
        }

        [$queueName, $messageCount, $consumerCount] = $rabbitConnection->channel()->queue_declare('aw_async_processor_2', true);

        if ($messageCount > 5000) {
            $logger->error("too big async_processor queue");

            return new JsonResponse("queue is full", 429);
        }

        $content = $request->getContent();
        $task = new EmailCallbackTask($content);
        $asyncProcess->execute($task);
        $logger->info("email callback queued, task id: " . $task->requestId, ["bodySize" => strlen($content)]);

        return new JsonResponse(CallbackProcessor::SAVE_MESSAGE_SUCCESS);
    }
}
