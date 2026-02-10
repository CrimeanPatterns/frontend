<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\MessageContextInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class SendTracker
{
    /**
     * @var UsrRepository
     */
    private $usrRepository;
    /**
     * @var UseragentRepository
     */
    private $useragentRepository;
    /**
     * @var LoggerInterface
     */
    private $mailLogger;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $query;
    /**
     * @var string
     */
    private $protoAndHost;
    /**
     * @var string
     */
    private $host;
    /**
     * @var UrlSigner
     */
    private $urlSigner;

    public function __construct(
        UsrRepository $usrRepository,
        UseragentRepository $useragentRepository,
        LoggerInterface $mailLogger,
        Connection $connection,
        string $protoAndHost,
        UrlSigner $urlSigner
    ) {
        $this->usrRepository = $usrRepository;
        $this->useragentRepository = $useragentRepository;
        $this->mailLogger = $mailLogger;
        $this->query = $connection->prepare("insert into SentEmail (ID, Context) values(:id, :context)");
        $this->protoAndHost = $protoAndHost;
        $this->host = parse_url($this->protoAndHost, PHP_URL_HOST);
        $this->urlSigner = $urlSigner;
    }

    public function prepareTracking(\Swift_Message $message, bool $openTracking, bool $clickTracking): SendTrackingInfo
    {
        $id = random_bytes(16);

        if (!$openTracking && !$clickTracking) {
            return new SendTrackingInfo($id, $message, null);
        }

        $body = $message->getBody();
        $hexId = bin2hex($id);

        if ($openTracking) {
            $body = preg_replace("#({$this->protoAndHost}/logo/\d+/\w+/)logo.png#ims", '${1}' . $hexId . '.png', $body);
            $body = str_replace("/emtrpx/0000.png", '/emtrpx/' . $hexId . '.png', $body);
        }

        if ($clickTracking) {
            $body = preg_replace_callback(
                '#href=([\'"])http(s?)://(?<host>[^\'"/\#]+)(?<path>(/[^\'"\#]*)|)(\#(?<fragment>[^\'"]+))?\1#ims',
                function (array $matches) use ($hexId) {
                    if (strcasecmp($matches['host'], $this->host) === 0) {
                        // blog is hosted as standalone wordpress, track links as redirects
                        if (strpos($matches['path'], '/blog') === 0) {
                            // return blog links as relative redirects
                            $matches = $this->convertFragment($matches);

                            return "href={$matches[1]}{$this->protoAndHost}/t/{$hexId}/a{$matches['path']}{$matches[1]}}";
                        }
                        // return awardwallet links in form ?emtr=xxx, to allow mobile links
                        $matches['path'] .= (strpos($matches['path'], '?') !== false ? '&' : '?')
                            . 'emtr=' . urlencode($hexId);

                        if (!empty($matches['fragment'])) {
                            $matches['path'] .= '#' . $matches['fragment'];
                        }

                        return "href={$matches[1]}{$this->protoAndHost}{$matches['path']}{$matches[1]}";
                    }
                    $matches = $this->convertFragment($matches);
                    // sign external links to prevent open redirects
                    // replace &amp; with & in links
                    $matches['path'] = html_entity_decode($matches['path']);
                    $sha = $this->urlSigner->getSign($matches['host'] . $matches['path']);

                    return "href={$matches[1]}{$this->protoAndHost}/t/{$hexId}/e/{$sha}/{$matches['host']}{$matches['path']}{$matches[1]}";
                },
                $body
            );
        }
        $message->setBody($body);

        return new SendTrackingInfo($id, $message, $body);
    }

    public function track(string $to, SendTrackingInfo $info, bool $sent)
    {
        if ($sent) {
            $this->logSentMessage($info->getId(), $to, $info->getMessage());
        }
        $bodyBackup = $info->getBodyBackup();

        if ($bodyBackup !== null) {
            $info->getMessage()->setBody($bodyBackup);
        }
    }

    private function logSentMessage(string $id, string $to, \Swift_Message $message)
    {
        if ($message instanceof MessageContextInterface) {
            $context = $message->getContext();
        } else {
            $context = [];
        }

        if (!isset($context['UserID'])) {
            $userId = $this->findUserIdByTo($to);

            if (isset($userId)) {
                $context['UserID'] = $userId;
            }
        }

        $from = $message->getFrom();

        if (is_array($from)) {
            $from = array_keys($from)[0];
        }
        $context = array_merge($context, [
            "from" => $from,
            "to" => $to,
            "subject" => $message->getSubject(),
        ]);
        $this->mailLogger->info("email sent", array_merge($context, ["id" => bin2hex($id)]));
        $this->query->execute(["id" => $id, "context" => json_encode($context)]);
    }

    private function findUserIdByTo(string $to): ?int
    {
        /** @var Usr|Useragent $user */
        $user = $this->findUser($to);
        $userId = null;

        if ($user) {
            if ($user instanceof Usr) {
                $userId = $user->getUserid();
            } elseif ($user instanceof Useragent) {
                $userId = $user->getAgentid()->getUserid();
            }
        }

        return $userId;
    }

    /**
     * @param string $email
     * @return Usr|Useragent
     */
    private function findUser($email)
    {
        /** @var Usr $user */
        $user = $this->usrRepository->findOneBy(['email' => $email]);

        if (!$user) {
            /** @var Useragent $ua */
            $ua = $this->useragentRepository
                ->findOneBy([
                    'email' => $email,
                    'clientid' => null,
                ]);

            if ($ua) {
                return $ua;
            }
        }

        return $user;
    }

    private function convertFragment(array $matches): array
    {
        if (!empty($matches['fragment'])) {
            // convert fragment to query string param, to allow redirect with fragment
            $matches['path'] .= (strpos($matches['path'], '?') !== false ? '&' : '?')
                . 'fragment=' . urlencode($matches['fragment']);
        }

        return $matches;
    }
}
