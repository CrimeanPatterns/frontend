<?php

namespace AwardWallet\MainBundle\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextareaTypeSanitizerExtension extends TextTypeSanitizerExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [TextareaType::class];
    }
}
