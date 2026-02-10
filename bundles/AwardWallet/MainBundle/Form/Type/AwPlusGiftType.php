<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AwPlusGiftType extends AbstractType
{
    public const SESSION_GIFT_AWPLUS_DATA = 'giftAWPlusData';

    public const PAY_TYPE_ONE_YEAR = 1;
    public const PAY_TYPE_YEARLY = 2;

    /** @var TokenStorage */
    private $tokenStorage;

    /** @var TranslatorInterface */
    private $translator;

    /** @var EntityManager */
    private $entityManager;

    /** @var SessionInterface */
    private $session;

    /** @var RequestStack */
    private $requestStack;

    /** @var AntiBruteforceLockerService */
    private $emailLocker;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        RequestStack $requestStack,
        AntiBruteforceLockerService $emailLocker
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->requestStack = $requestStack;
        $this->emailLocker = $emailLocker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $data = $this->session->has(self::SESSION_GIFT_AWPLUS_DATA) ? $this->session->get(self::SESSION_GIFT_AWPLUS_DATA) : [];

        $builder->add('email', EmailType::class, [
            'label' => 'your-friend-email-addr',
            'required' => true,
            'constraints' => [
                new Callback(['callback' => [$this, 'validateCallback']]),
                new Assert\Email(),
            ],
            'data' => $data['email'] ?? '',
            'attr' => [
                // 'autofocus' => 'autofocus',
                'tabindex' => 101,
            ],
        ]);
        $builder->add('payType', ChoiceType::class, [
            'label' => 'i-want-to-gift',
            'choices' => [
                $this->translator->trans('cart.item.type.awplus-1-year') => self::PAY_TYPE_ONE_YEAR,
                $this->translator->trans('awplus-subscription-recurring-yearly-pay') => self::PAY_TYPE_YEARLY,
            ],
            'data' => $data['payType'] ?? self::PAY_TYPE_YEARLY,
            'attr' => [
                'tabindex' => 102,
            ],
        ]);
        $builder->add('message', TextareaType::class, [
            'label' => 'personal-message',
            'required' => false,
            'data' => $data['message'] ?? '',
            'allow_quotes' => true,
            'attr' => [
                'maxlength' => 512,
                'tabindex' => 103,
            ],
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $formEvent) {
            $form = $formEvent->getForm();

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $formEvent->getData();
                $this->session->set(self::SESSION_GIFT_AWPLUS_DATA, $data);
            }
        });
    }

    /**
     * @internal
     */
    public function validateCallback($email, ExecutionContextInterface $context)
    {
        $error = $this->emailLocker->checkForLockout($this->requestStack->getMasterRequest()->getClientIp());

        if ($error) {
            $context->addViolation($error);

            return;
        }

        $recipient = $this->entityManager->getRepository(Usr::class)->findOneBy(['email' => $email]);

        if (null === $recipient) {
            $context->addViolation($this->translator->trans('booking.request.add.form.contact.errors.not-exist', [], 'booking'));

            return;
        }

        if ($recipient->isAwPlus() && null !== $recipient->getSubscription()) {
            $context->addViolation($this->translator->trans('user-already-awplus'));

            return;
        }

        if ($recipient->getId() === $this->tokenStorage->getToken()->getUser()->getUserid()) {
            $context->addViolation($this->translator->trans('user.email.invalid', [], 'validators'));

            return;
        }
    }

    public function getBlockPrefix(): string
    {
        return 'user_gift_awplus';
    }
}
