<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use Codeception\Module\JsonNormalizer;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;

class ExpressionLanguage implements PlaceholderProcessorInterface
{
    use PlaceholderProcessorHelperTrait;

    protected string $expr;

    public function __construct(string $expr)
    {
        $this->expr = $expr;
    }

    public function process($value, string $propertyPath, Context $context)
    {
        if (isset($context[self::class])) {
            $exprLang = $context[self::class];
        } else {
            $exprLang = new SymfonyExpressionLanguage();
            $exprLang->register('is_array',
                fn ($var): string => sprintf('is_array(%1$s)', $var),
                fn ($arguments, $var): string => \is_array($var)
            );
            $exprLang->register('count',
                fn ($var): string => sprintf('count(%1$s)', $var),
                fn ($arguments, $var): string => \count($var)
            );
            $exprLang->register('strlen',
                fn ($var): string => sprintf('strlen(%1$s)', $var),
                fn ($arguments, $var): string => \strlen($var)
            );
            $exprLang->register('str_contains',
                fn ($haystack, $needle): string => sprintf('str_contains(%1$s, (%2$s)', $haystack, $needle),
                fn ($arguments, $haystack, $needle): string => \str_contains($haystack, $needle)
            );
            $exprLang->register('str_starts_with',
                fn ($haystack, $needle): string => sprintf('str_starts_with(%1$s, %2$s)', $haystack, $needle),
                fn ($arguments, $haystack, $needle): string => \str_starts_with($haystack, $needle)
            );
            $exprLang->register('str_ends_with',
                fn ($haystack, $needle): string => sprintf('str_ends_with(%1$s, %2$s)', $haystack, $needle),
                fn ($arguments, $haystack, $needle): string => \str_ends_with($haystack, $needle)
            );
            $exprLang->register('strpos',
                fn ($haystack, $needle, $offset = 0): string => sprintf('strpos(%1$s, %2$s, %3$s)', $haystack, $needle, $offset),
                fn ($arguments, $haystack, $needle, $offset = 0): string => \strpos($haystack, $needle, $offset)
            );
            $exprLang->register('stripos',
                fn ($haystack, $needle, $offset = 0): string => sprintf('stripos(%1$s, %2$s, %3$s)', $haystack, $needle, $offset),
                fn ($arguments, $haystack, $needle, $offset): string => \stripos($haystack, $needle, $offset)
            );
            // type assertions
            $exprLang->register('is_string',
                fn ($var): string => sprintf('is_string(%1$s)', $var),
                fn ($arguments, $var): string => \is_string($var)
            );
            $exprLang->register('is_bool',
                fn ($var): string => sprintf('is_bool(%1$s)', $var),
                fn ($arguments, $var): string => \is_bool($var)
            );
            $exprLang->register('is_numeric',
                fn ($var): string => sprintf('is_numeric(%1$s)', $var),
                fn ($arguments, $var): string => \is_numeric($var)
            );
            $exprLang->register('is_int',
                fn ($var): string => sprintf('is_int(%1$s)', $var),
                fn ($arguments, $var): string => \is_int($var)
            );
            $exprLang->register('is_float',
                fn ($var): string => sprintf('is_float(%1$s)', $var),
                fn ($arguments, $var): string => \is_float($var)
            );
            $exprLang->register('is_null',
                fn ($var): string => sprintf('is_null(%1$s)', $var),
                fn ($arguments, $var): string => \is_null($var)
            );
            $exprLang->register('is_array',
                fn ($var): string => sprintf('is_array(%1$s)', $var),
                fn ($arguments, $var): string => \is_array($var)
            );
            $exprLang->register('is_object',
                fn ($var): string => sprintf('is_object(%1$s)', $var),
                fn ($arguments, $var): string => \is_object($var)
            );
            $exprLang->register('is_scalar',
                fn ($var): string => sprintf('is_scalar(%1$s)', $var),
                fn ($arguments, $var): string => \is_scalar($var)
            );
            $exprLang->register('is_a',
                fn ($var, $class): string => sprintf('is_a(%1$s, %2$s)', $var, $class),
                fn ($arguments, $var, $class): string => \is_a($var, $class)
            );
        }

        return $exprLang->evaluate($this->expr, ['value' => $value, 'context' => $context[JsonNormalizer::SHARED]]) ?
            $this->makeStub() :
            $this->makeError("Expr: |{$this->expr}| returned false", $propertyPath, [
                'value' => $value,
            ]);
    }

    public static function getCode(): string
    {
        return 'expr';
    }
}
