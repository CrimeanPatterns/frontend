<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use Symfony\Component\HttpFoundation\Request;

interface HttpRequestExecutorInterface
{
    /**
     * @param string $responseChannel - centrifuge channel to sent response into
     * @return string|null - returned response. null if response was sent through channel
     */
    public function execute(Request $request, string $responseChannel): ?string;
}
