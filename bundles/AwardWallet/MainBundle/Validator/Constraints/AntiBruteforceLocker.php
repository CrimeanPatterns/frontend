<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Class AntiBruteforceLocker.
 *
 * @Annotation
 * @Target({"PROPERTY", "CLASS", "ANNOTATION"})
 */
class AntiBruteforceLocker extends Constraint
{
    /**
     * @var AntiBruteforceLockerService|string
     */
    public $service;

    /**
     * @var string
     */
    public $keyMethod;

    /**
     * @var string
     */
    public $field;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (!isset($this->service, $this->keyMethod)) {
            throw new MissingOptionsException(sprintf('Either option "service" or "keyMethod" must be given for constraint %s', __CLASS__), ['service', 'keyMethod']);
        }
    }

    public function getTargets()
    {
        return [parent::CLASS_CONSTRAINT, parent::PROPERTY_CONSTRAINT];
    }

    public function validatedBy()
    {
        return 'anti_bruteforce_locker';
    }
}
