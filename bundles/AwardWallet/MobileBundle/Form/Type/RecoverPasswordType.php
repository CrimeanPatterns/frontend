<?php

namespace AwardWallet\MobileBundle\Form\Type;

use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RecoverPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('loginOrEmail', EmailType::class, [
            'label' => 'landing.dialog.forgot.label',
            'attr' => ['notice' => 'landing.dialog.forgot.legend'],
            'constraints' => [
                new AwAssert\AndX([
                    new Assert\NotBlank(),
                    new AwAssert\AntiBruteforceLocker([
                        'service' => 'aw.security.antibruteforce.forgot',
                        'keyMethod' => function () use ($options) {
                            return $options['user_ip'];
                        },
                    ]),
                    new AwAssert\AntiBruteforceLocker([
                        'service' => 'aw.security.antibruteforce.forgot',
                        'keyMethod' => function ($value) {
                            return $value;
                        },
                    ]),
                    new AwAssert\CallbackWithDep([
                        'callback' => [$this, 'validate'],
                        'services' => [
                            'doctrine.orm.default_entity_manager',
                        ],
                    ]),
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
        $resolver->setRequired(['user_ip']);
    }

    public function getBlockPrefix()
    {
        return 'recover_password';
    }

    public static function validate($login, ExecutionContextInterface $executionContext, EntityManager $entityManager)
    {
        /** @var ConstraintViolationInterface $violation */
        foreach ($executionContext->getViolations() as $violation) {
            if ($violation->getPropertyPath() === $executionContext->getPropertyPath()) {
                /** Wait for.
                 * @see https://github.com/symfony/symfony/pull/9988
                 * @see https://github.com/symfony/symfony/issues/5665
                 * to be implemented
                 **/
                return;
            }
        }

        $userRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $user = $userRep->loadUserByUsername($login);

        if (!$user) {
            $executionContext
                ->buildViolation('no_user')
                ->setTranslationDomain('validators')
                ->atPath('loginOrEmail')
                ->addViolation();
        }
    }
}
