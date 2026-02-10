<?php

namespace AwardWallet\MainBundle\Service\User;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class StateNotification
{
    private AppBot $appBot;
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private LocalizeService $localizeService;

    public function __construct(AppBot $appBot, EntityManagerInterface $entityManager, LocalizeService $localizeService)
    {
        $this->appBot = $appBot;
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->localizeService = $localizeService;
    }

    public function sendState(string $channel = Slack::CHANNEL_AW_ALL): bool
    {
        $message = [];
        $fn = fn ($value) => $this->localizeService->formatNumber($value);

        $dnsCount = $this->getDoNotSend();
        $ndrCount = $this->getNdr();
        $message[] = 'All users - Disable All Notifications: *' . $fn($dnsCount + $ndrCount) . '* users (unsubscribed: ' . $fn($dnsCount) . ', ndr: ' . $fn($ndrCount) . ')';

        $dnsCountUs = $this->getDoNotSend(true);
        $ndrCountUs = $this->getNdr(true);
        $message[] = 'All US users - Disable All Notifications: *' . $fn($dnsCountUs + $ndrCountUs) . '* users (unsubscribed: ' . $fn($dnsCountUs) . ', ndr: ' . $fn($ndrCountUs) . ')';

        $message[] = 'All users - Disable Promotional Offers: *' . $fn($this->getDisabledPromotionOffers()) . '* users';
        $message[] = 'All US users - Disable Promotional Offers: *' . $fn($this->getDisabledPromotionOffers(true)) . '* users';

        $message[] = 'All users - Disable New Blog Posts: *' . $fn($this->getNewBlogPostWithNever()) . '* users';
        $message[] = 'All US users - Disable New Blog Posts: *' . $fn($this->getNewBlogPostWithNever(true)) . '* users';

        return $this->appBot->send($channel, implode("\n", $message));
    }

    public function getDoNotSend(bool $isUs = false, bool $isExcludeNdr = true): int
    {
        if ($isUs) {
            $exclude = $isExcludeNdr ? ' AND u.EmailVerified <> ' . Usr::EMAIL_NDR : '';

            return (int) $this->connection->fetchOne('
                SELECT COUNT(*) FROM DoNotSend dns
                LEFT JOIN Usr u ON (u.Email = dns.Email)
                WHERE u.IsUs = 1 ' . $exclude . '
            ');
        }

        $exclude = $isExcludeNdr ? 'LEFT JOIN Usr u ON (u.Email = dns.Email) WHERE u.EmailVerified <> ' . Usr::EMAIL_NDR : '';

        return (int) $this->connection->fetchOne('
            SELECT COUNT(*) FROM DoNotSend dns
            ' . $exclude . '
            
        ');
    }

    public function getNdr(bool $isUs = false): int
    {
        if ($isUs) {
            return (int) $this->connection->fetchOne('
                SELECT COUNT(*) FROM Usr u WHERE u.IsUs = 1 AND u.EmailVerified = ' . Usr::EMAIL_NDR . '
            ');
        }

        return (int) $this->connection->fetchOne('
            SELECT COUNT(*) FROM Usr u WHERE u.EmailVerified = ' . Usr::EMAIL_NDR . '
        ');
    }

    public function getDisabledPromotionOffers(bool $isUS = false): int
    {
        $usCondition = $isUS ? 'AND u.IsUs = 1' : '';

        return (int) $this->connection->fetchOne('
            SELECT COUNT(*)
            FROM Usr u
            WHERE
                    u.EmailOffers = 0
                AND u.UserID NOT IN (' . $this->getSqlUIDWithDisabledEmail() . ')
                AND u.EmailVerified <> ' . Usr::EMAIL_NDR . '
                ' . $usCondition . '
        ');
    }

    public function getNewBlogPostWithNever(bool $isUS = false): int
    {
        $usCondition = $isUS ? 'AND u.IsUs = 1' : '';

        return (int) $this->connection->fetchOne('
            SELECT COUNT(*)
            FROM Usr u
            WHERE
                    u.EmailNewBlogPosts = ' . NotificationModel::BLOGPOST_NEW_NOTIFICATION_NEVER . '
                AND u.UserID NOT IN (' . $this->getSqlUIDWithDisabledEmail() . ')
                AND u.EmailVerified <> ' . Usr::EMAIL_NDR . '
                ' . $usCondition . '
        ');
    }

    private function getSqlUIDWithDisabledEmail(): string
    {
        return 'SELECT u.UserID FROM DoNotSend dns JOIN Usr u ON (u.Email = dns.Email)';
    }
}
