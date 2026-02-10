<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class CardExpirationValidator extends ConstraintValidator
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function validate($value, Constraint $constraint)
    {
        $currentDate = time();

        $expMonth = (int) $this->context->getRoot()->get('expiration_month')->getData();
        $expYear = (int) $this->context->getRoot()->get('expiration_year')->getData();
        $securityCode = $this->context->getRoot()->get('security_code')->getData();

        $expDate = strtotime(($expMonth + 1) . '/01/' . $expYear);

        if ($currentDate > $expDate) {
            /** @Desc("Your card has expired.") */
            $message = $this->translator->trans('card.has_expired');

            $this->context->addViolation($message);
            $this->context->getRoot()->get('expiration_month')->addError(new FormError(''));
        }

        if (!preg_match('/\d{3,4}/', $securityCode)) {
            /** @Desc("Invalid security code. Use numeric characters only.") */
            $message = $this->translator->trans('card.security_code.invalid');

            $this->context->addViolation($message);
        }
    }
}
