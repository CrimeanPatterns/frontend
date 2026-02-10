<?php

namespace AwardWallet\MainBundle\Service\Cache\Annotations;

/**
 * @Annotation
 */
class Tag
{
    /**
     * Tag name.
     *
     * @var string
     */
    public $name;

    /**
     * Tag description.
     *
     * @var string
     */
    public $desc;
}
