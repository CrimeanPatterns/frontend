<?php

namespace AwardWallet\MainBundle\Validator;

use AwardWallet\MainBundle\Form\Model\Profile\PasswordModel;
use AwardWallet\MainBundle\Security\PasswordChecker;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotSamePasswordValidator implements TranslationContainerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var PasswordChecker
     */
    private $passwordChecker;

    public function __construct(TranslatorInterface $translator, PasswordChecker $passwordChecker)
    {
        $this->translator = $translator;
        $this->passwordChecker = $passwordChecker;
    }

    public function validate(PasswordModel $model, ExecutionContextInterface $context)
    {
        if ($model->isOldPasswordRequired()) {
            if ($model->getOldPassword() === $model->getPass()) {
                $this->addError($context);
            }
        } else {
            $isPasswordValid = false;

            try {
                $this->passwordChecker->checkPasswordUnsafe(
                    $model->getEntity(),
                    $model->getPass()
                );
                $isPasswordValid = true;
            } catch (BadCredentialsException $e) {
            }

            if ($isPasswordValid) {
                $this->addError($context);
            }
        }
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('user.new_pass_not_equal_to_old_pass', 'validators'))
                ->setDesc('Your new password should not be the same as your old password.'),
        ];
    }

    protected function addError(ExecutionContextInterface $context)
    {
        $context
            ->buildViolation('user.new_pass_not_equal_to_old_pass')
            ->atPath('pass.first')
            ->setTranslationDomain('validators')
            ->addViolation();
    }
}
