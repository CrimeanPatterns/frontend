<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Utils\None;
use AwardWallet\MainBundle\Globals\Utils\Result\ResultInterface;
use AwardWallet\MainBundle\Repository\UserOAuthRepository;
use AwardWallet\MainBundle\Security\OAuth\Tokens;
use AwardWallet\MainBundle\Service\BackgroundCheckUpdater;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\ConnectImapMailboxRequest;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\ConnectOAuthMailboxRequest;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\ImapMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\OAuthMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\UpdateImapMailboxRequest;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\UpdateOAuthMailboxRequest;
use AwardWallet\MainBundle\Service\GoogleAnalytics4;
use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\Result\success;

class MailboxManager
{
    public const SESSION_KEY_ADDED_MAILBOX_TYPE = 'mailbox_type';

    private EmailScannerApi $emailScannerApi;

    private LoggerInterface $logger;

    private MailboxParamsFactory $mailboxParamsFactory;

    private GoogleAnalytics4 $googleAnalytics;

    private MailboxFinder $mailboxFinder;

    private \Memcached $memcached;

    private AntibruteforceChecker $antibruteforceChecker;

    private SessionInterface $session;

    private UserOAuthRepository $userOAuthRepository;

    private ValidMailboxesUpdater $validMailboxesUpdater;

    private BackgroundCheckUpdater $backgroundCheckUpdater;

    public function __construct(
        EmailScannerApi $emailScannerApi,
        LoggerInterface $logger,
        MailboxParamsFactory $mailboxParamsFactory,
        GoogleAnalytics4 $googleAnalytics,
        MailboxFinder $mailboxFinder,
        \Memcached $memcached,
        AntibruteforceChecker $antibruteforceChecker,
        SessionInterface $session,
        UserOAuthRepository $userOAuthRepository,
        ValidMailboxesUpdater $validMailboxesUpdater,
        BackgroundCheckUpdater $backgroundCheckUpdater
    ) {
        $this->emailScannerApi = $emailScannerApi;
        $this->logger = $logger;
        $this->mailboxParamsFactory = $mailboxParamsFactory;
        $this->googleAnalytics = $googleAnalytics;
        $this->mailboxFinder = $mailboxFinder;
        $this->memcached = $memcached;
        $this->antibruteforceChecker = $antibruteforceChecker;
        $this->session = $session;
        $this->userOAuthRepository = $userOAuthRepository;
        $this->validMailboxesUpdater = $validMailboxesUpdater;
        $this->backgroundCheckUpdater = $backgroundCheckUpdater;
    }

    /**
     * @return ResultInterface<Mailbox, string>
     */
    public function addImap(Usr $user, string $email, string $password, ?int $agentId): ResultInterface
    {
        $antibruteforceResult = $this->checkImapAntibruteforce($user, $email, $password);

        if ($antibruteforceResult->isFail()) {
            return $antibruteforceResult;
        }

        $this->logger->info("adding imap mailbox", ["email" => $email]);
        $mailbox = $this->emailScannerApi->connectImapMailbox(new ConnectImapMailboxRequest(array_merge(
            $this->mailboxParamsFactory->getBasicMailboxParams(
                $user,
                $agentId,
                $email
            ),
            [
                "login" => $email,
                "password" => $password,
            ]
        )));
        $this->resetCacheCounter($user);
        $this->logger->info("added imap mailbox", ["mailboxId" => $mailbox->getId(), 'email' => $email]);

        return success($mailbox);
    }

    /**
     * @return ResultInterface<None, string>
     */
    public function updateImap(Usr $user, ImapMailbox $mailbox, string $email, string $password): ResultInterface
    {
        $antibruteforceResult = $this->checkImapAntibruteforce($user, $email, $password);

        if ($antibruteforceResult->isFail()) {
            return $antibruteforceResult;
        }

        $this->logger->info("updating existing mailbox {$mailbox->getLogin()} (imap) at id {$mailbox->getId()}", ["mailboxId" => $mailbox->getId(), "email" => $mailbox->getLogin()]);
        $this->emailScannerApi->putMailbox(
            $mailbox->getId(),
            new UpdateImapMailboxRequest(
                [
                    "login" => $email,
                    "password" => $password,
                ]
            )
        );

        $this->logger->info("updated imap mailbox", ["mailboxId" => $mailbox->getId(), "email" => $mailbox->getLogin()]);

        return success();
    }

    public function delete(Usr $user, int $id): ?Mailbox
    {
        $mailbox = $this->mailboxFinder->findById($user, $id);

        if (!$mailbox) {
            return null;
        }

        $this->logger->info("removing existing mailbox at id {$mailbox->getId()}",
            $logContext = it(call(function () use ($mailbox) {
                yield "mailboxId" => $mailbox->getId();

                $email = null;

                if ($mailbox instanceof OAuthMailbox) {
                    $email = $mailbox->getEmail();
                } elseif ($mailbox instanceof ImapMailbox) {
                    $email = $mailbox->getLogin();
                }

                if (isset($email)) {
                    yield 'email' => $email;
                }
            }))->toArrayWithKeys()
        );

        $this->emailScannerApi->disconnectMailbox($id, true);
        $this->resetCacheCounter($user);

        if ($mailbox instanceof OAuthMailbox && $mailbox->getEmail() !== null) {
            $this->declineMailboxAccessOnOAuthLogin($user->getId(), $mailbox->getType(), $mailbox->getEmail());
        }

        $this->logger->info("mailbox removed", $logContext);

        return $mailbox;
    }

