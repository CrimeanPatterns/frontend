<?php

namespace AwardWallet\MainBundle\Globals\Utils\JsonParser;

use AwardWallet\MainBundle\Globals\PropertyAccess\SafeCallPropertyPath;
use AwardWallet\MainBundle\Globals\PropertyAccess\SafeCallPropertyPathIteratorInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\PropertyAccess\PropertyPathIteratorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iter\toObject;

class JsonExtractor
{
    /**
     * @var JsonStreamParser
     */
    private $parser;

    public function __construct(JsonStreamParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param resource|string|StreamInterface $source
     * @param string|PropertyPathInterface $propertyPath
     * @return \Generator<int|string, int|string|array|object|null>
     * @throws ParsingException
     */
    public function extract($source, $propertyPath = '', bool $assoc = false): \Generator
    {
        if (('' !== $propertyPath) && !\is_null($propertyPath)) {
            $propertyPath = $propertyPath instanceof PropertyPathInterface ?
                $propertyPath :
                new SafeCallPropertyPath($propertyPath);

            $pathIter = $propertyPath->getIterator();
            $pathIter->rewind();
        } else {
            $pathIter = null;
        }

        try {
            $events = $this->parser->parse($source);
            $events->rewind();

            if (!$events->valid()) {
                $this->throwInvalidJSON();
            }

            do {
                $eventId = $events->key();

                switch ($eventId) {
                    case JsonStreamParser::EVENT_START_DOCUMENT:
                    case JsonStreamParser::EVENT_WHITESPACE:
                        $events->next();

                        break;

                    case JsonStreamParser::EVENT_END_DOCUMENT:
                        $this->throwOnElementNotFound($pathIter);

                        return;

                    case JsonStreamParser::EVENT_START_ARRAY:
                    case JsonStreamParser::EVENT_START_OBJECT:
                        $events->next();

                        yield from $this->extractStructure($events, $pathIter, $assoc);

                        return;

                    default:
                        $this->throwInvalidJSON();
                }
            } while ($events->valid());
        } catch (\Throwable $e) {
            throw new ParsingException($this->parser->getLineNumber(), $this->parser->getCharNumber(), $e->getMessage(), $e);
        }
    }

    private function extractStructure(\Iterator $events, ?PropertyPathIteratorInterface $pathIter, bool $assoc): \Generator
    {
        $numIndex = 0;
        $lastKey = null;

        while ($events->valid()) {
            $eventId = $events->key();
            $eventData = $events->current();

            switch ($eventId) {
                case JsonStreamParser::EVENT_VALUE:
                    if (
                        $pathIter
                        && $pathIter->valid()
                    ) {
                        if ($pathIter->current() == ($lastKey ?? $numIndex++)) {
                            if (!$this->isSafeCallPath($pathIter)) {
                                throw new \RuntimeException(\sprintf('Element at "%s" is not iterable', $pathIter->current()));
                            } else {
                                return;
                            }
                        }
                    } else {
                        $pathIter = null;
                        $yieldKey = ($lastKey ?? $numIndex++);

                        yield $yieldKey => $eventData;
                    }

                    $events->next();

                    break;

                case JsonStreamParser::EVENT_KEY:
                    $lastKey = $eventData;
                    $events->next();

                    break;

                case JsonStreamParser::EVENT_START_OBJECT:
                case JsonStreamParser::EVENT_START_ARRAY:
                    if (
                        $pathIter
                        && $pathIter->valid()
                    ) {
                        $currentKey = ($lastKey ?? $numIndex++);

                        if ($pathIter->current() == $currentKey) {
                            $pathIter->next();

                            if (!$pathIter->valid()) {
                                $pathIter = null;
                            }

                            $events->next();

                            yield from $this->extractStructure($events, $pathIter, $assoc);

                            return;
                        } else {
                            $events->next();
                            $this->skipContainer($events);
                        }
                    } else {
                        $pathIter = null;
                        $events->next();
                        $subIter = $this->extractStructure($events, null, $assoc);
                        $yieldKey = ($lastKey ?? $numIndex++);

                        if (JsonStreamParser::EVENT_START_OBJECT === $eventId) {
                            $yieldValue = $assoc ?
                                \iter\toArrayWithKeys($subIter) :
                                toObject($subIter);
                        } else {
                            $yieldValue = \iter\toArray($subIter);
                        }

                        yield $yieldKey => $yieldValue;

                        $events->next();
                    }

                    break;

                case JsonStreamParser::EVENT_END_OBJECT:
                case JsonStreamParser::EVENT_END_ARRAY:
                case JsonStreamParser::EVENT_END_DOCUMENT:
                    $this->throwOnElementNotFound($pathIter);

                    return;

                case JsonStreamParser::EVENT_WHITESPACE:
                    $events->next();

                    break;

                default:
                    $this->throwInvalidJSON();
            }
        }

        $this->throwInvalidJSON();
    }

    private function skipContainer(\Iterator $events): void
    {
        $bracketsCount = 1; // we have start already

        while (($bracketsCount > 0) && $events->valid()) {
            $eventId = $events->key();

            switch ($eventId) {
                case JsonStreamParser::EVENT_END_OBJECT:
                case JsonStreamParser::EVENT_END_ARRAY:
                    --$bracketsCount;

                    break;

                case JsonStreamParser::EVENT_START_OBJECT:
                case JsonStreamParser::EVENT_START_ARRAY:
                    ++$bracketsCount;

                    break;
            }

            $events->next();
        }
    }

    private function throwInvalidJSON()
    {
        throw new \RuntimeException('Invalid JSON');
    }

    private function isSafeCallPath(?PropertyPathIteratorInterface $pathIter)
    {
        return
            ($pathIter instanceof SafeCallPropertyPathIteratorInterface)
            && $pathIter->isSafeCall();
    }

    private function throwOnElementNotFound(?PropertyPathIteratorInterface $pathIter)
    {
        if (
            $pathIter
            && $pathIter->valid()
            && !$this->isSafeCallPath($pathIter)
        ) {
            throw new \RuntimeException(sprintf('offset "%s" not found', $pathIter->current()));
        }
    }
}
