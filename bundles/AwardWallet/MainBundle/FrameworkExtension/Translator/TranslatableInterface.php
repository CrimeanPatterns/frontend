<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Translator;

use Symfony\Contracts\Translation\TranslatorInterface;

interface TranslatableInterface
{
    /**
     * @return string
     */
    public function trans(TranslatorInterface $translator);
}
