<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface DeleteActionInterface
{
    public static function getSchema(): string;

    public function deleteAction(Request $request, array $ids): JsonResponse;
}
