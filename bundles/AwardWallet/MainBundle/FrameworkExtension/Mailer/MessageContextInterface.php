<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer;

interface MessageContextInterface
{
    public function addContext(array $context): void;

    public function setContext(array $context): void;

    public function getContext(): array;
}
