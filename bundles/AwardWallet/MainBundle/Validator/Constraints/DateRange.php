<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @Annotation
 */
class DateRange extends Constraint
{
    public $minMessage = 'date.range.min';
    public $maxMessage = 'date.range.max';
    public $invalidMessage = 'date.range.invalid';
    /**
     * @var \DateTime
     */
    public $min;
    /**
     * @var \DateTime
     */
    public $max;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (null === $this->min && null === $this->max) {
            throw new MissingOptionsException(sprintf('Either option "min" or "max" must be given for constraint %s', __CLASS__), ['min', 'max']);
        }

        if (null !== $this->min && !($this->min instanceof \DateTime) && !is_string($this->min)) {
            throw new InvalidOptionsException(sprintf('Option "min" must be an instance of the class \DateTime or string for constraint %s', __CLASS__), ['min']);
        }

        if (null !== $this->max && !($this->max instanceof \DateTime) && !is_string($this->max)) {
            throw new InvalidOptionsException(sprintf('Option "max" must be an instance of the class \DateTime or string for constraint %s', __CLASS__), ['max']);
        }

        if (null !== $this->min && is_string($this->min)) {
            $this->min = new \DateTime($this->min);
            $this->min->setTime(0, 0, 0);
        }

        if (null !== $this->max && is_string($this->max)) {
            $this->max = new \DateTime($this->max);
            $this->max->setTime(0, 0, 0);
        }
    }

    public function validatedBy()
    {
        return 'date_range';
    }
}
