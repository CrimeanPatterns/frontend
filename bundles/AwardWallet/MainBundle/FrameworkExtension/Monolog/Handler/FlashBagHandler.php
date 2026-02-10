<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Monolog\Handler;

use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FlashBagHandler extends AbstractHandler
{
    private FlashBagInterface $flashBag;

    public function __construct(FlashBagInterface $flashBag, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->flashBag = $flashBag;
    }

    public function handle(array $record)
    {
        $this->flashBag->add(strtolower($record['level_name']), $record['message']);
    }
}
