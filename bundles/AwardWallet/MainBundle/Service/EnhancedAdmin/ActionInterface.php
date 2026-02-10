<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ActionInterface
{
    public static function getSchema(): string;

    public function action(Request $request, PageRenderer $renderer, string $actionName): Response;
}