    public function linkMailbox(
        Usr $user,
        ?int $agentId,
        string $type,
        string $email,
        Tokens $tokens
    ): ResultInterface {
        /** @var OAuthMailbox $existingMailbox */
        $existingMailbox = $this->mailboxFinder->findFirstByEmailAndType($user, $email, $type);

        if ($existingMailbox) {
            return $this->updateOauth($user, $existingMailbox, $tokens);
        }

        return $this->addOauth($user, $agentId, $type, $email, $tokens);
    }

    public function deleteAllUserMailboxes(Usr $user): void
    {
        $this->logger->info("deleting all user mailboxes", ["userId" => $user->getId()]);
        $mailboxes = $this->mailboxFinder->findAllByUser($user);

        foreach ($mailboxes as $mailbox) {
            $this->delete($user, $mailbox->getId());
        }
    }

    /**
     * @return ResultInterface<None, string>
     */
    protected function checkImapAntibruteforce(Usr $user, string $email, string $password): ResultInterface
    {
        return $this->antibruteforceChecker->check([
            AntibruteforceChecker::IP_CHECK => true,
            AntibruteforceChecker::EMAIL_CHECK => $email,
            AntibruteforceChecker::PASSWORD_CHECK => $password,
            AntibruteforceChecker::USER_CHECK => $user->getUserid(),
        ]);
    }

    /**
     * @return ResultInterface<None, string>
     */
    protected function checkOauthAntibruteforce(Usr $user): ResultInterface
    {
        return $this->antibruteforceChecker->check([
            AntibruteforceChecker::IP_CHECK => true,
            AntibruteforceChecker::USER_CHECK => $user->getUserid(),
        ]);
    }

    protected function resetCacheCounter(Usr $user): void
    {
        $this->validMailboxesUpdater->updateCounter($user->getId());
        $this->backgroundCheckUpdater->updateUser($user->getId());
    }

    /**
     * @return ResultInterface<None, string>
     */
    private function updateOauth(Usr $user, OAuthMailbox $mailbox, Tokens $tokens): ResultInterface
    {
        $antibruteforceResult = $this->checkOauthAntibruteforce($user);

        if ($antibruteforceResult->isFail()) {
            return $antibruteforceResult;
        }

        $this->logger->info("updating existing mailbox {$mailbox->getEmail()} (oauth) at id {$mailbox->getId()}, access token: " . Strings::cutInMiddle($tokens->getAccessToken(), 4), ["mailboxId" => $mailbox->getId(), 'email' => $mailbox->getEmail()]);
        $this->emailScannerApi->putMailbox(
            $mailbox->getId(),
            new UpdateOAuthMailboxRequest([
                'accessToken' => $tokens->getAccessToken(),
                'refreshToken' => $tokens->getRefreshToken(),
                'userData' => json_encode(Messenger::resetNotificationsData(json_decode($mailbox->getUserData(), true))),
            ])
        );
        $this->logger->info("updated oauth mailbox", ["mailboxId" => $mailbox->getId(), 'email' => $mailbox->getEmail()]);

        return success();
    }

    /**
     * @return ResultInterface<Mailbox, string>
     */
    private function addOauth(Usr $user, ?int $agentId, string $type, string $email, Tokens $tokens): ResultInterface
    {
        $antibruteforceResult = $this->checkOauthAntibruteforce($user);

        if ($antibruteforceResult->isFail()) {
            return $antibruteforceResult;
        }

        $this->logger->info("adding oauth mailbox", ["email" => $email, "access_token" => Strings::cutInMiddle($tokens->getAccessToken(), 4), "refresh_token" => Strings::cutInMiddle($tokens->getRefreshToken(), 4)]);
        $addRequest = new ConnectOAuthMailboxRequest(
            array_merge($this->mailboxParamsFactory->getBasicMailboxParams($user, $agentId, $email), [
                "email" => $email,
                "accessToken" => $tokens->getAccessToken(),
                "refreshToken" => $tokens->getRefreshToken(),
            ])
        );

        if ($type === Mailbox::TYPE_GOOGLE) {
            $mailbox = $this->emailScannerApi->connectGoogleMailbox($addRequest);
        } elseif ($type === Mailbox::TYPE_MICROSOFT) {
            $mailbox = $this->emailScannerApi->connectMicrosoftMailbox($addRequest);
        } elseif ($type === Mailbox::TYPE_YAHOO) {
            $mailbox = $this->emailScannerApi->connectYahooMailbox($addRequest);
        } elseif ($type === Mailbox::TYPE_AOL) {
            $mailbox = $this->emailScannerApi->connectAolMailbox($addRequest);
        } else {
            throw new \Exception("Unknown mailbox type: $type");
        }

        $this->resetCacheCounter($user);
        $this->logger->info("added oauth mailbox", ["mailboxId" => $mailbox->getId(), 'email' => $email]);
        $this->session->getFlashBag()->add(self::SESSION_KEY_ADDED_MAILBOX_TYPE, $type);

        return success($mailbox);
    }

    private function declineMailboxAccessOnOAuthLogin(int $userId, string $oauthProvider, string $email): void
    {
        /** @var UserOAuth $oauth */
        $oauth = $this->userOAuthRepository->findOneBy(['user' => $userId, 'provider' => $oauthProvider, 'email' => $email]);

        if ($oauth !== null) {
            $oauth->setDeclinedMailboxAccess(true);
            $this->userOAuthRepository->save($oauth);
        }
    }
}
