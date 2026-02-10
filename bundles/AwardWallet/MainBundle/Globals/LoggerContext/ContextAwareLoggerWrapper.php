<?php

namespace AwardWallet\MainBundle\Globals\LoggerContext;

use AwardWallet\MainBundle\Globals\StringUtils;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ContextAwareLoggerWrapper extends Logger
{
    private array $baseContextsMap = [];
    private array $baseContext = [];
    private ?string $messagePrefix = null;
    private bool $typedContext = false;

    public function __construct(LoggerInterface $logger, string $loggerName = 'decorated')
    {
        parent::__construct($loggerName, [new PsrHandler($logger)]);

        $this->pushProcessor(function ($record) {
            $context = $record['context'] ?? null;

            if (\is_array($context) && ($this->baseContext || $this->typedContext)) {
                if ($this->baseContext) {
                    $context = \array_merge($this->baseContext, $context);
                }

                if ($this->typedContext) {
                    $context = $this->retype($context);
                }

                $record['context'] = $context;
            }

            if ((null !== $this->messagePrefix) && isset($record['message'])) {
                $record['message'] = $this->messagePrefix . $record['message'];
            }

            return $record;
        });
    }

    public function withClass(string $class): self
    {
        $parts =
            it(
                \preg_match_all(
                    '/[A-Z0-9]+(?:(?=[A-Z0-9])|$)|[A-Z0-9]?[a-z]+/',
                    \substr($class, (false !== ($pos = \strrpos($class, '\\'))) ? $pos + 1 : 0),
                    $matches
                ) ? $matches[0] : []
            )
            ->filter(static fn (string $part) => StringUtils::isNotEmpty($part))
            ->map(static fn (string $part) => \strtolower($part))
            ->toArray();
        $this->messagePrefix = \implode(' ', $parts) . ': ';
        $this->pushContext([Context::SERVER_MODULE_KEY => \implode('_', $parts)]);

        return $this;
    }

    public function span(array $context): Span
    {
        $contextKey = StringUtils::getRandomCode(16);
        $this->pushContext($context, $contextKey);

        return new Span($this, $contextKey);
    }

    public function setMessagePrefix(string $messagePrefix): self
    {
        $this->messagePrefix = $messagePrefix;

        return $this;
    }

    public function pushContext(array $context, ?string $contextKey = null): self
    {
        if (null === $contextKey) {
            $this->baseContextsMap[] = $context;
        } else {
            $this->baseContextsMap[$contextKey] = $context;
        }

        $this->baseContext = \array_merge(...\array_values($this->baseContextsMap));

        return $this;
    }

    public function popContext(?string $contextKey = null): ?array
    {
        if (null === $contextKey) {
            $pop = \array_pop($this->baseContextsMap);
        } else {
            $pop = $this->baseContextsMap[$contextKey] ?? null;
            unset($this->baseContextsMap[$contextKey]);
        }

        $this->baseContext = \array_merge(...\array_values($this->baseContextsMap));

        return $pop;
    }

    public function withContext(array $context, callable $callback)
    {
        $this->pushContext($context);

        try {
            return $callback();
        } finally {
            $this->popContext();
        }
    }

    public function withTypedContext(): self
    {
        return $this->setTypedContext(true);
    }

    public function setTypedContext(bool $typedContext): self
    {
        $this->typedContext = $typedContext;

        return $this;
    }

    protected function retype(array $context): array
    {
        $newContext = [];

        foreach ($context as $key => $value) {
            if (\is_int($value)) {
                $suffix = 'int';
            } elseif (\is_string($value)) {
                $suffix = 'string';
            } elseif (\is_bool($value)) {
                $suffix = 'bool';
            } elseif (\is_array($value)) {
                $suffix = 'array';
            } elseif (\is_object($value)) {
                $suffix = 'object';
            } elseif (\is_float($value)) {
                $suffix = 'float';
            } elseif (\is_resource($value)) {
                $suffix = 'resource';
            } elseif (\is_null($value)) {
                $suffix = 'null';
            } else {
                continue;
            }

            $newContext[$key . '_' . $suffix] = $value;
        }

        return $newContext;
    }
}
