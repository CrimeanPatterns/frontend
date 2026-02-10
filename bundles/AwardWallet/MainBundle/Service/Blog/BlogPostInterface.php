<?php

namespace AwardWallet\MainBundle\Service\Blog;

interface BlogPostInterface
{
    public function fetchLastPost($count = 1);

    public function fetchPostById($postIds, $withoutCache = false): ?array;

    public function fetchPostByOptions(array $options): ?array;
}
