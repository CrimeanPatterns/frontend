<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailsTextareaValidator extends ConstraintValidator
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string[] $emails
     */
    public function validate($emails, Constraint $constraint)
    {
        $emailCount = count($emails);

        if (!$emailCount) {
            $message = $this->translator->trans(/** @Desc("Please enter at least one valid email address.") */ 'constraint.no.valid.emails');
        }

        if ($emailCount > 100) {
            $message = $this->translator->trans(/** @Desc("Please enter a maximum of 100 email addresses at a time.") */ 'constraint.max.email.count');
        }

        if (isset($message)) {
            $this->context->addViolation($message);
        }
    }
}
