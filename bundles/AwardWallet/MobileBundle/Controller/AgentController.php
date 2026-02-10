<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Handler\Subscriber\AddAgentGeneric;
use AwardWallet\MainBundle\Form\Handler\Subscriber\Subscriber;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\DoNotSendException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\MailerExceptionInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\NonDeliveryException;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MobileBundle\Form\Type\AddAgentType;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/agent")
 */
class AgentController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    private AwTokenStorageInterface $awTokenStorage;
    private AntiBruteforceLockerService $awSecurityAntibruteforceSearch;
    private TranslatorInterface $translator;
    private RequestStack $requestStack;
    private Counter $counter;

    public function __construct(
        LocalizeService $localizeService,
        AwTokenStorageInterface $awTokenStorage,
        AntiBruteforceLockerService $awSecurityAntibruteforceSearch,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        Counter $counter
    ) {
        $localizeService->setRegionalSettings();
        $this->awTokenStorage = $awTokenStorage;
        $this->awSecurityAntibruteforceSearch = $awSecurityAntibruteforceSearch;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->counter = $counter;
    }

    /**
     * @Route("/add", name="aw_mobile_add_agent", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_USER')")
     * @JsonDecode
     */
    public function addAgentAction(
        Request $request,
        FormDehydrator $formDehydrator,
        Handler $awFormAddAgentHandlerMobile,
        AddAgentGeneric $addAgentGeneric
    ) {
        $form = $this->createForm(AddAgentType::class, new Useragent());
        $generateFormResposnse = function () use ($form, $formDehydrator) {
            return $this->jsonResponse(
                $formDehydrator->dehydrateForm($form)
            );
        };

        $awFormAddAgentHandlerMobile->addHandlerSubscriber(
            (new Subscriber())
                ->setOnCommit(function (HandlerEvent $event) {
                    $event->setResponse($this->successJsonResponse([
                        'result' => [
                            'owner' => $this->getCurrentUser()->getId() .
                                '_' .
                                $event->getForm()->getData()->getEntity()->getUseragentid(),
                        ],
                    ]));
                })
                ->setOnException(
                    $addAgentGeneric->createExceptionHandler($generateFormResposnse)
                )
        );

        if ($response = $awFormAddAgentHandlerMobile->handleRequestTransactionally($form, $request)) {
            return $response;
        }

        return $generateFormResposnse();
    }

    /**
     * @Security("is_granted('CSRF')")
     * @Route("/add-connection", name="awm_create_connection", methods={"POST"})
     * @JsonDecode
     */
    public function addConnectionAction(Request $request, AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, ConnectionManager $connectionManager): JsonResponse
    {
        if ($this->checkQuantity()) {
            return $this->errorJsonResponse($translator->trans(
                'agents.connect.max-connections.message',
                [
                    '%connectionsLink%' => '',
                    '%createBusinessLink%' => '',
                    '%linkEnd%' => '',
                ]
            ));
        }

        $form = $this->getConnectForm();
        $form->submit(['email' => $request->request->get('email')], true);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->checkImpersonation($authorizationChecker);

            $user = $this->awTokenStorage->getBusinessUser();
            $uaRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

            $email = $form->get('email')->getViewData();
            $invitee = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->matching($this->getUserCriteria($email));
            $found = $invitee->count() > 0;

            try {
                if ($found && $uaRep->isExistingConnection($this->awTokenStorage->getBusinessUser(), $invitee->first())) {
                    return $this->errorJsonResponse($translator->trans('agents.connect.exist.message'));
                } elseif ($found) {
                    $connectionManager->connectUser($invitee->first(), $user);
                } else {
                    $connectionManager->inviteUser($email, $user);
                }
            } catch (NonDeliveryException $e) {
                return $this->mailErrorResponseFormatter('ndr', $e->getTarget());
            } catch (DoNotSendException $e) {
                return $this->mailErrorResponseFormatter('donotsend', $e->getTarget());
            } catch (MailerExceptionInterface $_) {
                return $this->successJsonResponse();
            }

            return $this->successJsonResponse();
        }

        return $this->errorJsonResponse(
            it($form->getErrors(true))
            ->map(function (FormError $error) {
                return $error->getMessage();
            })
            ->orElse(["Error"])
            ->first()
        );
    }

    public function lockerListener($event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }

        $userIp = $this->requestStack->getCurrentRequest()->getClientIp();

        $error = $this->awSecurityAntibruteforceSearch->checkForLockout($userIp);

        if (!empty($error)) {
            /** @Desc("Please wait 5 minutes before next attempt.") */
            $message = $this->translator->trans('connection.user_lockout');

            $form->addError(new FormError($message));
        }
    }

    protected function mailErrorResponseFormatter(string $messageKey, \Swift_Message $target): JsonResponse
    {
        return $this->errorJsonResponse(
            $this->translator->trans('email_failed.' . $messageKey, ['%email%' => \key($target->getTo())])
        );
    }

    protected function getUserCriteria($email): Criteria
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('email', $email),
                    Criteria::expr()->neq('userid', $this->awTokenStorage->getBusinessUser()->getId()),
                    Criteria::expr()->in('accountlevel', [ACCOUNT_LEVEL_FREE, ACCOUNT_LEVEL_AWPLUS])
                )
            );

        return $criteria;
    }

    private function checkQuantity(): bool
    {
        $user = $this->awTokenStorage->getBusinessUser();
        $connectionsCount = $this->counter->getConnectedUsers($user);
        $connectionsCount += $this->counter->getInvites($user);

        return !$this->isGranted('SITE_BUSINESS_AREA') && $connectionsCount >= PERSONAL_INTERFACE_MAX_USERS;
    }

    private function getConnectForm(): Form
    {
        $translator = $this->translator;

        $user = $this->awTokenStorage->getBusinessUser();

        /** @var \Symfony\Component\Form\Form $form */
        $form = $this->createFormBuilder(null, ['csrf_protection' => false])
            ->add('email', TextType::class, [
                'required' => true,
                'allow_urls' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 80]),
                    new Email(),
                    new Callback(function ($object, ExecutionContextInterface $context) use ($user, $translator) {
                        if ($user->getEmail() == trim($object)) {
                            $context->buildViolation(
                                $translator->trans(/** @Desc("You are not able to connect with yourself") */ 'agents.connect.email.yourself')
                            )
                                ->atPath('email')
                                ->addViolation();
                        }
                    }),
                    new Callback(function ($object, ExecutionContextInterface $context) use ($user, $translator) {
                        if (\EMAIL_VERIFIED !== $user->getEmailverified()) {
                            $context
                                ->buildViolation(
                                    $translator->trans(
                                        'email.not_verified',
                                        [
                                            '%link_on%' => '',
                                            '%link_off%' => '',
                                        ],
                                        'validators'
                                    )
                                )
                                ->addViolation();
                        }
                    }),
                ],
                'label' => 'login.email',
                'attr' => [
                    /**
                     * @Desc("Email of the person you wish to invite.")
                     */
                    'notice' => $translator->trans('agents.connect.email.notice'),
                ],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, "lockerListener"])
            ->getForm();

        return $form;
    }
}
