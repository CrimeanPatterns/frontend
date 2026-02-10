<?php

namespace AwardWallet\MainBundle\Service\SocksMessaging;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use phpcent\TransportException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Client implements ClientInterface
{
    /**
     * @var \phpcent\Client
     */
    private $client;

    private string $url;

    private LoggerInterface $logger;

    private RouterInterface $router;

    private AwTokenStorageInterface $tokenStorage;

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        string $cometServerUrl,
        string $cometServerSecret,
        LoggerInterface $logger,
        RouterInterface $router,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        require_once __DIR__ . '/../../../../../vendor/sl4mmer/phpcent/Client.php';

        require_once __DIR__ . '/../../../../../vendor/sl4mmer/phpcent/ITransport.php';

        require_once __DIR__ . '/../../../../../vendor/sl4mmer/phpcent/Transport.php';

        require_once __DIR__ . '/../../../../../vendor/sl4mmer/phpcent/TransportException.php';

        $this->client = new \phpcent\Client($cometServerUrl);
        $this->client->setSecret($cometServerSecret);
        $this->url = $cometServerUrl;
        $this->logger = $logger;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param $data array
     */
    public function publish($channel, $data)
    {
        try {
            return $this->client->publish($channel, $data);
        } catch (TransportException $e) {
            $this->logger->critical($e->getMessage());

            return null;
        }
    }

    public function presence($channel)
    {
        try {
            $result = $this->client->presence($channel);
            $this->logger->debug("got presence on $channel", ["presence" => $result]);

            return $result;
        } catch (TransportException $e) {
            $this->logger->critical($e->getMessage());

            return null;
        }
    }

    public function broadcast($channel, $data)
    {
        try {
            return $this->client->broadcast($channel, $data);
        } catch (TransportException $e) {
            $this->logger->critical($e->getMessage());

            return null;
        }
    }

    public function getClientData()
    {
        $timestamp = time();

        $info = [
            'username' => $this->tokenStorage->getToken()->getUser()->getFullName(),
            'isBooker' => $this->authorizationChecker->isGranted('USER_BOOKING_MANAGER'),
        ];

        if ($this->authorizationChecker->isGranted('USER_IMPERSONATED')) {
            $info['impersonated'] = true;
        }

        $info = json_encode((object) $info);

        return [
            'url' => $this->url,
            'authEndpoint' => $this->router->generate('aw_socks_auth', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'user' => (string) $this->tokenStorage->getToken()->getUser()->getUserid(),
            'timestamp' => (string) $timestamp,
            'info' => $info,
            'token' => $this->generateClientToken($this->tokenStorage->getToken()->getUser()->getUserid(), $timestamp, $info),
            'debug' => $this->authorizationChecker->isGranted('SITE_DEV_MODE'),
        ];
    }

    public function generateChannelSign($client, $channel, $info = null)
    {
        return $this->client->generateChannelSign($client, $channel, $info);
    }

    private function generateClientToken($user, $timestamp, $info = null)
    {
        return $this->client->generateClientToken($user, $timestamp, $info);
    }
}
