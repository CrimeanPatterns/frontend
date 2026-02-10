<?php

namespace AwardWallet\MainBundle\Service\Backup\Model;

use Symfony\Component\Console\Command\Command;

class ProcessorOptions
{
    private Command $delegate;

    public function __construct(Command $delegate)
    {
        $this->delegate = $delegate;
    }

    public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null): self
    {
        $this->delegate->addOption($name, $shortcut, $mode, $description, $default);

        return $this;
    }
}
