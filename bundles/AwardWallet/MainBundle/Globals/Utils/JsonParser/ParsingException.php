<?php

namespace AwardWallet\MainBundle\Globals\Utils\JsonParser;

/**
 * Copy of ParserHelper from salsify/json-streaming-parser.
 *
 * @see https://github.com/salsify/jsonstreamingparser/blob/master/web/Exception/ParsingException.php
 */
class ParsingException extends \Exception
{
    public function __construct(int $line, int $char, string $message, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Parsing error in [%d:%d]. %s', $line, $char, $message), 0, $previous);
    }
}
