<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface AutocompleteActionInterface
{
    public static function getSchema(): string;

    public function autocompleteAction(Request $request, string $id, string $query): JsonResponse;
}
