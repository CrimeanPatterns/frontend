<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Entity\EmailCustomParam;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\EmailLog;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Blog\ListPost;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Blog\ListPostWeek;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\Blog\Model\PostItem;
use AwardWallet\MainBundle\Service\User\StateNotification;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailNotificationNewPost
{
    public const PERIOD_DAY = 'day';
    public const PERIOD_WEEK = 'week';

    public array $linkQueryParams = [
        'utm_source' => 'aw',
        'utm_medium' => 'email',
        'utm_campaign' => 'new_post_notify',
        'utm_content' => 'body1',
        'awid' => 'aw',
        'mid' => 'email',
        'cid' => 'new_post_notify',
    ];
    public array $linkQueryWeeklyParams = [
        'awid' => 'aw',
        'mid' => 'email',
        'cid' => 'weekly_digest',
        'utm_source' => 'aw',
        'utm_medium' => 'email',
        'utm_campaign' => 'weekly_digest',
        'rkbtyn' => 'USRREFCODE',
    ];

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private Mailer $mailer;
    private BlogPostInterface $blogPost;
    private EmailLog $emailLog;
    private string $sparkpostApiKey;
    private AppBot $appBot;
    private LocalizeService $localizeService;
    private Connection $unbufConnection;
    private StateNotification $stateNotification;
    private TranslatorInterface $translator;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Mailer $mailer,
        BlogPostInterface $blogPost,
        EmailLog $emailLog,
        $sparkpostApiKey,
        AppBot $appBot,
        LocalizeService $localizeService,
        Connection $unbufConnection,
        StateNotification $stateNotification,
        TranslatorInterface $translator
    ) {
        $this->logger = (new ContextAwareLoggerWrapper($logger))->setMessagePrefix('EmailNotificationNewPost: ');
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->blogPost = $blogPost;
        $this->emailLog = $emailLog;
        $this->sparkpostApiKey = $sparkpostApiKey;
        $this->appBot = $appBot;
        $this->localizeService = $localizeService;
        $this->unbufConnection = $unbufConnection;
        $this->stateNotification = $stateNotification;
        $this->translator = $translator;
    }

    public function execute(
        string $period,
        \DateTimeImmutable $endDate,
        array $options = [],
        ?OutputInterface $output = null
    ) {
        if (self::PERIOD_DAY === $period) {
            $emailBlogPostsValue = [
                NotificationModel::BLOGPOST_NEW_NOTIFICATION_DAY,
                // NotificationModel::BLOGPOST_NEW_NOTIFICATION_IMMEDIATE,
            ];
            $startDate = $endDate->sub(new \DateInterval('P1D'));
        } elseif (self::PERIOD_WEEK === $period) {
            $emailBlogPostsValue = [NotificationModel::BLOGPOST_NEW_NOTIFICATION_WEEK];
            $startDate = $endDate->sub(new \DateInterval('P7D'));
        } else {
            throw new \RuntimeException('Unknown "period" option "' . $period . '"');
        }

        $isPeriodDay = self::PERIOD_DAY === $period;
        $isPeriodWeek = self::PERIOD_WEEK === $period;
        $isDryRun = $options['isDryRun'] ?? false;
        $isSendNotify = $isPeriodWeek && !$isDryRun && empty($options['userId']) && !isset($options['isDisableNotify']);
        $isOutput = null !== $output && method_exists($output, 'writeln');
        $packCount = (int) ($options['pack'] ?? 5000);

        $blogPosts = $this->getBlogPosts($startDate, $endDate, $period);

        if (empty($blogPosts)) {
            $this->logger->info($msg = $period . ' No blog posts found for specified period');
            $isOutput && $output->writeln(['', $msg, '']);

            return $msg;
        }

        if ($isPeriodDay) {
            $template = new ListPost();
            $template->blogpost = $blogPosts;
        }

        if ($isPeriodWeek) {
            $customParam = $this->entityManager->getConnection()->fetchAssociative('
                SELECT Subject, Preview, Message, BlogDigestExcludeID
                FROM EmailCustomParam
                WHERE
                        Type = ' . EmailCustomParam::TYPE_BLOG_WEEKLY_DIGEST . '
                    AND CURDATE() = EventDate 
                ORDER BY EventDate DESC, EmailCustomParamID DESC
                LIMIT 1
            ');

            if (!empty($customParam['BlogDigestExcludeID'])) {
                $excludePostIds = $this->toIntStrArray($customParam['BlogDigestExcludeID']);

                foreach ($blogPosts as $key => $blogPost) {
                    if (in_array($blogPost->getId(), $excludePostIds)) {
                        unset($blogPosts[$key]);
                    }
                }

                $blogPosts = array_values($blogPosts);
            }

            $template = new ListPostWeek();
            $template->htmlGroups = $this->getHtmlGroupPosts($this->getGroupBlogPosts($blogPosts));

            if (!empty($customParam['Subject'])) {
                $template->subject = trim($customParam['Subject']);
            }

            if (!empty($customParam['Preview'])) {
                $template->preview = trim($customParam['Preview']);
            }

            if (!empty($customParam['Message'])) {
                $customMessage = self::replaceCkeditorStyles($customParam['Message']);
                $customMessage = $this->appendUtmLinks($customMessage);

                $template->customMessage = $customMessage;
            }
        }

        $template->period = $period;

        $findByCondition = [];

        if (isset($options['userId'])) {
            $findByCondition[] = 'u.UserID IN (' . implode(',', $this->toIntStrArray($options['userId'])) . ')';
        }

        if (!isset($options['ignoreResend'])) {
            $findByCondition[] = 'el.EmailLogID IS NULL';
        }

        $sql = "
            SELECT u.UserID
            FROM Usr u
            LEFT JOIN EmailLog el ON (el.UserID = u.UserID AND el.MessageKind = " . EmailLog::MESSAGE_KIND_BLOG_DIGEST . " AND (
                   DATE(NOW()) = DATE(EmailDate)
                OR DATE(SUBDATE(NOW(), 1)) = DATE(EmailDate))
            )
            LEFT JOIN DoNotSend dns ON (dns.Email = u.Email)
            WHERE
                    u.EmailNewBlogPosts IN (" . implode(',', $emailBlogPostsValue) . ")
                " . (empty($findByCondition) ? '' : 'AND ' . implode(' AND ', $findByCondition)) . "
                AND dns.DoNotSendID IS NULL
                AND u.EmailVerified <> " . Usr::EMAIL_NDR . "
        ";

        $userCount = (int) $this->entityManager->getConnection()->fetchOne(
            str_replace('SELECT u.UserID', 'SELECT COUNT(u.UserID)', $sql)
        );

        if (!$userCount) {
            $this->logger->info($msg = $period . ' -- no users for this period (or DoNotSend, or NDR)');
            $isOutput && $output->writeln(['', $msg, '']);

            return $msg;
        }

        if ($isSendNotify) {
            $sendTimeStart = new \DateTime();
            $this->stateNotification->sendState(Slack::CHANNEL_AW_BLOG);
            $this->appBot->send(
                Slack::CHANNEL_AW_BLOG,
                'Frontend » blog weekly digest - started sending emails to ' . $this->localizeService->formatNumber($userCount) . ' users'
            );
        }

        $counter = 0;
        $stat = ['success' => 0, 'failure' => 0, 'error' => 0, 'notUser' => 0];

        if ($isDryRun) {
            $stat['test'] = 0;
        }

        $userIdChunks = array_chunk($this->entityManager->getConnection()->fetchFirstColumn($sql), $packCount);
        $userRepository = $this->entityManager->getRepository(Usr::class);

        $timeStart = $timeStep = microtime(true);
        $isOutput && $output->writeln('START (pack=' . $packCount . ') users count: ' . $userCount);

        foreach ($userIdChunks as $userIds) {
            $users = $userRepository->findBy(['userid' => $userIds]);

            foreach ($users as $user) {
                $template->toUser($user);
                $message = $this->mailer->getMessageByTemplate($template);

                if (!$isDryRun) {
                    if ($this->mailer->send([$message], [
                        Mailer::OPTION_TRANSACTIONAL => false,
                        Mailer::OPTION_FIX_BODY => false,
                    ])) {
                        ++$stat['success'];
                        $this->emailLog->recordEmailToLog($user->getId(), EmailLog::MESSAGE_KIND_BLOG_DIGEST);
                    } else {
                        ++$stat['failure'];
                    }
                } else {
                    $stat['test']++;
                }

                if ($isOutput && 0 === ++$counter % $packCount) {
                    $output->writeln($msg = $counter . ' - send state '
                        . ' (step time: ' . round(microtime(true) - $timeStep, 6) . ' seconds)'
                        . ' (execution time: ' . round(microtime(true) - $timeStart, 2) . ' seconds)'
                        . ' - success: ' . $stat['success']
                        . ', failure: ' . $stat['failure']
                        . ', error: ' . $stat['error']
                        . ', notUser: ' . $stat['notUser']
                        . ($isDryRun ? ', test: ' . $stat['test'] : '')
                    );
                    $this->logger->info($msg);
                    $timeStep = microtime(true);
                }
            }

            $this->entityManager->clear();
        }

        $info = 'finish stat ' . date('Y-m-d')
            . ' success: ' . $stat['success']
            . ', failure: ' . $stat['failure']
            . ', error: ' . $stat['error']
            . ', notUser: ' . $stat['notUser']
            . ($isDryRun ? ', test: ' . $stat['test'] : '');
        $this->logger->info($info);
        $isOutput && $output->writeln(
            'END - Total Execution time: ' . round(microtime(true) - $timeStart, 2) . ' seconds'
            . "\r\n" . $info
        );

        if ($isSendNotify) {
            $time = $sendTimeStart->diff(new \DateTime('@' . time()));
            $sparkStat = null;

            if (is_array($sparkStat) && (int) $sparkStat['count_injected'] > 0) {
                $sparkpostInfo = "\nsparkpost stats » "
                    . 'injected: ' . $this->localizeService->formatNumber($sparkStat['count_injected']) . ', '
                    . 'sent: ' . $this->localizeService->formatNumber($sparkStat['count_sent']) . ', '
                    . 'accepted: ' . $this->localizeService->formatNumber($sparkStat['count_accepted']);
            }

            $this->appBot->send(
                Slack::CHANNEL_AW_BLOG,
                'Frontend » blog weekly digest - successfully finished sending ' . $this->localizeService->formatNumber($stat['success']) . ' emails after '
                . ($time->d > 0 ? $time->d . ' ' . $this->translator->trans('days', ['%count%' => $time->d]) . ' ' : '')
                . ($time->h > 0 ? ' ' . $this->translator->trans('hours', ['%count%' => $time->h]) . ' ' : '')
                . (0 === $time->d && $time->i > 1 ? $time->i . ' min ' : '')
                . (0 === $time->d && 0 === $time->h && 0 === $time->i ? $time->s . ' seconds ' : '')
                . ($sparkpostInfo ?? '')
            );
        }

        $isOutput && $output->writeln(['', 'done.', '']);

        return 0;
    }

    public static function replaceCkeditorStyles(?string $message): string
    {
        if (empty($message)) {
            return '';
        }

        return str_replace(
            ['<a ', ' class="bold"'],
            ['<a class="custom-link" style="text-decoration:none;color:#4684C4;" ', ' style="font-weight:bold;"'],
            $message
        );
    }

    /**
     * @return PostItem[]
     */
    private function getBlogPosts($startDate, $endDate, string $period): array
    {
        $this->logger->info('Fetch blogposts by: startDate >= ' . $startDate->format('Y-m-d H:i:s') . ' and endDate <= ' . $endDate->format('Y-m-d H:i:s'));

        $blogPosts = $this->blogPost->fetchPostByOptions([
            BlogPost::OPTION_KEY_AFTER_DATE => $startDate->format('Y-m-d H:i'),
            BlogPost::OPTION_KEY_BEFORE_DATE => $endDate->format('Y-m-d H:i'),
            BlogPost::OPTION_KEY_EVENT => 'digest',
        ]);

        if (empty($blogPosts)) {
            return [];
        }

        foreach ($blogPosts as $blogPost) {
            $link = $blogPost->getLink();
            $link = self::PERIOD_WEEK === $period
                ? StringHandler::replaceVarInLink($link, $this->linkQueryWeeklyParams)
                : StringHandler::replaceVarInLink($link, $this->linkQueryParams);

            $blogPost->setLink($link);
        }

        return $blogPosts;
    }

    private function getGroupBlogPosts($blogPosts): array
    {
        $group = Constants::CATEGORIES_ORDER;

        /** @var PostItem $blogPost */
        foreach ($blogPosts as $blogPost) {
            $link = $blogPost->getLink();
            $link = StringHandler::replaceVarInLink($link, $this->linkQueryWeeklyParams);
            $blogPost->setLink($link);

            $category = array_values($blogPost->getCategories())[0] ?? [];

            if (empty($category)) {
                continue;
            }

            if (!array_key_exists($category->catId, $group)) {
                $group[$category->catId] = (array) $category;
                $replacementCategory = $blogPost->getCategory();

                if (!empty($replacementCategory['name'])) {
                    $group[$category->catId]['name'] = $replacementCategory['name'];
                }
                $group[$category->catId]['icon'] = $replacementCategory['icon'];
                $group[$category->catId]['posts'] = [];
            }

            if (empty($group[$category->catId]['slug'])) {
                $group[$category->catId]['slug'] = $category->slug;
            }

            if (!array_key_exists('posts', $group[$category->catId])) {
                $group[$category->catId]['posts'] = [];
            }

            $authors = $blogPost->getAuthors();
            $authors['names'] = str_replace(
                '<a ',
                '<a class="authors-name" style="color: #9ea0a6;text-decoration: underline;font-size: 13px;font-weight:700;" ',
                strip_tags($authors['names'] ?? '', '<a>')
            );
            $authors['names'] = rtrim($authors['names'], '.');
            $blogPost->setAuthors($authors);

            $group[$category->catId]['posts'][] = $blogPost;
        }

        foreach ($group as $key => $item) {
            if (empty($item['posts'])) {
                unset($group[$key]);
            }
        }

        if (array_key_exists(Constants::CATEGORY_OTHER_TIPS_ID, $group)
            && !array_key_exists(Constants::CATEGORY_TRAVEL_BOOKING_TIPS_ID, $group)) {
            $group[Constants::CATEGORY_OTHER_TIPS_ID]['name'] = Constants::CATEGORY_OTHER_TIPS_ONCE_NAME;
        }

        return array_values($group);
    }

    private function toIntStrArray($value): array
    {
        $value = explode(',', $value);
        $value = array_map('trim', $value);
        $value = array_map('intval', $value);

        return array_filter(array_unique($value));
    }

    private function appendUtmLinks(string $content): string
    {
        $dom = new \DOMDocument();
        $xmlUtf8 = '<?xml encoding="UTF-8">';
        $dom->loadHTML($xmlUtf8 . $content, LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        /** @var \DOMElement $element */
        foreach ($dom->getElementsByTagName('a') as $element) {
            if (!$element->hasAttribute('href')) {
                continue;
            }

            $href = $element->getAttribute('href');

            if (false === strpos($href, 'awardwallet.')) {
                continue;
            }

            $href = StringHandler::replaceVarInLink($href, $this->linkQueryWeeklyParams);

            $element->setAttribute('href', $href);
        }

        $content = str_replace('javascript:', '#', rtrim($dom->saveHTML(), "\n"));
        $content = html_entity_decode($content);

        return str_replace($xmlUtf8, '', $content);
    }

    private function getHtmlGroupPosts(array $groups): string
    {
        $baseUrl = 'https://awardwallet.com';
        $baseImagesPath = $baseUrl . '/images/email/newdesign';

        $html = '<table><tbody>';

        foreach ($groups as $group) {
            $html .= '<tr>';
            $html .= '<td colspan="3" style="padding:0 !important;height:20px !important;min-height: 20px !important;"></td>';
            $html .= '</tr>';

            $html .= '<tr><td class="blog-group-cell" colspan="3" style="padding: 14px 10px 10px;background: #f9f9fa;">';
            $html .= '<a class="blog-group-title" href="https://awardwallet.com/blog/category/' . $group['slug'] . '" style="text-decoration: none;font-size: 14px;color:#3D424D;font-weight: 700;">';
            $html .= '<img src="' . $baseUrl . $group['icon'] . '" alt="" align="top" style="vertical-align: top;margin-right: 14px;">' . $group['name'] . '</a>';
            $html .= '</td></tr>';

            $html .= '<tr>';
            $html .= '<td colspan="3" style="padding:0 !important;height:15px !important;min-height: 15px !important;"></td>';
            $html .= '</tr>';

            /** @var PostItem $post */
            foreach ($group['posts'] as $post) {
                $link = urldecode(StringHandler::replaceVarInLink(
                    $post->getLink(),
                    array_merge($this->linkQueryWeeklyParams, ['rkbtyn' => 'USRREFCODE'])
                ));
                $authors = $post->getAuthors();
                $pubDate = $this->localizeService->formatDate($post->getPubDate(), LocalizeService::FORMAT_MEDIUM);

                $html .= '<tr>';

                $html .= '<td class="blog-post-thumb" style="width:60px;max-width:90px;padding:14px 20px 8px;vertical-align:top;box-sizing:border-box;">';
                $html .= '<a href="' . $link . '" target="_blank"><img src="' . $post->getThumbnail() . '" class="post-thumb" style="width:60px;max-width:80px;"></a>';
                $html .= '</td>';

                $html .= '<td style="padding:10px 10px 10px 15px;">';
                $html .= '<a class="title-link" href="' . $link . '" target="_blank" style="font-size: 14px; font-weight: 500; text-decoration: none;color: #4684C4;">' . $post->getTitle() . '</a>';

                $html .= '<div class="blog-info" style="margin-top: 5px;padding-top: 5px;display: flex;">';
                $html .= '<div class="blog-couathors-avatars blog-coauthors--' . $authors['count'] . '">';
                $index = -1;

                foreach ($authors['list'] as $author) {
                    $style = 'width:25px;height:25px;border-radius: 50%;';
                    $style .= ++$index > 0 ? 'padding-left:3px;' : '';
                    $html .= '<img src="' . $author->avatar . '" alt="' . $author->name . '" align="top" style="' . $style . '">';
                }
                $html .= '</div>';

                $html .= '<div class="blog-coauthors-names" style="font-size:13px;color:#9ea0a6;padding-left:5px;display: none;">by ' . $authors['names'] . '</div>';

                $html .= '<div class="blog-post-date" style="font-size:13px;color:#9ea0a6;padding-left:10px;padding-top: 2px;">';
                $html .= '<img src="' . $baseUrl . '/images/email/blog/ico/calendar.png" style="vertical-align: top;margin-top:2px;margin-right: 8px;width:14px !important;height:16px !important;">';
                $html .= '<span style="display: inline-block;padding-top:3px;color:#9ea0a6;">' . $pubDate . '</span>';
                $html .= '</div>';

                $html .= '</div>';

                $html .= '</td>';

                $html .= '<td class="b-arr-right" style="display:none;vertical-align: middle;padding: 10px 20px;">';
                $html .= '<a href="' . $link . '" target="_blank"><img src="' . $baseImagesPath . '/arrow-right.png" style="max-height: 16px"></a>';
                $html .= '</td>';

                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        return $html;
    }
}
