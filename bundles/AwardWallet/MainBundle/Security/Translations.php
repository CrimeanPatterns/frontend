<?php

namespace AwardWallet\MainBundle\Security;

use JMS\TranslationBundle\Model\FileSource;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

class Translations implements TranslationContainerInterface
{
    public static function getTranslationMessages()
    {
        $messages = [
            /** @Desc('Invalid user name or password') */
            'Bad credentials',
        ];
        $result = [];

        foreach ($messages as $text) {
            $message = new Message($text, 'validators');
            $message->addSource(new FileSource(__FILE__));
            $result[] = $message;
        }

        return $result;
    }
}
