<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ListActionInterface
{
    public static function getSchema(): string;

    public function listAction(Request $request, ListRenderer $renderer): Response;
}
