<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Service\SocksMessaging\UserMessaging;
use AwardWallet\MainBundle\Updater\RequestSerializer;
use Symfony\Component\HttpFoundation\Request;

class HttpRequestTaskFactory
{
    /**
     * @var RequestSerializer
     */
    private $requestSerializer;
    /**
     * @var AwTokenStorage
     */
    private $tokenStorage;

    public function __construct(RequestSerializer $requestSerializer, AwTokenStorage $tokenStorage)
    {
        $this->requestSerializer = $requestSerializer;
        $this->tokenStorage = $tokenStorage;
    }

    public function createTask(Request $request): HttpRequestTask
    {
        return new HttpRequestTask(
            $this->requestSerializer->serializeRequest($request),
            UserMessaging::getChannelName('httpreq' . bin2hex(random_bytes(3)), $this->tokenStorage->getUser()->getId()),
            $this->tokenStorage->getUser()->getId()
        );
    }
}
