<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Entity\BlogUserPost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserPost
{
    private EntityManagerInterface $entityManager;
    private TokenStorageInterface $tokenStorage;
    private \Memcached $memcached;

    private array $cache;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        \Memcached $memcached
    ) {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->memcached = $memcached;
    }

    public function set(int $postId, int $type): bool
    {
        if (([$params, $types] = $this->getParams($postId, $type)) && null === $params) {
            return false;
        }

        $this->entityManager->getConnection()->executeQuery('
            INSERT IGNORE INTO BlogUserPost (Type, PostID, UserID)
            VALUES (:type, :postId, :userId)
        ',
            $params,
            $types
        );
        $this->clearCache($type);

        return true;
    }

    public function delete(int $postId, int $type): bool
    {
        if (([$params, $types] = $this->getParams($postId, $type)) && null === $params) {
            return false;
        }

        $this->entityManager->getConnection()->delete('BlogUserPost', $params, $types);
        $this->clearCache($type);

        return true;
    }

    public function has(int $postId, int $type): bool
    {
        if (([$params, $types] = $this->getParams($postId, $type)) && null === $params) {
            return false;
        }

        return in_array($postId, $this->get($type));
    }

    public function get(int $type): array
    {
        $userId = $this->getUserId();

        if (!$userId) {
            return [];
        }

        if (!empty($this->cache[$type])) {
            return $this->cache[$type];
        }

        $cacheKey = $this->getCacheKey($type);
        $data = $this->memcached->get($cacheKey);

        if (!$data) {
            $data = $this->entityManager->getConnection()->fetchFirstColumn('
                SELECT PostID FROM BlogUserPost WHERE Type = :type AND UserID = :userId
            ',
                ['type' => $type, 'userId' => $userId],
                ['type' => \PDO::PARAM_INT, 'userId' => \PDO::PARAM_INT]
            );

            $this->memcached->set($cacheKey, $data);
            $this->cache[$type] = $data;
        }

        return $data;
    }

    private function getParams(int $postId, int $type): ?array
    {
        $userId = $this->getUserId();

        if (!$postId || !$userId) {
            return [null, null];
        }

        if (!array_key_exists($type, BlogUserPost::TYPES)) {
            throw new \Exception('Invalid post type');
        }

        return [
            [
                'type' => $type,
                'postId' => $postId,
                'userId' => $userId,
            ],
            [
                'type' => \PDO::PARAM_INT,
                'postId' => \PDO::PARAM_INT,
                'userId' => \PDO::PARAM_INT,
            ],
        ];
    }

    private function getUserId(): ?int
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user instanceof UserInterface) {
            return $user->getId();
        }

        return null;
    }

    private function getCacheKey(int $type): string
    {
        $userId = $this->getUserId();

        if (!$userId) {
            $userId = 0;
        }

        return 'UserPost_v1_' . $userId . '_' . $type;
    }

    private function clearCache(int $type): void
    {
        $this->cache[$type] = [];
        $this->memcached->delete($this->getCacheKey($type));
    }
}
