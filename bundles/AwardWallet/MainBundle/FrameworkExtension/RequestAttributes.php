<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Symfony\Component\HttpFoundation\Request;

class RequestAttributes
{
    public static function isSessionLessRequest(Request $request): bool
    {
        return strpos($request->attributes->get('_firewall_context'), 'nosession_area') !== false;
    }
}
