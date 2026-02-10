<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor\SymfonyContext;
use AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor\SymfonyEnvironmentExecutor;
use AwardWallet\MainBundle\Service\OldUI;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use AwardWallet\MainBundle\Updater\RequestSerializer;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HttpRequestExecutor implements ExecutorInterface
{
    /**
     * @var RequestSerializer
     */
    private $requestSerializer;
    /**
     * @var SocksClient
     */
    private $messaging;
    /**
     * @var HttpKernelInterface
     */
    private $kernel;
    /**
     * @var SymfonyEnvironmentExecutor
     */
    private $environmentExecutor;
    /**
     * @var UsrRepository
     */
    private $usrRepository;

    public function __construct(
        RequestSerializer $requestSerializer,
        OldUI $oldUI,
        SocksClient $messaging,
        HttpKernelInterface $kernel,
        SymfonyEnvironmentExecutor $environmentLoader,
        UsrRepository $usrRepository
    ) {
        $this->requestSerializer = $requestSerializer;
        $this->messaging = $messaging;
        $this->kernel = $kernel;
        $this->environmentExecutor = $environmentLoader;
        $this->usrRepository = $usrRepository;
    }

    /**
     * @param HttpRequestTask $task
     */
    public function execute(Task $task, $delay = null)
    {
        $request = $this->requestSerializer->deserializeRequest($task->getSerializedRequest());

        $user = $this->usrRepository->find($task->getUserId());

        if ($user === null) {
            $this->messaging->publish($task->getResponseChannel(),
                ["type" => "response", "message" => "user not found"]);

            return new Response();
        }

        $backupServer = $_SERVER;
        $backupGet = $_GET;
        $_GET = $request->query->all();
        $_SERVER = $request->server->all();

        try {
            $response = $this->environmentExecutor->process(
                new SymfonyContext($user, $request),
                fn () => $this->kernel->handle($request)
            );
        } finally {
            $_GET = $backupGet;
            $_SERVER = $backupServer;
        }

        $this->messaging->publish($task->getResponseChannel(),
            ["type" => "response", "message" => $response->getContent()]);

        return new Response();
    }
}
