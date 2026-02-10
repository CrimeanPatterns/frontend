<?php

namespace Codeception\Extension;

use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Lib\Console\MessageFactory;
use Codeception\Lib\Notification;
use Codeception\Subscriber\ErrorHandler;
use Codeception\Test\Descriptor;
use PHPUnit\Framework\Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AwErrorHandler extends \Codeception\Extension
{
    public static $events = [
        Events::SUITE_BEFORE => ['handle', 1],
        Events::SUITE_AFTER => ['afterSuite', 1],
        Events::TEST_BEFORE => 'recordTestSignature',
    ];
    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var string
     */
    protected $currentTestSignature;
    /**
     * @var int
     */
    protected $width;

    protected static $staticDeprecationToTestSignature = [];

    protected static $staticDeprecationData = [];

    /**
     * @var bool to keep shutdownHandler from possible looping
     */
    private $stopped = false;

    /**
     * @var bool to avoid double error handler substitution
     */
    private $initialized = false;

    /**
     * @var int stores bitmask for errors
     */
    private $errorLevel;
    /**
     * @var Notification
     */
    private $notificationsCatcher;

    public function __construct($config, $options)
    {
        parent::__construct($config, $options);
        $this->errorLevel = E_ALL & ~E_STRICT & ~E_DEPRECATED;
        $this->messageFactory = new MessageFactory($this->output);
        $this->notificationsCatcher = new class() extends Notification {
            public function getLastMessage(): string
            {
                return self::$messages[\count(self::$messages) - 1];
            }
        };
        $this->detectWidth();
    }

    public function handle(SuiteEvent $e, string $eventName, EventDispatcherInterface $eventDispatcher)
    {
        $this->removeStockListener($eventDispatcher);
        $settings = $e->getSettings();

        if ($settings['error_level']) {
            $this->errorLevel = eval("return {$settings['error_level']};");
        }
        error_reporting($this->errorLevel);

        if ($this->initialized) {
            return;
        }

        // We must register shutdown function before deprecation error handler to restore previous error handler
        // and silence DeprecationErrorHandler yelling about 'THE ERROR HANDLER HAS CHANGED!'
        register_shutdown_function([$this, 'shutdownHandler']);
        set_error_handler([$this, 'errorHandler']);
        $this->initialized = true;
    }

    public function recordTestSignature(TestEvent $e)
    {
        try {
            $this->currentTestSignature = codecept_relative_path(Descriptor::getTestFullName($e->getTest()));
        } catch (\Throwable $e) {
            $this->currentTestSignature = null;
        }
    }

    public function afterSuite(SuiteEvent $e)
    {
        $this->messageFactory->message()->width($this->width, '-')->writeln();
        $messages = Notification::all();
        $signaturesMap = self::$staticDeprecationToTestSignature;
        self::$staticDeprecationToTestSignature = [];
        $deprecationData = self::$staticDeprecationData;
        self::$staticDeprecationData = [];

        foreach (array_count_values($messages) as $message => $count) {
            $outMessage = $message;
            $signatures = isset($signaturesMap[$message]) ? array_keys($signaturesMap[$message]) : [];

            if ($count > 1) {
                $outMessage = $count . 'x ' . $outMessage;
            }

            if ($signatures) {
                $outMessage .= ' (' . implode(', ', $signatures) . ')';
            }

            $this->output->notification($outMessage);
        }
    }

    public function errorHandler($errno, $errstr, $errfile, $errline, $context = [])
    {
        if (E_USER_DEPRECATED === $errno) {
            $this->handleDeprecationError($errno, $errstr, $errfile, $errline, $context);

            return;
        }

        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return false;
        }

        if (strpos($errstr, 'Cannot modify header information') !== false) {
            return false;
        }

        throw new Exception("$errstr at $errfile:$errline", $errno);
    }

    public function shutdownHandler()
    {
        if ($this->stopped) {
            return;
        }
        $this->stopped = true;
        $error = error_get_last();

        if (!is_array($error)) {
            return;
        }

        if (error_reporting() === 0) {
            return;
        }

        // not fatal
        if ($error['type'] > 1) {
            return;
        }

        echo "\n\n\nFATAL ERROR. TESTS NOT FINISHED.\n";
        echo sprintf("%s \nin %s:%d\n", $error['message'], $error['file'], $error['line']);
        debug_print_backtrace();
    }

    public static function containerAccessProcessor(array $data): string
    {
        $services = it($data)
            ->arsort()
            ->take(10)
            ->mapIndexed(function ($count, $serviceId) { return "{$serviceId}({$count})"; })
            ->joinToString(', ');

        if ('' !== $services) {
            $services = ", top 10: {$services}";
        }

        return $services;
    }

    public static function containerAccess(string $message, array $matches)
    {
        $access = $matches[1];

        if (isset(self::$staticDeprecationData[$message][$access])) {
            self::$staticDeprecationData[$message][$access]++;
        } else {
            self::$staticDeprecationData[$message][$access] = 1;
        }
    }

    protected function detectWidth()
    {
        // try to get terminal width from ENV variable (bash), see also https://github.com/Codeception/Codeception/issues/3788
        if (getenv('COLUMNS')) {
            $this->width = getenv('COLUMNS');
        } else {
            $this->width = (int) shell_exec("command -v tput >> /dev/null 2>&1 && tput cols") - 2;
        }

        return $this->width;
    }

    protected function removeStockListener(EventDispatcherInterface $eventDispatcher)
    {
        foreach ($eventDispatcher->getListeners(Events::SUITE_BEFORE) as $listener) {
            if (
                is_array($listener)
                && isset($listener[0])
                && $listener[0] instanceof ErrorHandler
            ) {
                $refl = new \ReflectionClass($listener[0]);
                $reflProp = $refl->getProperty('initialized');
                $reflProp->setAccessible(true);
                $reflProp->setValue($listener[0], true);
                $reflProp->setAccessible(false);

                break;
            }
        }
    }

    private function handleDeprecationError($type, $message, $file, $line, $context)
    {
        if (!($this->errorLevel & $type)) {
            return;
        }

        Notification::deprecate($message, "$file:$line");
        $message = $this->notificationsCatcher->getLastMessage();

        if (\count(self::$staticDeprecationToTestSignature[$message] ?? []) < 10) {
            self::$staticDeprecationToTestSignature[$message][$this->currentTestSignature ?? 'global'] = true;
        }
    }
}
