<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DateRangeValidator extends ConstraintValidator
{
    /** @var \AwardWallet\MainBundle\Globals\Localizer\LocalizeService */
    private $localizer;

    public function __construct(LocalizeService $localizer)
    {
        $this->localizer = $localizer;
    }

    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        if (!($value instanceof \DateTime)) {
            $this->context->addViolation($constraint->invalidMessage, [
                '{{ value }}' => (string) $value,
            ]);

            return;
        }

        if (null !== $constraint->max && $value > $constraint->max) {
            $this->context->addViolation($constraint->maxMessage, [
                '{{ value }}' => $this->localizer->formatDateTime($value, 'short', 'none'),
                '{{ limit }}' => $this->localizer->formatDateTime($constraint->max, 'short', 'none'),
            ]);

            return;
        }

        if (null !== $constraint->min && $value < $constraint->min) {
            $this->context->addViolation($constraint->minMessage, [
                '{{ value }}' => $this->localizer->formatDateTime($value, 'short', 'none'),
                '{{ limit }}' => $this->localizer->formatDateTime($constraint->min, 'short', 'none'),
            ]);
        }
    }
}
