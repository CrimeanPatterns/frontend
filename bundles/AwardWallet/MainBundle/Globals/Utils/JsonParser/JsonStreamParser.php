<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Globals\Utils\JsonParser;

use Psr\Http\Message\StreamInterface;

/**
 * Iteratorized version of salsify/json-streaming-parser.
 *
 * @see https://github.com/salsify/jsonstreamingparser
 */
class JsonStreamParser
{
    public const EVENT_START_DOCUMENT = 0;
    public const EVENT_END_DOCUMENT = 1;
    public const EVENT_START_OBJECT = 2;
    public const EVENT_END_OBJECT = 3;
    public const EVENT_START_ARRAY = 4;
    public const EVENT_END_ARRAY = 5;
    public const EVENT_KEY = 6;
    public const EVENT_VALUE = 7;
    public const EVENT_WHITESPACE = 8;

    private const STATE_START_DOCUMENT = 0;
    private const STATE_END_DOCUMENT = 14;
    private const STATE_DONE = -1;
    private const STATE_IN_ARRAY = 1;
    private const STATE_IN_OBJECT = 2;
    private const STATE_END_KEY = 3;
    private const STATE_AFTER_KEY = 4;
    private const STATE_IN_STRING = 5;
    private const STATE_START_ESCAPE = 6;
    private const STATE_UNICODE = 7;
    private const STATE_IN_NUMBER = 8;
    private const STATE_IN_TRUE = 9;
    private const STATE_IN_FALSE = 10;
    private const STATE_IN_NULL = 11;
    private const STATE_AFTER_VALUE = 12;
    private const STATE_UNICODE_SURROGATE = 13;

    private const STACK_OBJECT = 0;
    private const STACK_ARRAY = 1;
    private const STACK_KEY = 2;
    private const STACK_STRING = 3;

    private const UTF8_BOM = 1;
    private const UTF16_BOM = 2;
    private const UTF32_BOM = 3;

    /**
     * @var int
     */
    private $state;
    /**
     * @var int[]
     */
    private $stack = [];
    /**
     * @var bool
     */
    private $emitWhitespace;
    /**
     * @var string
     */
    private $buffer = '';
    /**
     * @var int
     */
    private $bufferSize;
    /**
     * @var string[]
     */
    private $unicodeBuffer = [];
    /**
     * @var int
     */
    private $unicodeHighSurrogate = -1;
    /**
     * @var string
     */
    private $unicodeEscapeBuffer = '';
    /**
     * @var string
     */
    private $lineEnding;
    /**
     * @var int
     */
    private $lineEndingBytesLength;
    /**
     * @var int
     */
    private $lineNumber;
    /**
     * @var int
     */
    private $charNumber;
    /**
     * @var int
     */
    private $bytesCount;
    /**
     * @var int
     */
    private $utfBom = 0;

    public function __construct(string $lineEnding = "\n", bool $emitWhitespace = false, int $bufferSize = 8192)
    {
        $this->emitWhitespace = $emitWhitespace;
        $this->state = self::STATE_START_DOCUMENT;
        $this->bufferSize = $bufferSize;
        $this->lineEnding = $lineEnding;
        $this->lineEndingBytesLength = \strlen($lineEnding);
    }

