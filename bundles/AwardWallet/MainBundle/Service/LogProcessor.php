<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Provider;
use Monolog\Processor\ProcessorInterface;

/**
 * @see \AwardWallet\Tests\Unit\MainBundle\Service\LogProcessorTest
 */
class LogProcessor implements ProcessorInterface
{
    /**
     * @var callable[]
     */
    private array $mappers;

    /**
     * @var string[]
     */
    private array $prefixes;

    private array $extraFields;

    private array $baseContext = [];

    /**
     * @param string|null $serviceName ['extra']['service']
     * @param callable[] $mappers e.g. [Tripsegment::class => fn (Tripsegment $ts): string => $ts->getId()]
     * @param string[] $prefixes sorted context keys e.g. ['ts', 'user'] => message - "[{tsId}][{userId}] message"
     */
    public function __construct(
        ?string $serviceName = null,
        array $baseContext = [],
        array $mappers = [],
        array $prefixes = [],
        array $extraFields = []
    ) {
        if (count($baseContext) > 0) {
            $this->baseContext = [$baseContext];
        }
        $this->mappers = $mappers;
        $this->prefixes = $prefixes;
        $this->extraFields = $extraFields;

        if (!is_null($serviceName)) {
            $this->extraFields['service'] = $serviceName;
        }
    }

    public function __invoke(array $record)
    {
        $record['extra'] = array_merge($this->extraFields, $record['extra'] ?? []);
        $record['context'] = array_merge(...$this->baseContext, ...[$record['context'] ?? []]);

        if ($this->prefixes) {
            $pref = [];

            foreach ($this->prefixes as $prefix) {
                $pattern = '%s';

                if (false !== strpos($prefix, '!')) {
                    [$pattern, $prefix] = explode('!', $prefix);
                }

                if (isset($record['context'][$prefix])) {
                    $pref[] = sprintf($pattern, $this->mapContext($record['context'][$prefix], true));
                    unset($record['context'][$prefix]);
                }
            }

            if ($pref) {
                $record['message'] = sprintf('[%s] %s', implode('][', $pref), $record['message']);
            }
        }

        if (false === strpos($record['message'], '{')) {
            return $record;
        }

        $replacements = [];

        foreach ($record['context'] as $key => $val) {
            $placeholder = '{' . $key . '}';

            if (strpos($record['message'], $placeholder) === false) {
                continue;
            }

            $replacements[$placeholder] = $this->mapContext($val, false);
            unset($record['context'][$key]);
        }

        $record['message'] = strtr($record['message'], $replacements);

        return $record;
    }

    public function addExtraField(string $extraName, $value): self
    {
        $this->extraFields[$extraName] = $value;

        return $this;
    }

    public function setBaseContext(array $baseContext): self
    {
        $this->baseContext = [$baseContext];

        return $this;
    }

    public function pushContext(array $context): self
    {
        $this->baseContext[] = $context;

        return $this;
    }

    public function replacePrevContext(array $context): self
    {
        $lastIndex = \count($this->baseContext) - 1;

        if (\count($context) === \count($this->baseContext[$lastIndex]) && \count(array_diff_key($context, $this->baseContext[$lastIndex])) === 0) {
            $this->baseContext[$lastIndex] = $context;
        } else {
            $this->baseContext[] = $context;
        }

        return $this;
    }

    public function popContext(): self
    {
        array_pop($this->baseContext);

        return $this;
    }

    private function mapContext($val, bool $shortFormat): string
    {
        if (is_null($val)) {
            return '<null>';
        }

        if (is_object($val)) {
            if ($callable = $this->findMapper($val)) {
                return $callable($val);
            } elseif (!is_null($result = $this->mapEntity($val))) {
                if ($shortFormat) {
                    return $result;
                }

                return sprintf('<%s %s>', $this->getShortClassName(get_class($val)), $result);
            } else {
                return sprintf('<object %s>', $this->getShortClassName(get_class($val)));
            }
        }

        if (is_scalar($val)) {
            return $val;
        }

        if (is_array($val)) {
            $json = @json_encode($val);

            if (false === $json) {
                return '<null>';
            }

            return sprintf('<array %s>', $json);
        }

        return sprintf('<%s>', gettype($val));
    }

    private function findMapper(object $object): ?callable
    {
        if (isset($this->mappers[get_class($object)])) {
            return $this->mappers[get_class($object)];
        }

        foreach ($this->mappers as $className => $mapper) {
            if (is_a($object, $className)) {
                return $mapper;
            }
        }

        return null;
    }

    private function mapEntity(object $entity): ?string
    {
        if ($entity instanceof Provider && !empty($entity->getCode())) {
            return $entity->getCode();
        }

        if ($entity instanceof Itinerary && !is_null($entity->getId())) {
            return $entity->getIdString();
        }

        if ($entity instanceof \DateTime) {
            return $entity->format('Y-m-d H:i:s');
        }

        if (method_exists($entity, 'getId') && !is_null($entity->getId())) {
            return $entity->getId();
        }

        if (
            method_exists($entity, '__toString')
            && strpos(get_class($entity), 'AwardWallet\\MainBundle\\Entity') !== 0
        ) {
            return (string) $entity;
        }

        return null;
    }

    private function getShortClassName(string $className)
    {
        return trim(substr($className, strrpos($className, '\\')), '\\');
    }
}
