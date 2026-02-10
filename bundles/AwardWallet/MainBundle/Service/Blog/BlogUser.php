<?php

namespace AwardWallet\MainBundle\Service\Blog;

class BlogUser
{
    private string $blogApiSecret;

    public function __construct(
        string $blogApiSecret
    ) {
        $this->blogApiSecret = $blogApiSecret;
    }

    public function getUserUnsubscribeUrl(string $email): string
    {
        return Constants::URL . '?' . http_build_query(
            [
                'wp-subscription-manager' => 1,
                'email' => urlencode($email),
                'key' => $this->getUserKey($email),
            ]
        );
    }

    public function getUserKey(string $email): string
    {
        $email = urldecode($email);

        return md5(sha1($this->blogApiSecret . $email));
    }
}
