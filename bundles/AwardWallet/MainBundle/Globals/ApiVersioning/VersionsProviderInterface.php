<?php

namespace AwardWallet\MainBundle\Globals\ApiVersioning;

interface VersionsProviderInterface
{
    /**
     * @return array[Version => string[]]
     */
    public function getVersions();
}
