<?php

namespace AwardWallet\MainBundle\Service\Blog;

class BlogPostMock implements BlogPostInterface
{
    public function fetchLastPost($count = 1)
    {
        return null;
    }

    public function fetchPostById($postIds, $withoutCache = false): ?array
    {
        return null;
    }

    public function fetchPostByOptions(array $options): array
    {
        return [];
    }
}
