<?php

namespace AwardWallet\MobileBundle\Resources;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

class Translation implements TranslationContainerInterface
{
    public static function getTranslationMessages()
    {
        return [
            (new Message('clear', 'mobile'))->setDesc('Clear'),
            (new Message('password_requirements', 'validators'))->setDesc('Not all password requirements have been met'),
        ];
    }
}
