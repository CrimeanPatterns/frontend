<?php

namespace Codeception\Module;

use Codeception\Module;
use Codeception\TestInterface;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;

class SymfonyLogger extends Module
{
    /**
     * @var TestHandler
     */
    private $buffer;

    public function _before(TestInterface $test)
    {
        $this->buffer = new TestHandler();
        /** @var Module\Symfony2 $symfony */
        $symfony = $this->getModule('Symfony');
        $symfony->_getContainer()->get(LoggerInterface::class)->pushHandler($this->buffer);
    }

    /**
     * @param \Codeception\TestCase $test
     * @param \PHPUnit_Framework_ExpectationFailedException $fail
     */
    public function _failed(TestInterface $test, $fail)
    {
        if (!empty($this->buffer)) {
            foreach ($this->buffer->getRecords() as $record) {
                $this->debug(trim($record['formatted']));
            }
        }
    }

    public function _after(TestInterface $test)
    {
        /** @var Module\Symfony2 $symfony */
        $symfony = $this->getModule('Symfony');

        if (!empty($this->buffer)) {
            $symfony->_getContainer()->get(LoggerInterface::class)->popHandler($this->buffer);
            $this->buffer = null;
        }
    }
}
