<?php

namespace Codeception\Module;

use AwardWallet\MainBundle\Globals\Singleton;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\Tests\Modules\JsonNormalizer\Context;
use AwardWallet\Tests\Modules\JsonNormalizer\Equal;
use AwardWallet\Tests\Modules\JsonNormalizer\ExpressionLanguage;
use AwardWallet\Tests\Modules\JsonNormalizer\Ignore;
use AwardWallet\Tests\Modules\JsonNormalizer\IsFloat;
use AwardWallet\Tests\Modules\JsonNormalizer\IsInt;
use AwardWallet\Tests\Modules\JsonNormalizer\IsScalar;
use AwardWallet\Tests\Modules\JsonNormalizer\IsString;
use AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\FoundValue;
use AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\JSONPath;
use AwardWallet\Tests\Modules\JsonNormalizer\ListContains;
use AwardWallet\Tests\Modules\JsonNormalizer\ListPrefix;
use AwardWallet\Tests\Modules\JsonNormalizer\PlaceholderProcessorInterface;
use AwardWallet\Tests\Modules\JsonNormalizer\RegexMatcher;
use AwardWallet\Tests\Modules\JsonNormalizer\Replace;
use AwardWallet\Tests\Modules\JsonNormalizer\Same;
use AwardWallet\Tests\Modules\JsonNormalizer\SameRelativeValue;
use Codeception\Module;
use PHPUnit\Framework\Assert;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class JsonNormalizer extends Module
{
    public const SHARED = 'shared';
    private const ENABLE_ENV_VALUE_LIST = ['yes', 'true', '1', 'on'];
    private const LOCATOR_PARENT_PREFIX_REGEXP = '#^parent([\.\[])#ims';
    private const LOCATOR_PARENT_PREFIX_LEN = 7;
    private static $placeholderProcessorsMap = [];

    public function _initialize()
    {
        parent::_initialize();

        self::$placeholderProcessorsMap =
            it([
                SameRelativeValue::class,
                ExpressionLanguage::class,
                Ignore::class,
                RegexMatcher::class,
                ListContains::class,
                ListPrefix::class,
                IsInt::class,
                IsFloat::class,
                IsScalar::class,
                IsString::class,
                Equal::class,
                Same::class,
                Replace::class,
            ])
            ->reindex(static fn (string $class) => ("{$class}::getCode")())
            ->toArrayWithKeys();
    }

    public function expectJsonTemplate(string $expectedJsonFilePath, string $actualJson, array $sharedContext = [], string $message = ''): void
    {
        $actualData = \json_decode($actualJson, true);
        $expectedJson = @\file_get_contents($expectedJsonFilePath);

        if (StringUtils::isEmpty($expectedJson)) {
            throw new \RuntimeException("Could not read file {$expectedJsonFilePath} or file is empty");
        }

        $expectedData = @\json_decode($expectedJson, true);

        if (JSON_ERROR_NONE !== \json_last_error()) {
            throw new \RuntimeException("File {$expectedJsonFilePath} has invalid json");
        }

        $expectedData = self::normalizeJson(
            $expectedData,
            $actualData,
            $sharedContext
        );
        $expectedJson = \json_encode(
            $expectedData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        $actualJson = \json_encode($actualData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (self::isEnvEnabled('DUMP_JSON')) {
            \file_put_contents($expectedJsonFilePath, $actualJson);
        }

        $dumpOnFail = self::isEnvEnabled('DUMP_JSON_ON_FAIL');

        try {
            Assert::assertJsonStringEqualsJsonString($expectedJson, $actualJson, $message);
        } catch (\Exception $e) {
            if ($dumpOnFail) {
                \file_put_contents($expectedJsonFilePath, $actualJson);
            }

            throw $e;
        }
    }

    private static function isEnvEnabled(string $env): bool
    {
        return \in_array(
            \strtolower(\getenv($env) ?? ''),
            self::ENABLE_ENV_VALUE_LIST
        );
    }

    private static function normalizeJson($expectedJsonData, array &$actualJsonData, array $sharedContext): array
    {
        $pathList = [];
        $jsonPathList = [];
        $context = new Context();
        $context[self::SHARED] = $sharedContext;
        $propertyAccessor = new PropertyAccessor(false, true);

        $go = static function (array &$struct) use ($propertyAccessor, &$pathList, &$go, &$jsonPathList, $context, &$actualJsonData) {
            foreach ($struct as $key => &$value) {
                if (!\is_array($value)) {
                    continue;
                }

                $pathList[] = "[{$key}]";
                $jsonPathList[] = (string) ((int) $key) === (string) $key ?
                    "[{$key}]" :
                    "['{$key}']";
                $placeholderProcessor = self::tryLoadProcessor($value);

                if ($placeholderProcessor) {
                    $locatorExists = false;
                    /** @var ?FoundValue $locatorFound */
                    $locatorFound = null;

                    if (null !== ($locator = $value['_locator'] ?? null)) {
                        $effectiveLocator = $locator;
                        $locatorExists = true;
                        $parentSuffixDiff = 0;
                        $lastMatches = null;

                        while (\preg_match(self::LOCATOR_PARENT_PREFIX_REGEXP, $effectiveLocator, $matches)) {
                            ++$parentSuffixDiff;
                            $effectiveLocator = \substr($effectiveLocator, self::LOCATOR_PARENT_PREFIX_LEN);
                            $lastMatches = $matches;
                        }

                        if ($parentSuffixDiff) {
                            $effectiveLocator =
                                '$'
                                . \implode("", \array_slice($jsonPathList, 0, \count($jsonPathList) - $parentSuffixDiff))
                                . $lastMatches[1]
                                . $effectiveLocator;
                        }

                        $locatorData = (new JSONPath($actualJsonData))->find($effectiveLocator)->getData();

                        if (\count($locatorData) === 1) {
                            $locatorFound = $locatorData[0] ?? null;
                        }
                    }

                    $propertyPath = null;

                    if ($locatorFound) {
                        $propertyPath =
                            it($locatorFound->getKey())
                            ->map(static fn ($key) => "[{$key}]")
                            ->joinToString('')
                            . $pathList[\count($pathList) - 1];
                    } elseif (null === $locator) {
                        $propertyPath = \implode('', $pathList);
                    }

                    if (isset($propertyPath) && $propertyAccessor->isReadable($actualJsonData, $propertyPath)) {
                        $actualValue = $propertyAccessor->getValue($actualJsonData, $propertyPath);
                        $processedValue = $placeholderProcessor->process(
                            $actualValue,
                            $propertyPath,
                            $context
                        );

                        if ($locatorExists) {
                            $processedValue['_locator'] = $locator;
                        }

                        $propertyAccessor->setValue(
                            $actualJsonData,
                            $propertyPath,
                            $processedValue,
                        );
                    }
                }

                if (\is_array($value)) {
                    $go($value);
                }

                \array_pop($pathList);
                \array_pop($jsonPathList);
            }
        };
        $go($expectedJsonData);

        return $expectedJsonData;
    }

    private static function tryLoadProcessor(array $value): ?PlaceholderProcessorInterface
    {
        if (isset($value['_type'])) {
            $type = $value['_type'];

            if (\class_exists($type, true)) {
                $class = $type;

                if (!\in_array(PlaceholderProcessorInterface::class, \class_implements($class, true))) {
                    return null;
                }
            } else {
                $class = self::$placeholderProcessorsMap[$type] ?? null;

                if (null === $class) {
                    return null;
                }
            }

            if (isset($value['_args'])) {
                $args = $value['_args'];

                if (
                    !\is_array($args)
                    || (\array_keys($args) !== \range(0, \count($args) - 1))
                ) {
                    return null;
                }
            } else {
                $args = [];
            }

            return \in_array(Singleton::class, self::classUsesWithParents($class)) ?
                ("{$class}::getInstance")() :
                new $class(...$args);
        }

        return null;
    }

    private static function classUsesWithParents(string $class): array
    {
        $uses = [];
        $classParents = \class_parents($class, true);

        foreach (\array_merge([$class], $classParents) as $class) {
            $uses = \array_merge($uses, \class_uses($class, true));
        }

        return $uses;
    }
}
