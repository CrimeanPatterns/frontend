<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class MailboxParamsFactory
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var string
     */
    private $host;
    /**
     * @var string
     */
    private $channel;

    public function __construct(RouterInterface $router, string $host, string $channel)
    {
        $this->router = $router;
        $this->host = $host;
        $this->channel = $channel;
    }

    public function getBasicMailboxParams(Usr $user, ?int $agentId, string $email): array
    {
        // hack for google oauth: google requires localhost, but mailbox scanner requires awardwallet.docker for callback url
        // so we open browser on http://localhost:8081, but construct callbackUrl with parameters from config (awardwallet.docker)
        $hostAndProto = $this->channel . "://" . $this->host;

        return [
            "startFrom" => date("Y-m-d", strtotime("-2 year")),
            "listen" => true,
            "callbackUrl" => $hostAndProto . $this->router->generate("aw_emailcallback_save", [], UrlGeneratorInterface::ABSOLUTE_PATH),
            "tags" => ["user_" . $user->getUserid(), 'agent_' . $agentId],
            "userData" => json_encode(["user" => $user->getUserid(), 'userAgent' => $agentId, "email" => $email]),
            "onProgress" => [
                "callbackUrl" => $hostAndProto . $this->router->generate("aw_mailbox_progress", [], UrlGeneratorInterface::ABSOLUTE_PATH),
            ],
        ];
    }
}
