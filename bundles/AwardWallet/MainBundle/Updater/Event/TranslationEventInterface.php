<?php

namespace AwardWallet\MainBundle\Updater\Event;

use Symfony\Contracts\Translation\TranslatorInterface;

interface TranslationEventInterface
{
    public function translate(TranslatorInterface $translator);
}
