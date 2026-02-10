<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay;

use AwardWallet\MainBundle\Service\InAppPurchase\Exception\DecodeException;

class Decoder
{
    private string $publicKey;

    public function __construct(string $publicKey)
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @throws DecodeException
     */
    public function decode(string $signedData, string $signature)
    {
        if (empty($this->publicKey)) {
            throw new DecodeException('android public key is missing', DecodeException::CODE_MISSING_PUBLIC_KEY);
        }

        $key = "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split($this->publicKey, 64, "\n") .
            '-----END PUBLIC KEY-----';

        $key = openssl_pkey_get_public($key);

        if (false === $key) {
            throw new DecodeException('invalid public key', DecodeException::CODE_INVALID_PUBLIC_KEY);
        }

        $signature = base64_decode($signature);
        $result = @openssl_verify(
            $signedData,
            $signature,
            $key,
            OPENSSL_ALGO_SHA1
        );

        if (1 !== $result) {
            throw new DecodeException(openssl_error_string());
        }

        $info = @json_decode($signedData, true);

        if (JSON_ERROR_NONE !== json_last_error() || !is_array($info)) {
            throw new DecodeException(sprintf("Unable to parse JSON (%s): %s", $signedData, json_last_error()));
        }

        return $info;
    }
}
