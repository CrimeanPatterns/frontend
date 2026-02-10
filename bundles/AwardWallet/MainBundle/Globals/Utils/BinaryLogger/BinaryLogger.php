<?php

namespace AwardWallet\MainBundle\Globals\Utils\BinaryLogger;

use Cocur\Slugify\SlugifyInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class BinaryLogger
{
    private const INFIX_WILL = 'will';
    private const INFIX_WILL_NOT = 'will not';
    private const INFIX_WONT = "won't";
    private const INFIX_WAS = 'was';
    private const INFIX_WAS_NOT = 'was not';
    private const INFIX_WASNT = "wasn't";
    private const INFIX_WERE = 'were';
    private const INFIX_WERE_NOT = 'were not';
    private const INFIX_WERENT = "weren't";
    private const INFIX_IS = 'is';
    private const INFIX_IS_NOT = 'is not';
    private const INFIX_ISNT = "isn't";
    private const INFIX_DOES = 'does';
    private const INFIX_DOES_NOT = 'does not';
    private const INFIX_DOESNT = "doesn't";
    private const INFIX_DO = 'do';
    private const INFIX_DO_NOT = 'do not';
    private const INFIX_DONT = "don't";
    private const INFIX_DID = 'did';
    private const INFIX_DID_NOT = 'did not';
    private const INFIX_DIDNT = "didn't";
    private const INFIX_HAS = 'has';
    private const INFIX_HAS_NOT = 'has not';
    private const INFIX_HASNT = "hasn't";
    private const INFIX_HAS_NO = 'has no';
    private const INFIX_HAD = 'had';
    private const INFIX_HAD_NOT = 'had not';
    private const INFIX_HADNT = "hadn't";
    private const INFIX_HAD_NO = 'had no';
    private const INFIX_HAVE = 'have';
    private const INFIX_HAVE_NOT = 'have not';
    private const INFIX_HAVENT = "haven't";
    private const INFIX_HAVE_NO = 'have no';
    private const INFIX_ARE = 'are';
    private const INFIX_ARE_NOT = 'are not';
    private const INFIX_ARENT = "aren't";

    private const INFIX_REVERSE_MAP = [
        self::INFIX_WILL => self::INFIX_WILL_NOT,
        self::INFIX_WILL_NOT => self::INFIX_WILL,
        self::INFIX_WAS => self::INFIX_WAS_NOT,
        self::INFIX_WAS_NOT => self::INFIX_WAS,
        self::INFIX_WERE => self::INFIX_WERE_NOT,
        self::INFIX_WERE_NOT => self::INFIX_WERE,
        self::INFIX_IS => self::INFIX_IS_NOT,
        self::INFIX_IS_NOT => self::INFIX_IS,
        self::INFIX_DOES => self::INFIX_DOES_NOT,
        self::INFIX_DOES_NOT => self::INFIX_DOES,
        self::INFIX_DO => self::INFIX_DO_NOT,
        self::INFIX_DO_NOT => self::INFIX_DO,
        self::INFIX_DID => self::INFIX_DID_NOT,
        self::INFIX_DID_NOT => self::INFIX_DID,
        self::INFIX_HAS => self::INFIX_HAS_NOT,
        self::INFIX_HAS_NOT => self::INFIX_HAS,
        self::INFIX_HAS_NO => self::INFIX_HAS,
        self::INFIX_HAD => self::INFIX_HAD_NOT,
        self::INFIX_HAD_NOT => self::INFIX_HAD,
        self::INFIX_HAD_NO => self::INFIX_HAD,
        self::INFIX_HAVE => self::INFIX_HAVE_NOT,
        self::INFIX_HAVE_NOT => self::INFIX_HAVE,
        self::INFIX_HAVE_NO => self::INFIX_HAVE,
        self::INFIX_ARE => self::INFIX_ARE_NOT,
        self::INFIX_ARE_NOT => self::INFIX_ARE,
        self::INFIX_WONT => self::INFIX_WILL,
        self::INFIX_WASNT => self::INFIX_WAS,
        self::INFIX_WERENT => self::INFIX_WERE,
        self::INFIX_ISNT => self::INFIX_IS,
        self::INFIX_DOESNT => self::INFIX_DOES,
        self::INFIX_DONT => self::INFIX_DO,
        self::INFIX_DIDNT => self::INFIX_DID,
        self::INFIX_HASNT => self::INFIX_HAS,
        self::INFIX_HADNT => self::INFIX_HAD,
        self::INFIX_HAVENT => self::INFIX_HAVE,
        self::INFIX_ARENT => self::INFIX_ARE,
    ];

    private string $prefix;
    private string $infixPositive;
    private string $infixNegative;
    private string $postfix;
    private string $variablePostfix;
    private LoggerInterface $logger;
    private ?SlugifyInterface $slugger;
    private int $positiveLogLevel;
    private int $negativeLogLevel;
    private bool $isUppercaseInfix;

    public function __construct(
        string $prefix,
        int $logLevel,
        bool $isUppercaseInfix,
        LoggerInterface $logger,
        ?SlugifyInterface $slugger = null
    ) {
        $this->prefix = $prefix;
        $this->logger = $logger;
        $this->slugger = $slugger;
        $this->positiveLogLevel = $logLevel;
        $this->negativeLogLevel = $logLevel;
        $this->isUppercaseInfix = $isUppercaseInfix;
    }

    /**
     * @template T
     * @param T $condition
     * @return T
     */
    public function __invoke($condition, array $context = [])
    {
        return $this->on($condition, $context);
    }

    public function do(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_DO, self::INFIX_DO_NOT);
    }

    public function doNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_DO_NOT, self::INFIX_DO);
    }

    public function are(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_ARE, self::INFIX_ARE_NOT);
    }

    public function areNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_ARE_NOT, self::INFIX_ARE);
    }

    public function did(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_DID, self::INFIX_DID_NOT);
    }

    public function didNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_DID_NOT, self::INFIX_DID);
    }

    public function had(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAD, self::INFIX_HAD_NOT);
    }

    public function hadNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAD_NOT, self::INFIX_HAD);
    }

    public function hadGot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAD, self::INFIX_HAD_NO);
    }

    public function hadNo(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAD_NO, self::INFIX_HAD);
    }

    public function does(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_DOES, self::INFIX_DOES_NOT);
    }

    public function doesNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_DOES_NOT, self::INFIX_DOES);
    }

    public function is(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_IS, self::INFIX_IS_NOT);
    }

    public function isNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_IS_NOT, self::INFIX_IS);
    }

    public function has(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAS, self::INFIX_HAS_NOT);
    }

    public function hasNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAS_NOT, self::INFIX_HAS);
    }

    public function hasGot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAS, self::INFIX_HAS_NO);
    }

    public function hasNo(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAS_NO, self::INFIX_HAS);
    }

    public function was(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_WAS, self::INFIX_WAS_NOT);
    }

    public function wasNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_WAS_NOT, self::INFIX_WAS);
    }

    public function will(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_WILL, self::INFIX_WILL_NOT);
    }

    public function willNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_WILL_NOT, self::INFIX_WILL);
    }

    public function were(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_WERE, self::INFIX_WERE_NOT);
    }

    public function wereNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_WERE_NOT, self::INFIX_WERE);
    }

    public function have(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAVE, self::INFIX_HAVE_NOT);
    }

    public function haveNot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAVE_NOT, self::INFIX_HAVE);
    }

    public function haveGot(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAVE, self::INFIX_HAVE_NO);
    }

    public function haveNo(string $postfix): self
    {
        return $this->setInfix($postfix, self::INFIX_HAVE_NO, self::INFIX_HAVE);
    }

    public function toDebug(): self
    {
        return $this->debug();
    }

    public function toInfo(): self
    {
        return $this->info();
    }

    public function toNotice(): self
    {
        return $this->notice();
    }

    public function toWarning(): self
    {
        return $this->warning();
    }

    public function toError(): self
    {
        return $this->error();
    }

    public function toCritical(): self
    {
        return $this->critical();
    }

    public function toAlert(): self
    {
        return $this->alert();
    }

    public function toEmergency(): self
    {
        return $this->emergency();
    }

    public function debug(): self
    {
        return $this->to(Logger::DEBUG);
    }

    public function to(int $logLevel): self
    {
        $this->positiveLogLevel = $logLevel;
        $this->negativeLogLevel = $logLevel;

        return $this;
    }

    public function info(): self
    {
        return $this->to(Logger::INFO);
    }

    public function notice(): self
    {
        return $this->to(Logger::NOTICE);
    }

    public function warning(): self
    {
        return $this->to(Logger::WARNING);
    }

    public function error(): self
    {
        return $this->to(Logger::ERROR);
    }

    public function critical(): self
    {
        return $this->to(Logger::CRITICAL);
    }

    public function alert(): self
    {
        return $this->to(Logger::ALERT);
    }

    public function emergency(): self
    {
        return $this->to(Logger::EMERGENCY);
    }

    public function positiveTo(int $logLevel): self
    {
        $this->positiveLogLevel = $logLevel;

        return $this;
    }

    public function positiveToDebug(): self
    {
        return $this->positiveTo(Logger::DEBUG);
    }

    public function positiveToInfo(): self
    {
        return $this->positiveTo(Logger::INFO);
    }

    public function positiveToNotice(): self
    {
        return $this->positiveTo(Logger::NOTICE);
    }

    public function positiveToWarning(): self
    {
        return $this->positiveTo(Logger::WARNING);
    }

    public function positiveToError(): self
    {
        return $this->positiveTo(Logger::ERROR);
    }

    public function positiveToCritical(): self
    {
        return $this->positiveTo(Logger::CRITICAL);
    }

    public function positiveToAlert(): self
    {
        return $this->positiveTo(Logger::ALERT);
    }

    public function positiveToEmergency(): self
    {
        return $this->positiveTo(Logger::EMERGENCY);
    }

    public function negativeTo(int $logLevel): self
    {
        $this->negativeLogLevel = $logLevel;

        return $this;
    }

    public function negativeToDebug(): self
    {
        return $this->negativeTo(Logger::DEBUG);
    }

    public function negativeToInfo(): self
    {
        return $this->negativeTo(Logger::INFO);
    }

    public function negativeToNotice(): self
    {
        return $this->negativeTo(Logger::NOTICE);
    }

    public function negativeToWarning(): self
    {
        return $this->negativeTo(Logger::WARNING);
    }

    public function negativeToError(): self
    {
        return $this->negativeTo(Logger::ERROR);
    }

    public function negativeToCritical(): self
    {
        return $this->negativeTo(Logger::CRITICAL);
    }

    public function negativeToAlert(): self
    {
        return $this->negativeTo(Logger::ALERT);
    }

    public function negativeToEmergency(): self
    {
        return $this->negativeTo(Logger::EMERGENCY);
    }

    /**
     * @template T
     * @param T $condition
     * @return T
     */
    public function by($condition, array $context = [])
    {
        return $this->on($condition, $context);
    }

    public function endsWith(string $variablePostfix): self
    {
        $this->variablePostfix = $variablePostfix;

        return $this;
    }

    public function ends(string $variablePostfix): self
    {
        return $this->endsWith($variablePostfix);
    }

    public function end(string $variablePostfix): self
    {
        return $this->endsWith($variablePostfix);
    }

    public function with(string $variablePostfix): self
    {
        return $this->endsWith($variablePostfix);
    }

    /**
     * @template T
     * @param T $condition
     * @return T
     */
    public function on($condition, array $context = [])
    {
        $prefix = $this->prefix;

        if (isset($this->infixNegative, $this->infixPositive)) {
            $infix = ($condition ? $this->infixPositive : $this->infixNegative);
            $postfix = $this->postfix;

            if ($this->isUppercaseInfix) {
                $infix = \strtoupper($infix);
            }

            $message = "{$prefix} {$infix} {$postfix}";

            if (isset($this->variablePostfix)) {
                $message .= ' ' . $this->variablePostfix;
            }

            $slugSource = "{$prefix} {$this->infixPositive} {$postfix}";
        } else {
            // split prefix by parts before asterisk selected text, selected text without asterisks, and after asterisk selected text
            $prefixParts = \preg_split('/((?<!\\\\)\*.+(?<!\\\\)\*)/s', $prefix, -1, PREG_SPLIT_DELIM_CAPTURE);

            if (\count($prefixParts) !== 3) {
                $message = $condition ? $prefix : ($this->uppercaseOrPass("not") . " ($prefix)");
                $slugSource = $prefix;
            } else {
                [$prefix, $infixTextWithAsterisks, $postfix] = $prefixParts;
                $infixText = \trim(\preg_replace('/\s+/m', ' ', \trim($infixTextWithAsterisks, '*')));

                $infix = $condition ?
                    $infixText :
                    (self::INFIX_REVERSE_MAP[\strtolower($infixText)] ?? $this->uppercaseOrPass("not ({$infixText})"));

                if ($this->isUppercaseInfix) {
                    $infix = \strtoupper($infix);
                }

                $slugSource = "{$prefix}{$infixText}{$postfix}";
                $message = "{$prefix}{$infix}{$postfix}";
            }
        }

        $slugContext = $this->slugger ? [$this->slugger->slugify($slugSource) => (bool) $condition] : [];
        $context = $context ?
            \array_merge($slugContext, $context) :
            $slugContext;
        $this->logger->log(
            $condition ? $this->positiveLogLevel : $this->negativeLogLevel,
            $message,
            $context
        );

        return $condition;
    }

    public function uppercaseInfix(): self
    {
        $this->isUppercaseInfix = true;

        return $this;
    }

    public function lowercaseInfix(): self
    {
        $this->isUppercaseInfix = false;

        return $this;
    }

    /**
     * @template T
     * @param callable():T $condition
     * @param ?callable():array $context
     * @return T
     */
    public function onCall(callable $condition, ?callable $context = null)
    {
        $condition = $condition();
        $context = $context !== null ? $context($condition) : [];

        return $this->on($condition, $context);
    }

    /**
     * @template T
     * @param callable():T $condition
     * @param ?callable():array $context
     * @return T
     */
    public function byCall(callable $condition, ?callable $context = null)
    {
        return $this->onCall($condition, $context);
    }

    protected function setInfix(string $postfix, string $positive, string $negative): self
    {
        if (
            isset($this->infixNegative)
            || isset($this->infixPositive)
            || isset($this->postfix)
        ) {
            throw new \LogicException('Already initialized!');
        }

        $this->postfix = $postfix;
        $this->infixPositive = $positive;
        $this->infixNegative = $negative;

        return $this;
    }

    private function uppercaseOrPass(string $text): string
    {
        return $this->isUppercaseInfix ? \strtoupper($text) : $text;
    }
}
