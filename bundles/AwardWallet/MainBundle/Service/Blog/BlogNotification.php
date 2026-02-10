<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BlogNotification
{
    public const TYPE_EMAIL = 1;
    public const TYPE_PUSH = 2;

    private LoggerInterface $logger;
    private \Memcached $cache;
    private AppBot $appBot;
    private LocalizeService $localizeService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        \Memcached $memcached,
        AppBot $appBot,
        LocalizeService $localizeService,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->cache = $memcached;
        $this->appBot = $appBot;
        $this->localizeService = $localizeService;
        $this->entityManager = $entityManager;
    }

    public function notifyAboutNewBlogPost(int $type, array $blogpost, array $stat): ?bool
    {
        $cacheKey = 'notifyBlogpostNew_' . $blogpost['id'];
        $notifyState = $this->cache->get($cacheKey);

        if (empty($notifyState)) {
            $notifyState = array_merge($blogpost, [
                'email' => null,
                'push' => null,
            ]);
        }

        if (self::TYPE_EMAIL === $type) {
            $notifyState['email'] = $stat;
        } elseif (self::TYPE_PUSH === $type) {
            $notifyState['push'] = $stat;
        }

        if (null !== $notifyState['email'] && null !== $notifyState['push']) {
            $dailyDigestCount = (int) $this->entityManager->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM Usr WHERE EmailNewBlogPosts = ' . NotificationModel::BLOGPOST_NEW_NOTIFICATION_DAY
            );
            $weeklyDigestCount = (int) $this->entityManager->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM Usr WHERE EmailNewBlogPosts = ' . NotificationModel::BLOGPOST_NEW_NOTIFICATION_WEEK
            );
            $message = [
                'New Blog Post has been published:',
                '*' . $blogpost['title'] . '*',
                'by ' . $blogpost['author'],
                $blogpost['url'],
                sprintf('%s mobile push notifications sent',
                    $this->localizeService->formatNumber($notifyState['push']['mobile']['anonymous'] + $notifyState['push']['mobile']['registered'])
                ),
                sprintf('%s desktop push notifications sent (%s registered, %s anonymous)',
                    $this->localizeService->formatNumber($notifyState['push']['desktop']['anonymous'] + $notifyState['push']['desktop']['registered']),
                    $this->localizeService->formatNumber($notifyState['push']['desktop']['registered']),
                    $this->localizeService->formatNumber($notifyState['push']['desktop']['anonymous'])
                ),
                sprintf('%s emails sent (%s registered, %s anonymous) [digest: %s daily, %s weekly]',
                    $this->localizeService->formatNumber($notifyState['email']['registered'] + $notifyState['email']['anonymous']),
                    $this->localizeService->formatNumber($notifyState['email']['registered']),
                    $this->localizeService->formatNumber($notifyState['email']['anonymous']),
                    $this->localizeService->formatNumber($dailyDigestCount),
                    $this->localizeService->formatNumber($weeklyDigestCount),
                ),
            ];

            $this->appBot->send(Slack::CHANNEL_AW_SOCIAL_MEDIA_EN, implode("\n", $message));
            $this->cache->delete($cacheKey);

            return true;
        } else {
            $this->logger->warning('BlogNotification error state data', $notifyState);
        }

        $this->cache->set($cacheKey, $notifyState, 60 * 60 * 24);

        return false;
    }
}
