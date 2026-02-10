<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;

class Desanitizer
{
    public const CHARS = 1 << 0;
    public const TAGS = 1 << 1;

    /**
     * @var ApiVersioningService
     */
    protected $apiVersioning;

    public function __construct(ApiVersioningService $apiVersioning)
    {
        $this->apiVersioning = $apiVersioning;
    }

    public function tryDesanitizeChars(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($this->apiVersioning->supports(MobileVersions::DESANITIZED_STRINGS)) {
            return htmlspecialchars_decode($value);
        }

        return $value;
    }

    public function tryDesanitizeTags(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($this->apiVersioning->supports(MobileVersions::DESANITIZED_STRINGS)) {
            return strip_tags($value, '<a>');
        }

        return $value;
    }

    public function tryFullDesanitize(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($this->apiVersioning->supports(MobileVersions::DESANITIZED_STRINGS)) {
            return strip_tags(htmlspecialchars_decode($value), '<a>');
        }

        return $value;
    }

    public function fullDesanitize(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return strip_tags(htmlspecialchars_decode($value), '<a>');
    }

    public function tryDesanitize(?string $value, int $flags = self::CHARS | self::TAGS): ?string
    {
        if ((self::CHARS | self::TAGS) & $flags) {
            return $this->tryFullDesanitize($value);
        }

        if (self::CHARS & $flags) {
            return $this->tryDesanitizeChars($value);
        }

        if (self::TAGS & $flags) {
            return $this->tryDesanitizeTags($value);
        }

        return null;
    }

    public function tryDesanitizeArray(array $data, array $desanitizedKeys, int $flags = self::CHARS | self::TAGS): array
    {
        foreach ($desanitizedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->tryDesanitize($data[$key], $flags);
            }
        }

        return $data;
    }
}
