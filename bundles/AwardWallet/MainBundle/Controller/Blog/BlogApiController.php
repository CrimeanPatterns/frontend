<?php

namespace AwardWallet\MainBundle\Controller\Blog;

use AwardWallet\MainBundle\Entity\BlogComment;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Blog\BlogApi;
use AwardWallet\MainBundle\Service\Blog\BlogNewPostMailSender;
use AwardWallet\MainBundle\Service\Blog\BlogNewPostPushSender;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BlogApiController extends AbstractController
{
    private const NOTIFICATION_NEW_POST_TTL_SKIP = 'blog_notification_newpost_skip';

    private EntityManagerInterface $entityManager;
    private BlogApi $blogApi;

    public function __construct(
        EntityManagerInterface $entityManager,
        BlogApi $blogApi
    ) {
        $this->entityManager = $entityManager;
        $this->blogApi = $blogApi;
    }

    /**
     * @Route("/api/blog/new-post", methods={"POST"}, name="aw_blog_new_post")
     * @return JsonResponse
     */
    public function newPost(
        Request $request,
        Process $process,
        \Memcached $memcached,
        LoggerInterface $logger
    ) {
        $this->blogApi->checkAuth($request);

        $response = ['invalid' => []];
        $emails = array_values($request->request->get("emails", []));

        for ($i = -1, $iCount = count($emails); ++$i < $iCount;) {
            if (false === strpos($emails[$i], '@')) {
                $emails[$i] .= '@gmail.com';
            }

            if (false === filter_var($emails[$i], FILTER_VALIDATE_EMAIL)
                || empty($emails[$i])
            ) {
                $response['invalid'][] = $emails[$i];
                unset($emails[$i]);

                continue;
            }
        }
        $emails = array_values($emails);

        $blogpost = [
            'id' => (int) $request->get('postId'),
            'url' => $request->get('postUrl'),
            'image' => $request->get('postImgUrl'),
            'title' => $request->get('postName'),
            'announce' => $request->get('postExcerpt'),
            'author' => $request->get('postAuthor'),
            'date' => new \DateTime(),
        ];
        $logger->info('BlogApi: new-post notifications', $blogpost);

        if (empty($blogpost['url']) || empty($blogpost['title'])) {
            throw new \OutOfBoundsException('Fields "URL" and "Title" can not be empty');
        }

        $lastCallTime = $memcached->get(self::NOTIFICATION_NEW_POST_TTL_SKIP . $blogpost['id']);

        if (!empty($lastCallTime) && (time() - (int) $lastCallTime) < 3600) {
            $logger->alert('BlogApi: multiple re-launches /api/blog/new-post', $blogpost);

            throw new \Exception('BlogApi: multiple re-launches /api/blog/new-post');
        }

        $memcached->set(self::NOTIFICATION_NEW_POST_TTL_SKIP . $blogpost['id'], time(), 60 * 60);
        $memcached->delete('sitemap_news');

        foreach ($blogpost as $key => $value) {
            !is_string($value) ?: $blogpost[$key] = strip_tags(html_entity_decode($value));
        }

        $task = new Task(
            BlogNewPostMailSender::class, StringUtils::getRandomCode(20), null, [
                'emails' => $emails,
                'blogpost' => $blogpost,
            ]);
        $process->execute($task);

        $task = new Task(
            BlogNewPostPushSender::class, StringUtils::getRandomCode(20), null, [
                'blogpost' => $blogpost,
            ]);
        $process->execute($task);

        if (empty($response['invalid'])) {
            unset($response['invalid']);
        }

        return new JsonResponse(array_merge(['status' => 'success'], $response));
    }

    /**
     * @Route("/api/blog/new-comment", methods={"POST"}, name="aw_blog_new_comment")
     * @return JsonResponse
     */
    public function newComment(Request $request, LoggerInterface $logger)
    {
        $this->blogApi->checkAuth($request);

        $response = ['invalid' => []];
        $emails = array_values($request->request->get('emails', []));

        for ($i = 0, $iCount = \count($emails); $i < $iCount; $i++) {
            if (false === strpos($emails[$i], '@')) {
                $emails[$i] .= '@gmail.com';
            }

            if (false === filter_var($emails[$i], FILTER_VALIDATE_EMAIL)
                || empty($emails[$i])
            ) {
                $response['invalid'][] = $emails[$i];
                unset($emails[$i]);

                continue;
            }
        }
        $emails = array_values($emails);

        if (empty($emails)) {
            return new JsonResponse(['status' => 'success']);
        }

        $blogComment = [
            'PostTitle' => $request->get('postTitle'),
            'PostLink' => $request->get('postLink'),
            'PostUpdate' => $request->get('postUpdate', 'now'),
            'CommentCount' => $request->get('postComments'),
            'CommentAuthor' => $request->get('commentAuthor'),
            'CommentDate' => $request->get('commentDate', 'now'),
            'CommentEmail' => $request->get('commentEmail'),
            'CommentLink' => $request->get('commentLink'),
            'CommentContent' => $request->get('commentContent'),
            'Subscribers' => $emails,
        ];

        if (empty($blogComment['PostTitle']) || empty($blogComment['CommentAuthor']) || empty($blogComment['CommentContent'])) {
            $logger->warning($msg = 'Fields postTitle, commentAuthor, commentContent, emails can not be empty', $blogComment);

            throw new \OutOfBoundsException($msg);
        }

        $blogComment['PostUpdate'] = new \DateTime($blogComment['PostUpdate']);
        $blogComment['CommentDate'] = new \DateTime($blogComment['CommentDate']);

        $blogCommentEntity = new BlogComment();

        foreach ($blogComment as $fieldName => $value) {
            $setMethod = 'set' . $fieldName;

            if (method_exists($blogCommentEntity, $setMethod)) {
                $blogCommentEntity->{$setMethod}($value);
            }
        }

        $this->entityManager->persist($blogCommentEntity);
        $this->entityManager->flush();

        if (empty($response['invalid'])) {
            unset($response['invalid']);
        }

        return new JsonResponse(array_merge(['status' => 'success'], $response));
    }
}
