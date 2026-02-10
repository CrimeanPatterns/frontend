<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface EditActionInterface
{
    public static function getSchema(): string;

    public function editAction(Request $request, FormRenderer $renderer, ?int $id = null): Response;
}
