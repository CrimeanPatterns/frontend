<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Blog\ListNewComment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BlogCommentNotification
{
    public const DEBUG = false;

    public const COMMENT_MIN_TIMEOUT = 60 * 5;

    private Mailer $mailer;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private BlogUser $blogUser;

    public function __construct(
        Mailer $mailer,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        BlogUser $blogUser
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->blogUser = $blogUser;
    }

    public function send($comments = null): ?bool
    {
        $comments = $comments ?? $this->getComments();

        if (empty($comments)
            || (time() - strtotime($comments[0]['CommentDate'])) < self::COMMENT_MIN_TIMEOUT) {
            return null;
        }

        $clearId = $emails = $blogpost = $unsubscribes = [];

        // Group comments by blogpost
        foreach ($comments as $item) {
            $clearId[] = $item['BlogCommentID'];
            $item['Subscribers'] = json_decode($item['Subscribers']);

            if (empty($item['Subscribers'])) {
                continue;
            }

            $postKey = md5($item['PostLink']);
            $tmp = $item;
            unset($tmp['PostLink'], $tmp['PostTitle'], $tmp['PostUpdate'], $tmp['CommentCount'], $tmp['Subscribers']);
            $tmp['avatarSrc'] = md5(strtolower($item['CommentEmail']));
            $tmp['CommentDate'] = new \DateTime('@' . strtotime($item['CommentDate']));
            $commentUniqKey = md5($tmp['CommentAuthor'] . $tmp['CommentContent']);

            if (!array_key_exists($postKey, $blogpost)) {
                $blogpost[$postKey] = [
                    'postTitle' => $item['PostTitle'],
                    'postLink' => $item['PostLink'],
                    'postUpdate' => $item['PostUpdate'],
                    'commentCount' => $item['CommentCount'],
                    'comments' => [$tmp],
                    'subscribers' => $item['Subscribers'],
                    '_uniqComment' => [$commentUniqKey],
                ];
            } elseif (false === array_search($commentUniqKey, $blogpost[$postKey]['_uniqComment'])) {
                $blogpost[$postKey]['comments'][] = $tmp;
                $blogpost[$postKey]['_uniqComment'][] = $commentUniqKey;
            }
        }

        // Determine the user, which topics to send
        foreach ($blogpost as $postKey => $post) {
            $tmp = $post;

            for ($i = 0, $iCount = \count($post['subscribers']); $i < $iCount; $i++) {
                $email = $post['subscribers'][$i];
                array_key_exists($email, $emails) ?: $emails[$email] = [];

                unset($tmp['subscribers']);
                $emails[$email][$postKey] = $tmp;
                $unsubscribes[$email] = $this->blogUser->getUserUnsubscribeUrl($email);
            }
        }

        $userRepository = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $template = new ListNewComment();
        $template->awUser = null;
        $sentCount = 0;

        foreach ($emails as $email => $post) {
            if (self::DEBUG) {
                $unsubscribes['test@awardwallet.com'] = 'https://awardwallet.com/blog/?-unsubscribe-for-' . urlencode($email);
            }
            /** @var Usr $user */
            $user = $userRepository->findBy(['email' => $email]);

            if (!empty($user)) {
                self::DEBUG ? $user[0]->setEmail('test@awardwallet.com') : null;
                $template->toUser($user[0]);
            } else {
                self::DEBUG ? $email = 'test@awardwallet.com' : null;

                $template->toEmail($email);
                // $template->user = new Usr();
                // $template->user->setLogin($email);
                // $template->user->setCreationdatetime(new \DateTime());
            }

            $template->blogPosts = $post;
            $template->unsubscribeUrl = $unsubscribes[$email];
            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send([$message]);
            $sentCount++;
        }

        if (!empty($clearId)) {
            $this->entityManager->getConnection()->executeQuery(
                'DELETE FROM BlogComment WHERE BlogCommentID IN(?)',
                [$clearId],
                [$this->entityManager->getConnection()::PARAM_INT_ARRAY]
            );
        }

        return true;
    }

    public function getComments()
    {
        return $this->entityManager->getConnection()->fetchAll('SELECT * FROM BlogComment ORDER BY CommentDate ASC');
    }
}
