<?php

namespace AwardWallet\MainBundle\Form\Type\Cart;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AT201SubscriptionType extends AbstractType
{
    /** @var AuthorizationCheckerInterface */
    private $checker;
    /**
     * @var UsrRepository
     */
    private $usrRepository;

    public function __construct(AuthorizationCheckerInterface $checker, UsrRepository $usrRepository)
    {
        $this->checker = $checker;
        $this->usrRepository = $usrRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->checker->isGranted('ROLE_USER')) {
            $builder->add('email', EmailType::class, [
                'label' => 'awardwallet-email',
                'help' => 'email-match-aw-account',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Callback(['callback' => [$this, "validateEmail"]]),
                ],
            ]);
        }

        $builder
            ->add('type', HiddenType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'proceed-to-payment',
            ]);
    }

    /**
     * @internal
     */
    public function validateEmail($data, ExecutionContextInterface $context)
    {
        $user = $this->usrRepository->findOneBy(['email' => $data]);

        if ($user === null) {
            $context->addViolation('The email address you provided does not match our records.');
        }
    }
}
