<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Handler\Subscriber\AddAgentGeneric;
use AwardWallet\MainBundle\Form\Handler\Subscriber\Subscriber;
use AwardWallet\MainBundle\Form\Type\AddAgentType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\MailerExceptionInterface;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\WidgetBundle\Widget\ConnectionsPersonsWidget;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Person Controller.
 *
 * @Route("/agents")
 */
class AgentController extends AbstractController implements TranslationContainerInterface
{
    private AwTokenStorageInterface $tokenStorage;

    private Counter $counter;

    private AntiBruteforceLockerService $antiBruteforceLockerService;

    private TranslatorInterface $translator;

    private RequestStack $requestStack;
    private RouterInterface $router;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        Counter $counter,
        AntiBruteforceLockerService $securityAntibruteforceConnectionSearch,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        RouterInterface $router
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->counter = $counter;
        $this->antiBruteforceLockerService = $securityAntibruteforceConnectionSearch;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/add", name="aw_add_agent", methods={"GET", "POST"}, options={"expose"=true})
     * @Template("@AwardWalletMain/Agent/addAgentPage.html.twig")
     */
    public function addAgentPageAction(
        Request $request,
        ConnectionsPersonsWidget $connectionsPersonsWidget,
        Handler $formAddAgentHandlerDesktop,
        AddAgentGeneric $addAgentGeneric,
        UsrRepository $usrRepository,
        PageVisitLogger $pageVisitLogger
    ) {
        /** @var Usr $user */
        $user = $this->getUser();
        $session = $request->getSession();

        if ($request->isMethod('GET')) {
            $session->set('return_url', preg_replace('/\?.+$/', "", $request->headers->get('referer')));
        }

        $connectionsPersonsWidget->setActiveItem('add-new');
        $agent = new Useragent();
        $form = $this->createForm(AddAgentType::class, $agent);
        $quantityError = $this->checkQuantity();

        $generateFormResponse = function () use ($quantityError, $form, $user, $usrRepository) {
            return [
                'quantityError' => $quantityError,
                'form' => $form->createView(),
                'isBusinessAdmin' => !empty($usrRepository->getBusinessByUser($user)),
                'maxUsers' => PERSONAL_INTERFACE_MAX_USERS,
            ];
        };

        $formAddAgentHandlerDesktop->addHandlerSubscriber(
            (new Subscriber())
                ->setOnCommit(function (HandlerEvent $event) use ($session, $agent) {
                    $session->set('agentId', $agent->getId()); // Store added agent id for further use
                    $backUrl = ($session->get('return_url') && (strpos($session->get('return_url'), 'add-connection') === false)) ?
                        $session->get('return_url') : $this->generateUrl('aw_account_list');

                    if (false !== strpos($backUrl, '/account/add/') && false === strpos($backUrl, '?')) {
                        $backUrl .= '?agentId=' . $agent->getId();
                    }

                    $session->remove('return_url');
                    $event->setResponse($this->redirect($backUrl));
                })
                ->setOnException(
                    $addAgentGeneric->createExceptionHandler($generateFormResponse)
                )
        );

        if ($response = $formAddAgentHandlerDesktop->handleRequestTransactionally($form, $request)) {
            return $response;
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_ADD_NEW_PERSON);

        return $generateFormResponse();
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Route("/add-connection", name="aw_create_connection", options={"expose"=true})
     * @Template("@AwardWalletMain/Agent/addConnection.html.twig")
     */
    public function addConnectionAction(
        Request $request,
        ConnectionsPersonsWidget $connectionsPersonsWidget,
        ConnectionManager $connectionManager,
        UseragentRepository $useragentRepository,
        UsrRepository $usrRepository
    ) {
        if ($this->checkQuantity()) {
            return $this->render('@AwardWalletMain/Agent/maxConnectionsError.html.twig');
        }

        $connectionsPersonsWidget->setActiveItem('add-new');

        $user = $this->tokenStorage->getBusinessUser();
        $form = $this->getConnectForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('USER_IMPERSONATED')) {
                throw new ImpersonatedException();
            }

            $email = $form->get('email')->getViewData();
            $invitee = $usrRepository->matching($this->getUserCriteria($user, $email));
            $found = $invitee->count() > 0;
            $template = '@AwardWalletMain/Agent/connectStep4.html.twig';

            try {
                if ($found && $useragentRepository->isExistingConnection($user, $invitee->first())) {
                    $template = '@AwardWalletMain/Agent/connectExistingError.html.twig';
                } elseif ($found) {
                    $connectionManager->connectUser($invitee->first(), $user);
                } else {
                    $connectionManager->inviteUser($email, $user);
                }
            } catch (MailerExceptionInterface $_) {
                // error propagated by header
            }

            return $this->render($template, [
                'email' => $email,
            ]);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/send-reminder", name="aw_connection_send_reminder", methods={"POST"}, options={"expose"=true})
     */
    public function sendReminderAction(
        Request $request,
        ConnectionManager $connectionManager,
        EntityRepository $inviteCodeRepository,
        UseragentRepository $useragentRepository
    ): JsonResponse {
        $user = $this->tokenStorage->getBusinessUser();
        $emailInviteId = $request->request->get('email_invite_id');
        $inviteeId = $request->request->get('invitee_id');
        $userIp = $request->getClientIp();

        if ($emailInviteId) {
            /** @var Invitecode $invite */
            $invite = $inviteCodeRepository->find($emailInviteId);

            if (empty($invite)) {
                return $this->json(['success' => false]);
            }

            try {
                [$success, $failReason] = $connectionManager->sendReminderBruteforceSafe(
                    $invite,
                    $user,
                    $userIp
                );
            } catch (MailerExceptionInterface $_) {
                // error propagated by header
                return $this->json(['success' => true]);
            }

            if ($success) {
                return $this->json(['success' => true]);
            } else {
                return $this->json(['error' => $failReason]);
            }
        } elseif ($inviteeId) {
            $userAgent = $useragentRepository->findOneBy([
                'clientid' => $user->getId(),
                'agentid' => $inviteeId,
            ]);

            if (!$userAgent) {
                return $this->json(['success' => false]);
            }

            try {
                [$success, $failReason] = $connectionManager->sendReminderBruteforceSafe(
                    $userAgent,
                    $user,
                    $userIp
                );
            } catch (MailerExceptionInterface $e) {
                // error propagated by header
                return $this->json(['success' => true]);
            }

            if ($success) {
                return $this->json(['success' => true]);
            } elseif (\is_string($failReason)) {
                return $this->json(['error' => $failReason]);
            }
        }

        return $this->json(['success' => false]);
    }

    public function lockerListener(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (is_null($data)) {
            return;
        }

        $userIp = $this->requestStack->getCurrentRequest()->getClientIp();
        $error = $this->antiBruteforceLockerService->checkForLockout($userIp);

        if (!empty($error)) {
            /** @Desc("Please wait 5 minutes before next attempt.") */
            $message = $this->translator->trans('connection.user_lockout');

            $form->addError(new FormError($message));
        }
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('agents.popup.header'))->setDesc('Select connection type'),
            (new Message('agents.popup.connect.btn'))->setDesc('Connect with another person'),
            (new Message('agents.popup.add.btn'))->setDesc('Just add a new name'),
            (new Message('email.addresses'))->setDesc('Email addresses'),
            (new Message('agents.popup.content'))->setDesc('You have two options, you can connect with another person on AwardWallet, or you can just create another name to better organize your rewards.'),
        ];
    }

    protected function getUserCriteria(Usr $user, string $email)
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('email', $email),
                    Criteria::expr()->neq('userid', $user->getUserid()),
                    Criteria::expr()->in('accountlevel', [ACCOUNT_LEVEL_FREE, ACCOUNT_LEVEL_AWPLUS])
                )
            );

        return $criteria;
    }

    private function getConnectForm(Usr $user): FormInterface
    {
        /** @var \Symfony\Component\Form\Form $form */
        $form = $this->createFormBuilder()
            ->add('email', TextType::class, [
                'required' => true,
                'allow_urls' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 80]),
                    new Email(),
                    new Callback(function ($object, ExecutionContextInterface $context) use ($user) {
                        if ($user->getEmail() == trim($object)) {
                            $context->buildViolation(
                                $this->translator->trans(/** @Desc("You are not able to connect with yourself") */ 'agents.connect.email.yourself')
                            )
                                ->atPath('email')
                                ->addViolation();
                        }
                    }),
                    new Callback(function ($object, ExecutionContextInterface $context) use ($user) {
                        if (\EMAIL_VERIFIED !== $user->getEmailverified()) {
                            $context
                                ->buildViolation(
                                    $this->translator->trans(
                                        /** @Desc("Your email has not been verified; please %link_on%verify your email%link_off% before proceeding.") */
                                        'email.not_verified',
                                        [
                                            '%link_on%' => '<a target="_blank" href="' . $this->router->generate('aw_profile_overview') . '">',
                                            '%link_off%' => '</a>',
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
                    'notice' => $this->translator->trans('agents.connect.email.notice'),
                ],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, "lockerListener"])
            ->getForm();

        return $form;
    }

    private function checkQuantity()
    {
        $user = $this->tokenStorage->getBusinessUser();
        $connectionsCount = $this->counter->getConnectedUsers($user);
        $connectionsCount += $this->counter->getInvites($user);

        return !$this->isGranted('SITE_BUSINESS_AREA') && $connectionsCount >= PERSONAL_INTERFACE_MAX_USERS;
    }
}
