<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Passwordvault;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AddPasswordVaultEvent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class AddPasswordVaultListener
{
    private const SLACK_BOT_NAME = 'Password Request';
    /** @var LoggerInterface */
    private $logger;
    /** @var Mailer */
    private $mailer;
    /** @var EntityManager */
    private $em;
    /** @var AppBot */
    private $appBot;
    private $basePath;

    public function __construct(LoggerInterface $logger, Mailer $mailer, EntityManager $em, AppBot $appBot, $basePath)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->em = $em;
        $this->appBot = $appBot;
        $this->basePath = $basePath;
    }

    public function onAddPasswordVault(AddPasswordVaultEvent $event)
    {
        $logData = [
            "provider" => $event->getProvider(),
            "partner" => $event->getPartner(),
            "login" => $event->getLogin(),
        ];
        $providerRepo = $this->em->getRepository(Provider::class);
        $provider = $providerRepo->findOneBy(['code' => $event->getProvider()]);

        if (!$provider instanceof Provider) {
            $this->logger->critical('PasswordRequest result Unavailable provider', $logData);

            return;
        }

        $emails = ["accountoperators@awardwallet.com"];
        $requestUserId = $event->getUserid();

        if (!empty($requestUserId)) {
            /** @var UsrRepository $userRepo */
            $userRepo = $this->em->getRepository(Usr::class);
            $usr = $userRepo->find($event->getUserId());

            if (!$usr instanceof Usr) {
                $this->logger->warning('PasswordRequest result Unavailable userId', $logData);
            } else {
                $emails[] = $usr->getEmail();
            }
        }

        require_once __DIR__ . "/../../../../../web/manager/passwordVault/common.php";

        if (!empty($event->getAccountId())) {
            /** @var Account $account */
            $account = $this->em->getRepository(Account::class)->find($event->getAccountId());

            if (!$account instanceof Account) {
                $this->logger->warning('Unknown AccountId for PasswordVault saving', $logData);

                return;
            }

            $pv = $this->em->getRepository(Passwordvault::class)->findOneBy(['accountid' => $account]);
        }

        if (isset($pv) && $pv instanceof Passwordvault) {
            $id = $pv->getPasswordvaultid();
        } else {
            $id = $this->addPasswordVault($provider->getProviderid(), $event);

            if (isset($account) && $account instanceof Account) {
                $pv = $this->em->getRepository(Passwordvault::class)->find($id);
                $pv->setAccountid($account)->setUserid($account->getUser());
                $this->em->persist($pv);
                $this->em->flush();
            }
        }

        if (empty($id)) {
            $this->logger->warning('Can`t save PasswordRequest result', $logData);

            return;
        }
        $this->logger->notice('PasswordRequest result saved', $logData);

        $messageParams = [
            'partner' => $event->getPartner(),
            'provider' => $provider->getCode(),
            'pass' => "{$this->basePath}/manager/passwordVault/get.php?ID={$id}",
            'note' => $event->getNote(),
        ];

        $this->mailer->send($this->buildEmailMessage($messageParams, $emails), [Mailer::OPTION_SKIP_STAT => true]);
        $this->appBot->send(Slack::CHANNEL_AW_REQUEST, $this->buildSlackMessage($messageParams), self::SLACK_BOT_NAME, ':aw:');
    }

    private function buildEmailMessage(array $params, array $emails): \Swift_Message
    {
        $body = "<p>Partner: {$params['partner']}</p>" .
            "<p>Provider: {$params['provider']}</p>" .
            "<p>Password: {$params['pass']}</p>" .
            "<p>Note: {$params['note']}</p>";

        $message = $this->mailer->getMessage();
        $message->setBody($body);
        $message->setSubject("Received password request");
        $message->setTo($emails);

        return $message;
    }

    private function buildSlackMessage(array $params): string
    {
        return "_Received password request!_\n" .
               "*Partner*: {$params['partner']}\n" .
               "*Provider*: {$params['provider']}\n" .
               "*Password*: {$params['pass']}\n" .
               "*Note*: {$params['note']}\n";
    }

    private function addPasswordVault(int $providerId, AddPasswordVaultEvent $event): ?int
    {
        return \addToPasswordVault(
            $providerId,
            $event->getLogin(),
            $event->getLogin2(),
            $event->getLogin3(),
            $event->getPassword(),
            null,
            $event->getPartner(),
            $event->getAnswers()
        );
    }
}
