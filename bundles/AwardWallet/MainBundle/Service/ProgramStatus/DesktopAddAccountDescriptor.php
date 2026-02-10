<?php

namespace AwardWallet\MainBundle\Service\ProgramStatus;

class DesktopAddAccountDescriptor extends DesktopContactUsDescriptor
{
    protected function getTargetAAFormLink(): string
    {
        return '_self';
    }
}
