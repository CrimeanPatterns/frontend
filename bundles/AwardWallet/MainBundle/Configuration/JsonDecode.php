<?php

namespace AwardWallet\MainBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class JsonDecode extends ConfigurationAnnotation
{
    public function getAliasName()
    {
        return 'json_decode';
    }

    public function allowArray()
    {
        return false;
    }
}