    /**
     * @param resource|string|StreamInterface $source
     */
    public function parse($source): \Generator
    {
        $this->lineNumber = 1;
        $this->charNumber = 1;
        $this->bytesCount = 1;

        if (\is_string($source)) {
            $byteLen = \strlen($source);

            for ($i = 0; $i < $byteLen; ++$i) {
                $char = $source[$i];
                $events = $this->consumeChar($char);

                if ($events) {
                    yield from $events;
                }

                if ($char === $this->lineEnding) {
                    ++$this->lineNumber;
                    $this->charNumber = 1;
                    $this->bytesCount += $this->lineEndingBytesLength;
                } else {
                    ++$this->charNumber;
                    ++$this->bytesCount;
                }
            }
        } elseif (\is_resource($source) && 'stream' === \get_resource_type($source)) {
            $eof = false;

            while (!\feof($source) && !$eof) {
                $pos = \ftell($source);
                $line = \stream_get_line($source, $this->bufferSize, $this->lineEnding);

                if (false === $line) {
                    $line = '';
                }

                $ended = (bool) (\ftell($source) - \strlen($line) - $pos);
                // if we're still at the same place after stream_get_line, we're done
                $eof = \ftell($source) === $pos;
                $byteLen = \strlen($line);

                for ($i = 0; $i < $byteLen; ++$i) {
                    $events = $this->consumeChar($line[$i]);

                    if ($events) {
                        yield from $events;
                    }

                    ++$this->charNumber;
                    ++$this->bytesCount;
                }

                if ($ended) {
                    ++$this->lineNumber;
                    $this->charNumber = 1;
                    $this->bytesCount += $this->lineEndingBytesLength;
                }
            }
        } elseif ($source instanceof StreamInterface) {
            $eof = false;

            while (!$source->eof() && !$eof) {
                $pos = $source->tell();
                $line = $source->read($this->bufferSize);

                if (false === $line) {
                    $line = '';
                }

                $ended = (bool) ($source->tell() - \strlen($line) - $pos);
                // if we're still at the same place after stream_get_line, we're done
                $eof = $source->tell() === $pos;
                $byteLen = \strlen($line);

                for ($i = 0; $i < $byteLen; ++$i) {
                    $events = $this->consumeChar($line[$i]);

                    if ($events) {
                        yield from $events;
                    }

                    ++$this->charNumber;
                    ++$this->bytesCount;
                }

                if ($ended) {
                    ++$this->lineNumber;
                    $this->charNumber = 1;
                    $this->bytesCount += $this->lineEndingBytesLength;
                }
            }
        } else {
            throw new \InvalidArgumentException('Invalid stream or string provided');
        }
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function getCharNumber(): int
    {
        return $this->charNumber;
    }

    public function getBytesCount(): int
    {
        return $this->bytesCount;
    }

    private function consumeChar(string $char): array
    {
        // see https://en.wikipedia.org/wiki/Byte_order_mark
        if ($this->charNumber < 5 && 1 === $this->lineNumber && $this->checkAndSkipUtfBom($char)) {
            return [];
        }

        // valid whitespace characters in JSON (from RFC4627 for JSON) include:
        // space, horizontal tab, line feed or new line, and carriage return.
        // thanks: http://stackoverflow.com/questions/16042274/definition-of-whitespace-in-json
        if ((' ' === $char || "\t" === $char || "\n" === $char || "\r" === $char)
            && !(self::STATE_IN_STRING === $this->state
                || self::STATE_UNICODE === $this->state
                || self::STATE_START_ESCAPE === $this->state
                || self::STATE_IN_NUMBER === $this->state)
        ) {
            // we wrap this so that we don't make a ton of unnecessary function calls
            // unless someone really, really cares about whitespace.
            if ($this->emitWhitespace) {
                return [self::EVENT_WHITESPACE => $char];
            }

            return [];
        }

        switch ($this->state) {
            case self::STATE_IN_STRING:
                if ('"' === $char) {
                    return $this->endString();
                } elseif ('\\' === $char) {
                    $this->state = self::STATE_START_ESCAPE;
                } elseif ($char < "\x1f") {
                    $this->throwParseError('Unescaped control character encountered: ' . $char);
                } else {
                    $this->buffer .= $char;
                }

                break;

            case self::STATE_IN_ARRAY:
                if (']' === $char) {
                    return $this->endArray();
                } else {
                    return $this->startValue($char);
                }

                break;

            case self::STATE_IN_OBJECT:
                if ('}' === $char) {
                    return $this->endObject();
                } elseif ('"' === $char) {
                    $this->startKey();
                } else {
                    $this->throwParseError('Start of string expected for object key. Instead got: ' . $char);
                }

                break;

            case self::STATE_END_KEY:
                if (':' !== $char) {
                    $this->throwParseError("Expected ':' after key.");
                }
                $this->state = self::STATE_AFTER_KEY;

                break;

            case self::STATE_AFTER_KEY:
                return $this->startValue($char);

                break;

            case self::STATE_START_ESCAPE:
                $this->processEscapeCharacter($char);

                break;

            case self::STATE_UNICODE:
                $this->processUnicodeCharacter($char);

                break;

            case self::STATE_UNICODE_SURROGATE:
                $this->unicodeEscapeBuffer .= $char;

                if (2 === \mb_strlen($this->unicodeEscapeBuffer)) {
                    $this->endUnicodeSurrogateInterstitial();
                }

                break;

            case self::STATE_AFTER_VALUE:
                $within = \end($this->stack);

                if (self::STACK_OBJECT === $within) {
                    if ('}' === $char) {
                        return $this->endObject();
                    } elseif (',' === $char) {
                        $this->state = self::STATE_IN_OBJECT;
                    } else {
                        $this->throwParseError("Expected ',' or '}' while parsing object. Got: " . $char);
                    }
                } elseif (self::STACK_ARRAY === $within) {
                    if (']' === $char) {
                        return $this->endArray();
                    } elseif (',' === $char) {
                        $this->state = self::STATE_IN_ARRAY;
                    } else {
                        $this->throwParseError("Expected ',' or ']' while parsing array. Got: " . $char);
                    }
                } else {
                    $this->throwParseError(
                        'Finished a literal, but unclear what state to move to. Last state: ' . $within
                    );
                }

                break;

            case self::STATE_IN_NUMBER:
                if (\ctype_digit($char)) {
                    $this->buffer .= $char;
                } elseif ('.' === $char) {
                    if (false !== \strpos($this->buffer, '.')) {
                        $this->throwParseError('Cannot have multiple decimal points in a number.');
                    } elseif (false !== stripos($this->buffer, 'e')) {
                        $this->throwParseError('Cannot have a decimal point in an exponent.');
                    }
                    $this->buffer .= $char;
                } elseif ('e' === $char || 'E' === $char) {
                    if (false !== \stripos($this->buffer, 'e')) {
                        $this->throwParseError('Cannot have multiple exponents in a number.');
                    }
                    $this->buffer .= $char;
                } elseif ('+' === $char || '-' === $char) {
                    $last = \mb_substr($this->buffer, -1);

                    if (!('e' === $last || 'E' === $last)) {
                        $this->throwParseError("Can only have '+' or '-' after the 'e' or 'E' in a number.");
                    }
                    $this->buffer .= $char;
                } else {
                    // we have consumed one beyond the end of the number
                    return $this->endNumber() + $this->consumeChar($char);
                }

                break;

            case self::STATE_IN_TRUE:
                $this->buffer .= $char;

                if (4 === \strlen($this->buffer)) {
                    return $this->endTrue();
                }

                break;

            case self::STATE_IN_FALSE:
                $this->buffer .= $char;

                if (5 === \strlen($this->buffer)) {
                    return $this->endFalse();
                }

                break;

            case self::STATE_IN_NULL:
                $this->buffer .= $char;

                if (4 === \strlen($this->buffer)) {
                    return $this->endNull();
                }

                break;

            case self::STATE_START_DOCUMENT:
                $events = [self::EVENT_START_DOCUMENT => null];

                if ('[' === $char) {
                    return $events + $this->startArray();
                } elseif ('{' === $char) {
                    return $events + $this->startObject();
                } else {
                    $this->throwParseError('Document must start with object or array.');
                }

                break;

            case self::STATE_END_DOCUMENT:
                if ('[' !== $char && '{' !== $char) {
                    $this->throwParseError('Expected end of document.');
                }
                $this->state = self::STATE_START_DOCUMENT;

                return $this->consumeChar($char);

                break;

            case self::STATE_DONE:
                $this->throwParseError('Expected end of document.');

                break;

            default:
                $this->throwParseError('Internal error. Reached an unknown state: ' . $this->state);

                break;
        }

        return [];
    }

    private function checkAndSkipUtfBom(string $c): bool
    {
        if (1 === $this->charNumber) {
            if (\chr(239) === $c) {
                $this->utfBom = self::UTF8_BOM;
            } elseif (\chr(254) === $c || \chr(255) === $c) {
                // NOTE: could also be UTF32_BOM
                // second character will tell
                $this->utfBom = self::UTF16_BOM;
            } elseif (\chr(0) === $c) {
                $this->utfBom = self::UTF32_BOM;
            }
        }

        if (self::UTF16_BOM === $this->utfBom && 2 === $this->charNumber
            && \chr(254) === $c) {
            $this->utfBom = self::UTF32_BOM;
        }

        if (self::UTF8_BOM === $this->utfBom && $this->charNumber < 4) {
            // UTF-8 BOM starts with chr(239) . chr(187) . chr(191)
            return true;
        }

        if (self::UTF16_BOM === $this->utfBom && $this->charNumber < 3) {
            return true;
        }

        if (self::UTF32_BOM === $this->utfBom && $this->charNumber < 5) {
            return true;
        }

        return false;
    }

    /**
     * @throws ParsingException
     */
    private function startValue(string $c): array
    {
        if ('[' === $c) {
            return $this->startArray();
        } elseif ('{' === $c) {
            return $this->startObject();
        } elseif ('"' === $c) {
            $this->startString();
        } elseif (ParserHelper::isDigit($c)) {
            $this->startNumber($c);
        } elseif ('t' === $c) {
            $this->state = self::STATE_IN_TRUE;
            $this->buffer .= $c;
        } elseif ('f' === $c) {
            $this->state = self::STATE_IN_FALSE;
            $this->buffer .= $c;
        } elseif ('n' === $c) {
            $this->state = self::STATE_IN_NULL;
            $this->buffer .= $c;
        } else {
            $this->throwParseError('Unexpected character for value: ' . $c);
        }

        return [];
    }

    private function startArray(): array
    {
        $this->state = self::STATE_IN_ARRAY;
        $this->stack[] = self::STACK_ARRAY;

        return [self::EVENT_START_ARRAY => null];
    }

    private function endArray(): array
    {
        $popped = \array_pop($this->stack);

        if (self::STACK_ARRAY !== $popped) {
            $this->throwParseError('Unexpected end of array encountered.');
        }

        $events = [self::EVENT_END_ARRAY => null];

        $this->state = self::STATE_AFTER_VALUE;

        if (empty($this->stack)) {
            $events += $this->endDocument();
        }

        return $events;
    }

    private function startObject(): array
    {
        $this->state = self::STATE_IN_OBJECT;
        $this->stack[] = self::STACK_OBJECT;

        return [self::EVENT_START_OBJECT => null];
    }

    private function endObject(): array
    {
        $popped = \array_pop($this->stack);

        if (self::STACK_OBJECT !== $popped) {
            $this->throwParseError('Unexpected end of object encountered.');
        }

        $events = [self::EVENT_END_OBJECT => null];

        $this->state = self::STATE_AFTER_VALUE;

        if (empty($this->stack)) {
            $events += $this->endDocument();
        }

        return $events;
    }

    private function startString(): void
    {
        $this->stack[] = self::STACK_STRING;
        $this->state = self::STATE_IN_STRING;
    }

    private function startKey(): void
    {
        $this->stack[] = self::STACK_KEY;
        $this->state = self::STATE_IN_STRING;
    }

    private function endString(): array
    {
        $popped = \array_pop($this->stack);

        if (self::STACK_KEY === $popped) {
            $event = [self::EVENT_KEY => $this->buffer];

            $this->state = self::STATE_END_KEY;
        } elseif (self::STACK_STRING === $popped) {
            $event = [self::EVENT_VALUE => $this->buffer];

            $this->state = self::STATE_AFTER_VALUE;
        } else {
            $this->throwParseError('Unexpected end of string.');
        }

        $this->buffer = '';

        return $event;
    }

    /**
     * @throws ParsingException
     */
    private function processEscapeCharacter(string $c): void
    {
        if ('"' === $c) {
            $this->buffer .= '"';
        } elseif ('\\' === $c) {
            $this->buffer .= '\\';
        } elseif ('/' === $c) {
            $this->buffer .= '/';
        } elseif ('b' === $c) {
            $this->buffer .= "\x08";
        } elseif ('f' === $c) {
            $this->buffer .= "\f";
        } elseif ('n' === $c) {
            $this->buffer .= "\n";
        } elseif ('r' === $c) {
            $this->buffer .= "\r";
        } elseif ('t' === $c) {
            $this->buffer .= "\t";
        } elseif ('u' === $c) {
            $this->state = self::STATE_UNICODE;
        } else {
            $this->throwParseError('Expected escaped character after backslash. Got: ' . $c);
        }

        if (self::STATE_UNICODE !== $this->state) {
            $this->state = self::STATE_IN_STRING;
        }
    }

    /**
     * @throws ParsingException
     */
    private function processUnicodeCharacter(string $char): void
    {
        if (!ParserHelper::isHexCharacter($char)) {
            $this->throwParseError(
                'Expected hex character for escaped Unicode character. '
                . 'Unicode parsed: ' . implode('', $this->unicodeBuffer) . ' and got: ' . $char
            );
        }
        $this->unicodeBuffer[] = $char;

        if (4 === \count($this->unicodeBuffer)) {
            $codepoint = \hexdec(\implode('', $this->unicodeBuffer));

            if ($codepoint >= 0xD800 && $codepoint < 0xDC00) {
                $this->unicodeHighSurrogate = $codepoint;
                $this->unicodeBuffer = [];
                $this->state = self::STATE_UNICODE_SURROGATE;
            } elseif ($codepoint >= 0xDC00 && $codepoint <= 0xDFFF) {
                if (-1 === $this->unicodeHighSurrogate) {
                    $this->throwParseError('Missing high surrogate for Unicode low surrogate.');
                }
                $combinedCodepoint = (($this->unicodeHighSurrogate - 0xD800) * 0x400) + ($codepoint - 0xDC00) + 0x10000;
                $this->endUnicodeCharacter($combinedCodepoint);
            } else {
                if (-1 !== $this->unicodeHighSurrogate) {
                    $this->throwParseError('Invalid low surrogate following Unicode high surrogate.');
                } else {
                    $this->endUnicodeCharacter($codepoint);
                }
            }
        }
    }

    private function endUnicodeSurrogateInterstitial(): void
    {
        $unicodeEscape = $this->unicodeEscapeBuffer;

        if ('\\u' !== $unicodeEscape) {
            $this->throwParseError("Expected '\\u' following a Unicode high surrogate. Got: " . $unicodeEscape);
        }
        $this->unicodeEscapeBuffer = '';
        $this->state = self::STATE_UNICODE;
    }

    private function endUnicodeCharacter(int $codepoint): void
    {
        $this->buffer .= ParserHelper::convertCodepointToCharacter($codepoint);
        $this->unicodeBuffer = [];
        $this->unicodeHighSurrogate = -1;
        $this->state = self::STATE_IN_STRING;
    }

    private function startNumber(string $c): void
    {
        $this->state = self::STATE_IN_NUMBER;
        $this->buffer .= $c;
    }

    private function endNumber(): array
    {
        $buffer = ParserHelper::convertToNumber($this->buffer);
        $this->buffer = '';
        $this->state = self::STATE_AFTER_VALUE;

        return [self::EVENT_VALUE => $buffer];
    }

    private function endTrue(): array
    {
        return $this->endSpecialValue(true, 'true');
    }

    private function endFalse(): array
    {
        return $this->endSpecialValue(false, 'false');
    }

    private function endNull(): array
    {
        return $this->endSpecialValue(null, 'null');
    }

    private function endSpecialValue($value, string $stringValue): array
    {
        if ($stringValue !== $this->buffer) {
            $this->throwParseError("Expected 'null'. Got: " . $this->buffer);
        }

        $this->buffer = '';
        $this->state = self::STATE_AFTER_VALUE;

        return [self::EVENT_VALUE => $value];
    }

    private function endDocument(): array
    {
        $this->state = self::STATE_END_DOCUMENT;

        return [self::EVENT_END_DOCUMENT => null];
    }

    /**
     * @throws ParsingException
     */
    private function throwParseError(string $message): void
    {
        throw new ParsingException($this->lineNumber, $this->charNumber, $message);
    }
}
