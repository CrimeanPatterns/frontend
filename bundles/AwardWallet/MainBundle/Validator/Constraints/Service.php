<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class Service.
 *
 * @Annotation
 * @Target({"CLASS", "ANNOTATION", "PROPERTY"})
 */
class Service extends Constraint
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $method;
    /**
     * @var string
     */
    public $errorPath;

    public function getTargets()
    {
        return [static::CLASS_CONSTRAINT];
    }

    public function validatedBy()
    {
        return 'service';
    }
}
