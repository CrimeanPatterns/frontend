<?php

namespace AwardWallet\MainBundle\Validator;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\EmailModel;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserEmailValidator implements TranslationContainerInterface
{
    /**
     * @param Usr|EmailModel $value
     */
    public function validate($value, ExecutionContextInterface $context, $errorPath = null): ?string
    {
        if (!$value) {
            return null;
        }

        $email = null;
        $roles = [];

        if ($value instanceof Usr) {
            $email = $value->getEmail();
            $roles = $value->getRoles();
        } elseif ($value instanceof EmailModel) {
            $email = $value->getEmail();
            $roles = $value->getEntity()->getRoles();
        }

        if (empty($email)) {
            return null;
        }

        // Check for AwardWallet domain email restriction (staff only)
        if (preg_match('/^.+@(?:[\w\d_-]+\.)*awardwallet\.com$/i', $email) && !in_array('ROLE_STAFF', $roles)) {
            $context->buildViolation('user.email_taken')
                ->atPath($errorPath ?? 'email')
                ->addViolation();

            return null;
        }

        if (!$this->isValidEmail($email)) {
            $context->buildViolation(/** @Desc("Invalid email format") */ 'user.invalid_email_format')
                ->setTranslationDomain('validators')
                ->atPath($errorPath ?? 'email')
                ->addViolation();
        }

        return null;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('user.invalid_email_format', 'validators'))->setDesc('Invalid email format'),
        ];
    }

    private function isValidEmail(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }
}
