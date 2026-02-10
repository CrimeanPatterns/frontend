<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig\CacheWarmer;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

abstract class CacheWarmerKnownErrors
{
    private const FILE_PREFIX = __DIR__ . '/../../../../../../';
    private const KNOWN_ERRORS = [
        self::FILE_PREFIX . 'vendor/symfony/symfony/src/Symfony/Bundle/SecurityBundle/Resources/views/Collector/security.html.twig' => [
            'Unknown "profiler_dump" function.',
        ],
        self::FILE_PREFIX . 'vendor/doctrine/doctrine-bundle/Resources/views/Collector/db.html.twig' => [
            'Unknown "profiler_dump" function.',
        ],
        self::FILE_PREFIX . 'vendor/jms/translation-bundle/Resources/views/Translate/messages.html.twig' => [
            'Unknown "sameas" test. Did you mean "same as"?',
        ],
        self::FILE_PREFIX . 'vendor/symfony/symfony/src/Symfony/Bridge/Twig/Resources/views/Email/zurb_2/notification/body.html.twig' => [
            'Unknown "markdown_to_html" filter.',
            'Unknown "inky_to_html" filter.',
        ],
        self::FILE_PREFIX . 'vendor/symfony/symfony/src/Symfony/Bridge/Twig/Resources/views/Email/zurb_2/notification/content_markdown.html.twig' => [
            'Unknown "profiler_dump" function.',
            'Unknown "markdown_to_html" filter.',
        ],
    ];

    private static array $normalizedCached = [];

    public static function isKnownError(\Throwable $e): bool
    {
        self::initializeCache();

        return isset(self::$normalizedCached[$e->getFile()][$e->getMessage()]);
    }

    private static function initializeCache(): void
    {
        if (self::$normalizedCached) {
            return;
        }

        self::$normalizedCached =
            it(self::KNOWN_ERRORS)
            ->mapKeys(fn (string $file) => \realpath($file))
            ->map(fn (array $errors) => \array_flip($errors))
            ->toArrayWithKeys();
    }
}
