<?php

namespace AwardWallet\MainBundle\Globals\Utils\BinaryLogger;

use Cocur\Slugify\SlugifyInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class BinaryLoggerFactory
{
    private ?SlugifyInterface $slugger;
    private LoggerInterface $logger;
    private ?string $prefix = null;
    private int $logLevel = Logger::DEBUG;
    private bool $isUppercaseInfix = false;

    public function __construct(LoggerInterface $logger, ?SlugifyInterface $slugger = null)
    {
        $this->logger = $logger;
        $this->slugger = $slugger;
    }

    /**
     * @template T
     * @param T|null $condition
     * @return BinaryLogger|T
     */
    public function __invoke(string $prefix, $condition = null)
    {
        $binaryLogger = $this->make($prefix);

        if (\func_num_args() > 1) {
            return $binaryLogger->on($condition);
        }

        return $binaryLogger;
    }

    /**
     * @template T
     * @param T|null $condition
     * @return BinaryLogger|T
     */
    public function that(string $prefix, $condition = null)
    {
        $binaryLogger = $this->make($prefix);

        if (\func_num_args() > 1) {
            return $binaryLogger->on($condition);
        }

        return $binaryLogger;
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

    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function to(int $logLevel): self
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    public function toDebug(): self
    {
        return $this->to(Logger::DEBUG);
    }

    public function toInfo(): self
    {
        return $this->to(Logger::INFO);
    }

    public function toNotice(): self
    {
        return $this->to(Logger::NOTICE);
    }

    public function toWarning(): self
    {
        return $this->to(Logger::WARNING);
    }

    public function toError(): self
    {
        return $this->to(Logger::ERROR);
    }

    public function toCritical(): self
    {
        return $this->to(Logger::CRITICAL);
    }

    public function toAlert(): self
    {
        return $this->to(Logger::ALERT);
    }

    public function toEmergency(): self
    {
        return $this->to(Logger::EMERGENCY);
    }

    protected function make(string $prefix): BinaryLogger
    {
        return new BinaryLogger(
            null !== $this->prefix ?
                $this->prefix . $prefix :
                $prefix,
            $this->logLevel,
            $this->isUppercaseInfix,
            $this->logger,
            $this->slugger
        );
    }
}
