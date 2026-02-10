<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Blog\Model\PostItem;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class BlogPost implements BlogPostInterface
{
    public const API_JSON_URL = 'https://awardwallet.com/blog/wp-json/';

    public const IS_DEV_TEST = false;

    public const OPTION_KEY_POST_ID = 'postId';
    public const OPTION_KEY_IGNORE_POST_ID = 'ignorePostId';
    public const OPTION_KEY_CATEGORY_ID = 'categoryId';
    public const OPTION_KEY_TAG_ID = 'tagId';
    public const OPTION_KEY_AFTER_DATE = 'afterDate';
    public const OPTION_KEY_BEFORE_DATE = 'beforeDate';
    public const OPTION_KEY_PAGE = 'page';
    public const OPTION_KEY_LIMIT = 'limit';
    public const OPTION_KEY_ORDER = 'order';
    public const OPTION_KEY_EVENT = 'event';

    private const LIMIT_SAVED_POSTS = 2;
    private const REQUEST_TIMEOUT = 5;

    private LoggerInterface $logger;
    private \CurlDriver $curlDriver;
    private \Memcached $cache;
    private string $blogApiSecret;
    private string $rootDir;

    public function __construct(
        LoggerInterface $logger,
        \HttpDriverInterface $curlDriver,
        \Memcached $memcached,
        string $blogApiSecret,
        string $rootDir
    ) {
        $this->logger = $logger;
        $this->curlDriver = $curlDriver;
        $this->cache = $memcached;
        $this->blogApiSecret = $blogApiSecret;
        $this->rootDir = $rootDir;
    }

    public function fetchLastPost($count = 1)
    {
        if ($count > self::LIMIT_SAVED_POSTS) {
            throw new \Exception('Saved posts limit exceeded');
        }

        $cacheKey = 'blog:' . __FUNCTION__ . '_v1_' . $count;
        $blogposts = $this->cache->get($cacheKey);

        if (!$blogposts) {
            $response = self::IS_DEV_TEST
                ? $this->fetchTestPost(['count' => $count])
                : $this->curlDriver->request(
                    new \HttpDriverRequest(
                        $this->getApiJsonUrl('get-homepage-post', ['limit' => self::LIMIT_SAVED_POSTS]),
                        Request::METHOD_POST,
                        null,
                        [] + $this->getAuthData(),
                        self::REQUEST_TIMEOUT
                    )
                );
            $blogposts = json_decode($response->body);

            if (empty($blogposts)) {
                $this->logger->critical('Error retrieving data, home page - blog');
                $this->cache->set($cacheKey, [Constants::BUSY => true], 60 * 15);

                return null;
            }

            $parsedBlogposts = [];

            foreach ($blogposts as $blogpost) {
                $validateKey = ['title', 'excerpt', 'author', 'authorURL', 'postURL', 'postDate'];

                foreach ($validateKey as $blogKey) {
                    if (empty($blogpost->$blogKey)) {
                        $this->logger->critical('Error of data processing, home page - blog');
                        $this->cache->set($cacheKey, [Constants::BUSY => true], 60 * 15);

                        return null;
                    } else {
                        $blogpost->$blogKey = html_entity_decode($blogpost->$blogKey);
                    }
                }
                $blogpost->postDate = new \DateTime($blogpost->postDate);
                $parsedBlogposts[] = $blogpost;
            }

            $this->cache->set($cacheKey, $parsedBlogposts, 60 * 5);
        }

        if (!is_array($blogposts) || isset($blogposts[Constants::BUSY])) {
            return null;
        }

        if (1 === $count) {
            return $blogposts[0];
        }

        return array_slice($blogposts, 0, $count);
    }

    public function fetchPostById($postIds, $withoutCache = false, array $options = []): ?array
    {
        if (is_string($postIds)) {
            $postIds = StringUtils::getIntArrayFromString($postIds);
        } elseif (!is_array($postIds)) {
            throw new \Exception('Invalid variable $postIds');
        }

        $posts = $this->fetchPostByOptions([self::OPTION_KEY_POST_ID => $postIds], $withoutCache);

        if (null === $posts) {
            return null;
        }

        $blogposts = $this->extractOldFormat($postIds, $posts);
        $result = [];

        $isOnlyFields = array_key_exists('fields', $options);
        $isReplaceArg = array_key_exists('replaceArg', $options);

        foreach ($postIds as $postId) {
            if (array_key_exists($postId, $blogposts)) {
                $result[$postId] = $blogposts[$postId];

                if ($isOnlyFields) {
                    foreach ($result[$postId] as $field => $item) {
                        if (!in_array($field, $options['fields'])) {
                            unset($result[$postId][$field]);
                        }
                    }
                }

                if ($isReplaceArg) {
                    $result[$postId]['postURL'] = StringUtils::replaceVarInLink(
                        $blogposts[$postId]['postURL'],
                        $options['replaceArg'],
                        true
                    );
                }
            }
        }

        return $result;
    }

    public function fetchByTag(array $tags, int $limit = 10): ?array
    {
        $cacheKey = 'blog:' . __FUNCTION__ . '_v1_' . sha1(implode('|', $tags)) . '_limit:' . $limit;
        $blogposts = $this->cache->get($cacheKey);

        if (!$blogposts) {
            $response = $this->curlDriver->request(
                new \HttpDriverRequest(
                    $this->getApiJsonUrl('get-posts-by-tag', ['limit' => $limit]),
                    Request::METHOD_POST,
                    ['tags' => $tags],
                    [] + $this->getAuthData(),
                    self::REQUEST_TIMEOUT
                )
            );
            $blogposts = json_decode($response->body);

            if (empty($blogposts)) {
                $this->cache->set($cacheKey, [Constants::BUSY => true], 60 * 60 * 1);

                return null;
            }

            if (!$this->validatePostsData($blogposts)) {
                throw new \Exception('Incorrect data');
            }

            $blogposts = $this->normalizeData($blogposts);
            $this->cache->set($cacheKey, $blogposts, 60 * 60 * 24);
        }

        if (empty($blogposts) || array_key_exists(Constants::BUSY, $blogposts)) {
            return null;
        }

        return $blogposts;
    }

    /**
     * @param array $options {afterDate: string, beforeDate:string, limit: int, order: string, postId:string|array}
     * $options[*date] format 'Y-m-d H:i'
     * $options[order] 'asc' or 'desc'
     * @return PostItem[]
     * @throws \Exception
     */
    public function fetchPostByOptions(array $options, bool $withoutCache = false): ?array
    {
        $cacheVersion = 2;
        $cacheExpiration = 60 * 60 * 6;
        $cacheKey = 'blog:' . __FUNCTION__ . '_v' . $cacheVersion . '_' . sha1(json_encode($options));

        $blogposts = null;
        $isPostIds = array_key_exists(self::OPTION_KEY_POST_ID, $options)
            && is_array($options[self::OPTION_KEY_POST_ID]);

        if (!$isPostIds) {
            $blogposts = $this->cache->get($cacheKey);
        } else {
            $postIds = $optionPostIds = array_map('intval', $options[self::OPTION_KEY_POST_ID]);
            $cachePrefix = 'blog:' . __FUNCTION__ . '_v' . $cacheVersion . '__postId-';
            $cachePosts = [];

            if (!$withoutCache) {
                foreach ($postIds as $key => $postId) {
                    $blogpost = $this->cache->get($cachePrefix . $postId);

                    if ($blogpost) {
                        $cachePosts[$postId] = $blogpost;
                        unset($postIds[$key]);
                    }
                }
            }
        }

        if (array_key_exists(self::OPTION_KEY_IGNORE_POST_ID, $options)
            && is_array($options[self::OPTION_KEY_IGNORE_POST_ID])
        ) {
            $ignoredPostIds = array_map('intval', $options[self::OPTION_KEY_IGNORE_POST_ID]);

            if (!empty($cachePosts)) {
                foreach ($ignoredPostIds as $postId) {
                    if (array_key_exists($postId, $cachePosts)) {
                        unset($cachePosts[$postId]);
                    }
                }
            }
        }

        if ($isPostIds && empty($postIds) && !empty($cachePosts)) {
            return $this->extractBlogposts($cachePosts);
        }

        if ($withoutCache || !$blogposts) {
            $response = self::IS_DEV_TEST
                ? $this->fetchTestPost($options)
                : $this->curlDriver->request(
                    new \HttpDriverRequest(
                        $this->getApiJsonUrl('posts/get-by-options'),
                        Request::METHOD_POST,
                        $options,
                        [] + $this->getAuthData(),
                        self::REQUEST_TIMEOUT
                    )
                );
            $blogposts = json_decode($response->body);

            if (empty($blogposts)) {
                $this->cache->set($cacheKey, [Constants::BUSY => true], 60 * 60);

                return null;
            }

            if ($isPostIds) {
                foreach ($blogposts as $blogpost) {
                    $this->cache->set($cachePrefix . $blogpost->id, $blogpost, $cacheExpiration);
                }

                $result = [];
                $blogposts = (array) $blogposts;

                foreach ($optionPostIds as $postId) {
                    $post = $blogposts[$postId] ?? $cachePosts[$postId] ?? null;

                    if (null !== $post) {
                        $result[$postId] = $post;
                    }
                }
                $blogposts = $result;
            } else {
                $this->cache->set($cacheKey, $blogposts, $cacheExpiration);
            }
        }

        if (empty($blogposts) || (is_array($blogposts) && array_key_exists(Constants::BUSY, $blogposts))) {
            return null;
        }

        return $this->extractBlogposts($blogposts);
    }

    private function extractBlogposts($blogposts): array
    {
        $result = [];

        foreach ((array) $blogposts as $blogpost) {
            $result[$blogpost->id] = new PostItem(
                $blogpost->id,
                $blogpost->title,
                $blogpost->description,
                $blogpost->thumbnail,
                new \DateTime($blogpost->pubDate),
                $blogpost->link,
                $blogpost->commentsCount,
                $blogpost->authorName,
                $blogpost->authorLink,
                (array) ($blogpost->authors ?? []),
                (array) ($blogpost->categories ?? []),
                (array) ($blogpost->tags ?? []),
                (array) ($blogpost->reviewed ?? [])
            );
        }

        return $result;
    }

    private function extractOldFormat(array $postIds, array $posts): array
    {
        $blogposts = [];

        foreach ($postIds as $postId) {
            /** @var PostItem $post */
            $post = $posts[$postId] ?? null;

            if (null === $post) {
                continue;
            }

            $blogposts[$postId] = [
                'id' => $postId,
                'title' => $post->getTitle(),
                'excerpt' => $post->getDescription(),
                'author' => $post->getAuthorName(),
                'authorURL' => $post->getAuthorLink(),
                'imageURL' => $post->getThumbnail(),
                'postURL' => $post->getLink(),
                'postDate' => $post->getPubDate()->format('Y-m-d H:i:s'),
                'commentsNumber' => $post->getCommentsCount(),
            ];
        }

        return $blogposts;
    }

    private function validatePostsData(array $blogposts): bool
    {
        $validateKey = ['title', 'excerpt', 'author', 'authorURL', 'postURL', 'postDate'];

        foreach ($blogposts as &$blogpost) {
            foreach ($validateKey as $field) {
                if (empty($blogpost->$field)) {
                    return false;
                }

                $blogpost->$field = html_entity_decode($blogpost->$field);
            }
        }

        return true;
    }

    private function normalizeData(array $blogposts): array
    {
        foreach ($blogposts as &$blogpost) {
            foreach ($blogpost as $key => $value) {
                if ('postDate' === $key) {
                    $blogpost->$key = new \DateTime($blogpost->postDate);
                }
            }
        }

        return $blogposts;
    }

    private function getApiJsonUrl(string $path, array $query = []): string
    {
        $url = self::API_JSON_URL;

        if (false === stripos($path, '/')) {
            $url .= 'api/';
        }
        $url .= ltrim($path, '/');

        return $url . '?' . http_build_query($query);
    }

    private function getAuthData(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode('blog:' . $this->blogApiSecret),
        ];
    }

    private function fetchTestPost(array $options): object
    {
        $data = json_decode(file_get_contents($this->rootDir . '/../tests/_data/Blog/posts.json'), true);

        if (!empty($options[self::OPTION_KEY_POST_ID])) {
            $ids = is_array($options[self::OPTION_KEY_POST_ID])
                ? $options[self::OPTION_KEY_POST_ID]
                : StringUtils::getIntArrayFromString($options[self::OPTION_KEY_POST_ID]);

            if (count($ids) > count($data)) {
                $data = array_merge($data, $data, $data, $data, $data);
            }

            $index = -1;

            foreach ($ids as $id) {
                $index++;

                if (empty($data[$index])) {
                    break;
                }

                $data[$index]['id'] = $id;
                $data[$index]['title'] .= ' (idTest: ' . $id . ')';
            }

            $data = array_slice($data, 0, count($ids));
        }

        if (!empty($options['fields']) && in_array('imageURL', $options['fields'])) {
            foreach ($data as $key => $post) {
                $data[$key]['imageURL'] = $post['imageURL'] ?? $post['thumbnail'];
                $data[$key]['postURL'] = $post['postURL'] ?? $post['link'];
            }
        }

        return (object) [
            'body' => json_encode($data),
        ];
    }
}
