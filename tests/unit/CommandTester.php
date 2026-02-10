<?php

namespace AwardWallet\Tests\Unit;

use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester as CTester;

/**
 * @template C of Command
 */
abstract class CommandTester extends BaseUserTest
{
    /**
     * @var ?C
     */
    protected $command;

    /**
     * @var CTester
     */
    protected $commandTester;

    /**
     * @var TestHandler
     */
    protected $logs;

    /**
     * @var string
     */
    protected $loggerService = 'logger';

    /**
     * @param C $command
     */
    protected function initCommand(Command $command)
    {
        $app = new Application($this->container->get('kernel'));
        $app->add($command);

        $this->command = $app->find($command->getName());

        if (method_exists($this->command, 'setContainer')) {
            $this->command->setContainer($this->container);
        }

        $this->commandTester = new CTester($this->command);
        $this->logs = new TestHandler();
        // $this->container->get($this->loggerService)->pushHandler($this->logs);
        $this->container->get(LoggerInterface::class)->pushHandler($this->logs);
    }

    protected function cleanCommand()
    {
        $this->container->get(LoggerInterface::class)->popHandler();
        $this->logs =
        $this->commandTester =
        $this->command = null;
    }

    protected function executeCommand(array $args = [])
    {
        $this->commandTester->execute(array_merge([
            'command' => $this->command->getName(),
        ], $args));
    }

    protected function clearLogs()
    {
        $this->logs->clear();
    }

    /**
     * @param string $str
     */
    protected function displayContains($str, string $message = '')
    {
        $this->assertStringContainsString($str, $this->commandTester->getDisplay(), $message);
    }

    /**
     * @param string $str
     */
    protected function displayNotContains($str, string $message = '')
    {
        $this->assertStringNotContainsString($str, $this->commandTester->getDisplay(), $message);
    }

    /**
     * @param string $str
     */
    protected function logContains($str, string $message = '')
    {
        $this->assertStringContainsString($str, $this->getLogs(), $message);
    }

    protected function logContainsCount($str, int $count, string $message = '')
    {
        $this->assertCount($count, array_filter($this->logs->getRecords(), function (array $record) use ($str) {
            return strpos($record['message'], $str) !== false;
        }), $message);
    }

    /**
     * @param string $str
     */
    protected function logNotContains($str, string $message = '')
    {
        $this->assertStringNotContainsString($str, $this->getLogs(), $message);
    }

    protected function getLogs()
    {
        return "\n------\n" . implode("\n", array_map(function (array $record) {
            return $record['message'];
        }, $this->logs->getRecords())) . "\n------\n";
    }
}
