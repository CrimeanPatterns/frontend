<?php

namespace AwardWallet\Engine\atlanticairways\Email;

class Detector extends \AwardWallet\Engine\Detector
{
    protected function getFrom(): array
    {
        return [
            '@atlanticairways.com',
            '/[@.]atlantic[.]fo/',
        ];
    }
}
