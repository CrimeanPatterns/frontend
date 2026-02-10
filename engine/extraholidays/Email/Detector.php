<?php

namespace AwardWallet\Engine\extraholidays\Email;

class Detector extends \AwardWallet\Engine\Detector
{
    protected function getFrom(): array
    {
        return [
            '/[.@]holidayextras[.]com/',
            '/[.@]holidayextras[.]co[.].uk/',
        ];
    }
}
