<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AsyncControllerAction
{
    public const ATTRIBUTE_ASYNC_REQUEST = 'async_request';

    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var HttpRequestTaskFactory
     */
    private $factory;
    /**
     * @var Process
     */
    private $asyncProcess;
    /**
     * @var Client
     */
    private $messaging;

    public function __construct(Environment $twig, HttpRequestTaskFactory $factory, Process $asyncProcess, Client $messaging)
    {
        $this->twig = $twig;
        $this->factory = $factory;
        $this->asyncProcess = $asyncProcess;
        $this->messaging = $messaging;
    }

    public function renderProgress(Request $request, string $title, int $priority = Process::PRIORITY_HIGH): ?Response
    {
        if ($request->attributes->has(self::ATTRIBUTE_ASYNC_REQUEST)) {
            return null;
        }

        $request->attributes->set(self::ATTRIBUTE_ASYNC_REQUEST, true);
        $task = $this->factory->createTask($request);
        $this->asyncProcess->execute($task, null, false, $priority);

        return new Response($this->twig->render("@AwardWalletMain/Manager/asyncResponse.html.twig", [
            "title" => $title,
            "channel" => $task->getResponseChannel(),
            'centrifuge_config' => $this->messaging->getClientData(),
        ]));
    }
}
