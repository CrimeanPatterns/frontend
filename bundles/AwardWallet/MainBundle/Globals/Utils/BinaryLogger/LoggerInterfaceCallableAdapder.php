<?php

namespace AwardWallet\MainBundle\Globals\Utils\BinaryLogger;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggerInterfaceCallableAdapder implements LoggerInterface
{
    /**
     * @var callable(Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY|Logger::DEBUG|Logger::ERROR|Logger::INFO|Logger::NOTICE|Logger::WARNING, string, array): void
     */
    private $log;

    public function __construct(callable $log)
    {
        $this->log = $log;
    }

    public function emergency($message, array $context = [])
    {
        $this->log(Logger::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(Logger::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(Logger::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(Logger::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(Logger::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(Logger::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(Logger::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(Logger::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        ($this->log)($level, $message, $context);
    }
}
