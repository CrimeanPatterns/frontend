<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Callback;

/**
 * @Annotation
 */
class CallbackWithDep extends Callback
{
    public $callback;

    public $services;

    public function __construct($options = null)
    {
        if (is_array($options) && 1 === count($options) && isset($options['value'])) {
            $options = $options['value'];
        }

        if (is_array($options) && !isset($options['callback']) && !isset($options['groups']) && !isset($options['services'])) {
            if (is_callable($options)) {
                $options = ['callback' => $options];
            }
        }

        Constraint::__construct($options);
    }

    public function getDefaultOption()
    {
        return 'callback';
    }

    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }

    public function validatedBy()
    {
        return 'callback_dep';
    }
}
