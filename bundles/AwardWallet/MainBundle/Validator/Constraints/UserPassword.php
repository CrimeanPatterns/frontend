<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

/**
 * @Annotation
 * @Target({"ANNOTATION", "CLASS", "PROPERTY"})
 */
class UserPassword extends \Symfony\Component\Security\Core\Validator\Constraints\UserPassword
{
    public $property;

    public function getTargets()
    {
        return [parent::PROPERTY_CONSTRAINT, parent::CLASS_CONSTRAINT];
    }
}
