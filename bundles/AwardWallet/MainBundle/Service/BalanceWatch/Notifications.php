<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\BalanceWatch\BalanceChanged;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\BalanceWatch\StartMonitored;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\BalanceWatch\Timeout;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\BalanceWatch\UpdateError;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Notification\Content as NotifyContent;
use AwardWallet\MainBundle\Service\Notification\Sender;
use Symfony\Contracts\Translation\TranslatorInterface;

class Notifications
{
    private Mailer $mailer;

    private LocalizeService $localizer;

    private TranslatorInterface $translator;

    private Sender $sender;

    public function __construct(
        Mailer $mailer,
        LocalizeService $localizer,
        TranslatorInterface $translator,
        Sender $sender
    ) {
        $this->mailer = $mailer;
        $this->localizer = $localizer;
        $this->translator = $translator;
        $this->sender = $sender;
    }

    public function sendAccountNotification(Account $account, int $notifyType, BalanceWatch $balanceWatch): bool
    {
        switch ($notifyType) {
            case Constants::EVENT_START_MONITORED:
                return $this->sendNotificationStartMonitored($account, $balanceWatch);

            case Constants::EVENT_BALANCE_CHANGED:
                return $this->sendNotificationBalanceChanged($account, $balanceWatch);

            case Constants::EVENT_TIMEOUT:
            case Constants::EVENT_FORCED_STOP:
                return $this->sendNotificationTimeout($account, $balanceWatch);

            case Constants::EVENT_UPDATE_ERROR:
                return $this->sendNotificationUpdateError($account, $balanceWatch);

            default:
                throw new \InvalidArgumentException(__METHOD__ . ' function takes only values by type of notifications');
        }
    }

    private function sendNotificationStartMonitored(Account $account, BalanceWatch $balanceWatch)
    {
        $template = new StartMonitored($balanceWatch->getPayerUser() ?? $account->getUser(), $balanceWatch->isBusiness());
        $this->sendPushNotification(
            $account,
            $balanceWatch,
            new NotifyContent(
                'AwardWallet',
                (new Trans('account.balancewatch.start-monitored.message', [
                    '%providerName%' => $account->getProviderid()->getShortname(),
                    '%accountLogin%' => $account->getAccountNumber(),
                ], 'email'))->addTransFormatter(\Closure::fromCallable('strip_tags')),
                NotifyContent::TYPE_BALANCE_WATCH,
                $account
            )
        );

        return $this->sendMailNotification($account, $template);
    }

    private function sendNotificationBalanceChanged(Account $account, BalanceWatch $balanceWatch)
    {
        $template = new BalanceChanged($balanceWatch->getPayerUser() ?? $account->getUser(), $balanceWatch->isBusiness());
        $this->sendPushNotification(
            $account,
            $balanceWatch,
            new NotifyContent(
                (new Trans('account.balancewatch.balance-change.subject', [
                    '%balanceChange%' => function (string $id, array $parameters, string $domain, string $locale) use ($account) {
                        return ($account->getBalance() >= $account->getLastbalance() ? '+' : '-') . $this->localizer->formatNumber(abs($account->getBalance() - $account->getLastbalance()), null, $locale);
                    },
                    '%providerName%' => $account->getProviderid()->getShortname(),
                    '%accountLogin%' => $account->getAccountNumber(),
                ], 'email'))->addTransFormatter(\Closure::fromCallable('strip_tags')),
                (new Trans('account.balancewatch.balance-change.message-short', [
                    '%balanceChange%' => function (string $id, array $parameters, string $domain, string $locale) use ($account) {
                        return ($account->getBalance() >= $account->getLastbalance() ? '+' : '-') . $this->localizer->formatNumber(abs($account->getBalance() - $account->getLastbalance()), null, $locale);
                    },
                    '%providerName%' => $account->getProviderid()->getShortname(),
                    '%accountLogin%' => $account->getAccountNumber(),
                    '%balanceFrom%' => function (string $id, array $parameters, string $domain, string $locale) use ($account) {
                        return $this->localizer->formatNumber($account->getLastbalance(), null, $locale);
                    },
                    '%balanceTo%' => function (string $id, array $parameters, string $domain, string $locale) use ($account) {
                        return $this->localizer->formatNumber($account->getBalance(), null, $locale);
                    },
                ], 'email'))->addTransFormatter(\Closure::fromCallable('strip_tags')),
                NotifyContent::TYPE_BALANCE_WATCH,
                $account
            )
        );

        return $this->sendMailNotification($account, $template);
    }

    private function sendNotificationTimeout(Account $account, BalanceWatch $balanceWatch)
    {
        $template = new Timeout($balanceWatch->getPayerUser() ?? $account->getUser(), $balanceWatch->isBusiness());
        $this->sendPushNotification(
            $account,
            $balanceWatch,
            new NotifyContent(
                (new Trans('account.balancewatch.timeout.subject', [
                    '%providerName%' => $account->getProviderid()->getShortname(),
                    '%accountLogin%' => $account->getAccountNumber(),
                ], 'email'))->addTransFormatter(\Closure::fromCallable('strip_tags')),
                (new Trans('account.balancewatch.timeout.message-short', [
                    '%providerName%' => $account->getProviderid()->getShortname(),
                    '%accountLogin%' => $account->getAccountNumber(),
                ], 'email'))->addTransFormatter(\Closure::fromCallable('strip_tags')),
                NotifyContent::TYPE_BALANCE_WATCH,
                $account
            )
        );

        return $this->sendMailNotification($account, $template);
    }

    private function sendNotificationUpdateError(Account $account, BalanceWatch $balanceWatch)
    {
        $template = new UpdateError($balanceWatch->getPayerUser() ?? $account->getUser(), $balanceWatch->isBusiness());
        $this->sendPushNotification(
            $account,
            $balanceWatch,
            new NotifyContent(
                (new Trans('account.balancewatch.update-error.subject', [
                    '%providerName%' => $account->getProviderid()->getShortname(),
                    '%accountLogin%' => $account->getAccountNumber(),
                ], 'email'))->addTransFormatter(\Closure::fromCallable('strip_tags')),
                (new Trans('account.balancewatch.update-error.message-short', [
                    '%providerName%' => $account->getProviderid()->getShortname(),
                    '%accountLogin%' => $account->getAccountNumber(),
                ], 'email'))->addTransFormatter(\Closure::fromCallable('strip_tags')),
                NotifyContent::TYPE_BALANCE_WATCH,
                $account
            )
        );

        return $this->sendMailNotification($account, $template);
    }

    private function sendMailNotification(Account $account, AbstractTemplate $template)
    {
        $template->account = $account;
        $template->providerName = $account->getProviderid()->getDisplayname();
        $message = $this->mailer->getMessageByTemplate($template);

        /*if (null !== ($balanceWatch = $this->getAccountBalanceWatch($account)) && null !== $balanceWatch->getPayerUser()) {
            $templatePayer = clone $template;
            $templatePayer->toUser($balanceWatch->getPayerUser());
            $messagePayer = $this->mailer->getMessageByTemplate($templatePayer);
            $message = [$message, $messagePayer];
        }*/

        return $this->mailer->send($message);
    }

    private function sendPushNotification(Account $account, BalanceWatch $balanceWatch, NotifyContent $content)
    {
        $recipient = $balanceWatch->getPayerUser() ?: $account->getUser();

        foreach ($this->sender->getUserDevicesQuery([$recipient], MobileDevice::TYPES_ALL, NotifyContent::TYPE_BALANCE_WATCH, true)->iterate() as $device) {
            $this->sender->send($content, $device);
        }

        return $this;
    }
}
