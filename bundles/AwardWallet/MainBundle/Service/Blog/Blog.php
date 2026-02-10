<?php

namespace AwardWallet\MainBundle\Service\Blog;

use Psr\Log\LoggerInterface;

class Blog
{
    private LoggerInterface $logger;
    private string $blogApiSecret;

    public function __construct(
        LoggerInterface $logger,
        string $blogApiSecret
    ) {
        $this->logger = $logger;
        $this->blogApiSecret = $blogApiSecret;
    }

    public function decrypt(string $data, string $algo = 'AES-128-CBC'): ?string
    {
        if (empty($data)) {
            return null;
        }

        $c = base64_decode($data);
        $ivlen = openssl_cipher_iv_length($algo);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);

        if (empty($ciphertext_raw)) {
            return null;
        }

        $original_plaintext = openssl_decrypt(
            $ciphertext_raw,
            $algo,
            $this->blogApiSecret,
            OPENSSL_RAW_DATA,
            $iv
        );
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $this->blogApiSecret, true);

        if (hash_equals($hmac, $calcmac)) {
            return $original_plaintext;
        }

        return null;
    }
}
