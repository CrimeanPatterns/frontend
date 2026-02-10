<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Monolog\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FlashBagHandlerFactory
{
    private FlashBagInterface $flashBag;

    private Logger $logger;

    public function __construct(FlashBagInterface $flashBag, Logger $logger)
    {
        $this->flashBag = $flashBag;
        $this->logger = $logger;
    }

    public function push(): void
    {
        $handler = new FlashBagHandler($this->flashBag, Logger::DEBUG, true);
        $handler->setFormatter(new LineFormatter());
        $this->logger->pushHandler($handler);
    }

    public function pop(): void
    {
        $this->logger->popHandler();
    }
}
