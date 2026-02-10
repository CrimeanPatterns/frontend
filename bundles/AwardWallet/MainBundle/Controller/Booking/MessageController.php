<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbPhoneNumber;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\BookingServiceFee;
use AwardWallet\MainBundle\Event\BookingMessage;
use AwardWallet\MainBundle\Form\Type\AbInvoiceType;
use AwardWallet\MainBundle\Form\Type\AbMessageEditType;
use AwardWallet\MainBundle\Form\Type\AbMessageType;
use AwardWallet\MainBundle\Form\Type\AbPhoneNumberType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\FormErrorHandler;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\BookingRespond;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;
use AwardWallet\MainBundle\Service\Booking\MessageFormatter;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/awardBooking")
 */
class MessageController extends AbstractController implements TranslationContainerInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private BookingRequestManager $bookingRequestManager;
    private DefaultBookerParameter $defaultBookerParameter;
    private EventDispatcherInterface $eventDispatcher;
    private RouterInterface $router;
    private EntityManagerInterface $entityManager;
    private AwTokenStorageInterface $tokenStorage;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        BookingRequestManager $bookingRequestManager,
        DefaultBookerParameter $defaultBookerParameter,
        EventDispatcherInterface $eventDispatcher,
        RouterInterface $router,
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $tokenStorage
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->bookingRequestManager = $bookingRequestManager;
        $this->defaultBookerParameter = $defaultBookerParameter;
        $this->eventDispatcher = $eventDispatcher;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Route("/getMessages/{internal}/{withContainer}/{id}", name="aw_booking_message_getmessages", requirements={"id" = "\d+", "internal" = "internal|common", "withContainer" = "0|1"}, defaults={"withContainer" = 1}, options={"expose"=true})
     * @Security("is_granted('VIEW', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function getMessagesAction(AbRequest $abRequest, $internal, $withContainer = true, Request $req)
    {
        $readed_time = $req->get('readed');
        $isInternal = (strtolower($internal) == 'internal');

        $this->bookingRequestManager->markAsRead($abRequest, $this->tokenStorage->getBusinessUser(), new \DateTime());

        if (!$this->authorizationChecker->isGranted('USER_BOOKING_REFERRAL') && $isInternal) {
            throw new AccessDeniedException();
        }

        $messages = $abRequest->getMessages();
        $internals = [];
        $common = [];

        /** @var $message AbMessage */
        foreach ($messages as $message) {
            if ($message->isInternal()) {
                $internals[] = $message;
            } else {
                $common[] = $message;
            }

            if ($message->getUser() != $this->getUser()) {
                $readed_time = (new \DateTime('now'))->modify('-1 minute');
            }
        }

        $agents = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $requests = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);

        $defaultBooker = $this->defaultBookerParameter->get();

        if ($withContainer) {
            return $this->render('@AwardWalletMain/Booking/Message/message.html.twig', [
                'messages' => $isInternal ? $internals : $common,
                'agentsRep' => $agents,
                'reqRep' => $requests,
                'usrRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class),
                'request' => $abRequest,
                'readed' => $readed_time,
                'msgReplacedVars' => MessageFormatter::getMessageReplacedVars($abRequest),
                'defaultBooker' => $defaultBooker,
            ]);
        }

        $response = [];

        foreach ($isInternal ? $internals : $common as $message) {
            $response["message_" . $message->getAbMessageID()] = [
                "lastUpdated" => $message->getLastUpdateDate() ? $message->getLastUpdateDate()->getTimestamp() : 0,
                "html" => $this->render('@AwardWalletMain/Booking/Message/message.html.twig', [
                    'messages' => [$message],
                    'agentsRep' => $agents,
                    'reqRep' => $requests,
                    'usrRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class),
                    'request' => $abRequest,
                    'readed' => $readed_time,
                    'msgReplacedVars' => MessageFormatter::getMessageReplacedVars($abRequest),
                    'defaultBooker' => $defaultBooker,
                ])->getContent(),
            ];
        }

        return new JsonResponse($response);
    }

    /**
     * @Security("is_granted('CSRF') and is_granted('VIEW', abRequest)")
     * @Route("/add_message_ajax/{id}", name="aw_booking_message_ajaxaddmessage", methods={"POST"}, requirements={"id" = "\d+"}, options={"expose"=true})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function ajaxAddMessageAction(Request $request, AbRequest $abRequest)
    {
        $message = new AbMessage();
        $messageForm = $this->createForm(AbMessageType::class, $message, ['request' => $abRequest]);
        $user = $this->tokenStorage->getToken()->getUser();

        $messageColorRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbMessageColor::class);

        $messageForm->handleRequest($request);

        if ($messageForm->isSubmitted()) {
            if ($messageForm->isValid()) {
                /** @var AbMessage $AbMessage */
                $AbMessage = $messageForm->getData();
                $AbMessage->setUser($user);
                $AbMessage->setRequest($abRequest);
                $AbMessage->setType(($messageForm->has('Internal') && $messageForm->get('Internal')->getData()) ? AbMessage::TYPE_INTERNAL : AbMessage::TYPE_COMMON);

                if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
                    $AbMessage->setFromBooker(true);
                } else {
                    $AbMessage->setPost(nl2br(htmlspecialchars($AbMessage->getPost())));
                    $AbMessage->setFromBooker(false);
                }

                if ($messageForm->has('Color') && $messageForm->get('Color')->getData()) {
                    if ($color = $messageColorRep->find($messageForm->get('Color')->getData())) {
                        $AbMessage->setColor($color);
                    }
                } else {
                    $AbMessage->setColor(null);
                }

                $abRequest->addMessage($AbMessage);
                $this->bookingRequestManager->addMessage($AbMessage);
                $this->bookingRequestManager->flush();

                $messageType = BookingRespond::TYPE_INCLUDE;

                $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($AbMessage, [
                    'emailClass' => BookingRespond::class,
                    'emailClassType' => $messageType,
                ]), 'aw.booking.message.new');

                $messageRender = $this->render('@AwardWalletMain/Booking/Message/message.html.twig', [
                    'messages' => [$AbMessage],
                    'agentsRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class),
                    'reqRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class),
                    'usrRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class),
                    'request' => $abRequest,
                    'withContainer' => true,
                    'msgReplacedVars' => MessageFormatter::getMessageReplacedVars($abRequest),
                    'defaultBooker' => $this->defaultBookerParameter->get(),
                ])->getContent();

                $response = [
                    'success' => true,
                    'redirect' => $this->router->generate('aw_booking_view_index', ['id' => $abRequest->getAbRequestID(), '_' => rand()]) . '#addMessageForm',
                    'type' => ($messageForm->has('Internal') && $messageForm->get('Internal')->getData()) ? 'internal' : 'common',
                    'message' => $messageRender,
                ];

                return new JsonResponse($response);
            } else {
                $response = [
                    'success' => false,
                    'errors' => [],
                ];

                /** @var Form $field */
                foreach ($messageForm as $field) {
                    $e = [];

                    foreach ($field->getErrors() as $error) {
                        $e[] = $error->getMessage();
                    }

                    if ($e) {
                        $response['errors'][$field->getName()] = implode('<br>', $e);
                    }
                }

                return new JsonResponse($response);
            }
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/add_message/{id}", name="aw_booking_message_addmessage", methods={"GET", "POST"}, requirements={"id" = "\d+"})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @Security("is_granted('VIEW', abRequest)")
     */
    public function addMessageAction(Request $request, AbRequest $abRequest)
    {
        // for backTo from login
        if ($request->getMethod() != 'POST') {
            return $this->redirect('/');
        }

        if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            throw $this->createNotFoundException();
        }

        $message = new AbMessage();
        $messageForm = $this->createForm(AbMessageType::class, $message, ['request' => $abRequest]);
        $user = $this->tokenStorage->getToken()->getUser();

        $messageForm->handleRequest($request);

        if ($messageForm->isSubmitted()) {
            if ($messageForm->isValid()) {
                /** @var AbMessage $AbMessage */
                $AbMessage = $messageForm->getData();
                $AbMessage->setUser($user);
                $AbMessage->setRequest($abRequest);
                $AbMessage->setType(AbMessage::TYPE_COMMON);
                $AbMessage->setPost(nl2br(htmlspecialchars($AbMessage->getPost())));
                $AbMessage->setFromBooker(false);
                $AbMessage->setColor(null);

                $abRequest->addMessage($AbMessage);
                $this->bookingRequestManager->addMessage($AbMessage);
                $this->bookingRequestManager->flush();

                return $this->redirect($this->router->generate('aw_booking_view_index', ['id' => $abRequest->getAbRequestID(), '_' => rand()]) . '#message_' . $AbMessage->getAbMessageID());
            }
        }
        $errors = count($messageForm->getErrors(true, false)) > 0 ? (string) $messageForm->getErrors(true, false) : null;

        return $this->forward('AwardWallet\MainBundle\Controller\Booking\ViewController::indexAction', ['id' => $abRequest->getAbRequestID(), 'post' => $message->getPost(), 'errors' => $errors]);
    }

    /**
     * @Security("is_granted('CSRF') and is_granted('VIEW', abRequest) and is_granted('EDIT', message)")
     * @Route("/edit_message_ajax/{id}/{messageId}", name="aw_booking_message_ajaxeditmessage", requirements={"id" = "\d+", "messageId" = "\d+"})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @ParamConverter("message", class="AwardWalletMainBundle:AbMessage", options={"id" = "messageId"})
     */
    public function ajaxEditMessageAction(Request $request, AbRequest $abRequest, AbMessage $message)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($message->getRequest() != $abRequest) {
            throw new AccessDeniedException();
        }

        $messageForm = $this->createForm(AbMessageEditType::class, $message, [
            'action' => $this->router->generate('aw_booking_message_ajaxeditmessage', [
                'id' => $abRequest->getAbRequestID(),
                'messageId' => $message->getAbMessageId(),
            ]),
            'request' => $abRequest,
        ]);

        $messageColorRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbMessageColor::class);

        $messageForm->handleRequest($request);

        if ($messageForm->isSubmitted()) {
            if ($messageForm->isValid()) {
                /** @var AbMessage $AbMessage */
                $AbMessage = $messageForm->getData();
                $AbMessage->setType((($messageForm->has('Internal') && $messageForm->get('Internal')->getData()) || $message->getType() === AbMessage::TYPE_INTERNAL) ? AbMessage::TYPE_INTERNAL : AbMessage::TYPE_COMMON);

                if (!$this->authorizationChecker->isGranted('SITE_BUSINESS_AREA') || !$this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($user)) {
                    $AbMessage->setPost(nl2br(htmlspecialchars($AbMessage->getPost())));
                } else {
                    $AbMessage->setPost($AbMessage->getPost());
                }

                if ($messageForm->has('Color') && $messageForm->get('Color')->getData()) {
                    if ($color = $messageColorRep->find($messageForm->get('Color')->getData())) {
                        $AbMessage->setColor($color);
                    }
                } else {
                    $AbMessage->setColor(null);
                }
                $AbMessage->setLastUpdateDate(new \DateTime());

                $this->entityManager->flush();

                $messageRender = $this->render('@AwardWalletMain/Booking/Message/message.html.twig', [
                    'messages' => [$AbMessage],
                    'agentsRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class),
                    'reqRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class),
                    'usrRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class),
                    'request' => $abRequest,
                    'withContainer' => true,
                    'msgReplacedVars' => MessageFormatter::getMessageReplacedVars($abRequest),
                    'defaultBooker' => $this->defaultBookerParameter->get(),
                ])->getContent();

                $response = [
                    'success' => true,
                    'type' => $message->getType() == AbMessage::TYPE_INTERNAL ? 'internal' : 'common',
                    'message' => $messageRender,
                ];

                $this->eventDispatcher->dispatch(new BookingMessage\EditEvent($message), 'aw.booking.message.edit');

                return new JsonResponse($response);
            } else {
                $response = [
                    'success' => false,
                    'errors' => [],
                ];

                /** @var Form $field */
                foreach ($messageForm as $field) {
                    $e = [];

                    foreach ($field->getErrors() as $error) {
                        $e[] = $error->getMessage();
                    }

                    if ($e) {
                        $response['errors'][$field->getName()] = implode('<br>', $e);
                    }
                }

                return new JsonResponse($response);
            }
        }

        $formRender = $this->render('@AwardWalletMain/Booking/Message/editMessage.html.twig', [
            'messageForm' => $messageForm->createView(),
            'message' => $message,
            'request' => $abRequest,
        ])->getContent();
        $response = [
            'success' => true,
            'form' => $formRender,
        ];

        return new JsonResponse($response);
    }

    /**
     * @Security("is_granted('CSRF') and is_granted('VIEW', abRequest) and is_granted('DELETE', message)")
     * @Route("/delete_message_ajax/{id}/{messageId}", name="aw_booking_message_ajaxdeletemessage", requirements={"id" = "\d+", "messageId" = "\d+"})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @ParamConverter("message", class="AwardWalletMainBundle:AbMessage", options={"id" = "messageId"})
     */
    public function ajaxDeleteMessageAction(AbRequest $abRequest, AbMessage $message)
    {
        if ($message->getRequest() != $abRequest) {
            throw new AccessDeniedException();
        }

        $messageId = $message->getAbMessageID();
        $this->entityManager->remove($message);
        $this->entityManager->flush();

        $response = [
            'success' => true,
        ];

        $this->eventDispatcher->dispatch(new BookingMessage\DeleteEvent($message, $messageId), 'aw.booking.message.delete');

        return new JsonResponse($response);
    }

    /**
     * @Route("/create_invoice/{id}", name="aw_booking_message_createinvoice", requirements={"id" = "\d+"}, options={"expose" = true})
     * @Security("is_granted('VIEW', abRequest) and is_granted('BOOKER', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function createInvoiceAction(
        AbRequest $abRequest,
        Request $request,
        TranslatorInterface $translator,
        LocalizeService $localizeService
    ) {
        $data = new AbInvoice();
        $bookerInfo = $abRequest->getBooker()->getBookerInfo();
        $bookerFee = $bookerInfo->getPrice();

        // AwardMagic
        if ($bookerInfo->getUserID()->getUserid() === 327644) {
            $descriptions = [
                [
                    'label' => $translator->trans('invoice.item.award-booking-standard', [], 'booking'),
                    'value' => '179',
                ],
                [
                    'label' => $translator->trans('invoice.item.award-booking-magic', [], 'booking'),
                    'value' => '279',
                ],
                [
                    'label' => $translator->trans('invoice.item.airline-fees', [], 'booking'),
                    'value' => '0',
                ],
                [
                    'label' => $translator->trans('invoice.item.award-monitoring', [], 'booking'),
                    'value' => '70',
                ],
                [
                    'label' => $translator->trans('invoice.item.ticket-cancellation', [], 'booking'),
                    'value' => '70',
                ],
                [
                    'label' => $translator->trans('invoice.item.booking-fee', [], 'booking'),
                    'value' => '25',
                ],
                [
                    'label' => $translator->trans('invoice.item.refund-deposit', [], 'booking'),
                    'value' => '-25',
                ],
            ];
        } else {
            $descriptions = [
                [
                    'label' => $translator->trans('invoice.item.service-fee', [], 'booking'),
                    'value' => null,
                ],
                [
                    'label' => $translator->trans('invoice.item.taxes', [], 'booking'),
                    'value' => null,
                ],
                [
                    'label' => $translator->trans('invoice.item.deposit', [], 'booking'),
                    'value' => null,
                ],
                [
                    'label' => $translator->trans('invoice.item.additional-fee', [], 'booking'),
                    'value' => null,
                ],
                [
                    'label' => $translator->trans('invoice.item.upgrade-fee', [], 'booking'),
                    'value' => null,
                ],
            ];
        }

        $getDefaultItem = function () use ($descriptions, $bookerFee) {
            return (new BookingServiceFee())
                ->setDescription($descriptions[0]['label'])
                ->setPrice($bookerFee == intval($bookerFee) ? intval($bookerFee) : $bookerFee)
                ->setQuantity(1)
                ->setDiscount(0);
        };

        $data->addItem($getDefaultItem());

        $invoiceForm = $this->createForm(AbInvoiceType::class, $data, [
            'attr' => ['request' => $abRequest],
        ]);

        if ($request->isMethod('POST')) {
            $invoiceForm->submit($request->request->get($invoiceForm->getName()));
        }

        $params = [
            'thousandsSeparator' => $localizeService->getThousandsSeparator(),
            'decimalPoint' => $localizeService->getDecimalPoint(),
            'currency' => $abRequest->getBooker()->getBookerInfo()->getCurrency(),
            'request' => $abRequest,
            'descriptions' => $descriptions,
        ];

        if ($invoiceForm->isSubmitted() && $invoiceForm->isValid()) {
            $this->bookingRequestManager->addInvoice($invoiceForm->getData(), $abRequest);

            return new JsonResponse([
                'status' => 'success',
                'content' => $this->renderView('@AwardWalletMain/Booking/Message/invoiceForm.html.twig', array_merge($params, [
                    'invoiceForm' => $this->createForm(AbInvoiceType::class, (new AbInvoice())->addItem($getDefaultItem()), [
                        'attr' => ['request' => $abRequest],
                    ])->createView(),
                ])),
            ]);
        } else {
            $params['invoiceForm'] = $invoiceForm->createView();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'status' => 'fail',
                'content' => $this->renderView('@AwardWalletMain/Booking/Message/invoiceForm.html.twig', $params),
            ]);
        } else {
            return $this->render('@AwardWalletMain/Booking/Message/invoiceForm.html.twig', $params);
        }
    }

    /**
     * @Route("/seat_assignments/{id}", name="aw_booking_message_seatassignments", requirements={"id" = "\d+"}, options={"expose" = true})
     * @Security("is_granted('VIEW', abRequest) and is_granted('BOOKER', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function seatAssignmentsAction(AbRequest $abRequest, Request $request, FormErrorHandler $formErrorHandler)
    {
        $createForm = function ($controller) {
            $form = $controller->createFormBuilder(null, [
                'data_class' => 'AwardWallet\\MainBundle\\Entity\\AbMessage',
                //                'cascade_validation' => true,
            ])
                ->add('PhoneNumbers', CollectionType::class, [
                    'entry_type' => AbPhoneNumberType::class,
                    /** @Ignore */
                    'label' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'by_reference' => false,
                    'constraints' => new Valid(),
                ])->getForm();
            $form->setData((new AbMessage())->addPhoneNumber(new AbPhoneNumber()));

            return $form;
        };

        $form = $createForm($this);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var AbMessage $m */
                $m = $form->getData();
                $m->setType(AbMessage::TYPE_SEAT_ASSIGNMENTS);
                $m->setUser($this->tokenStorage->getToken()->getUser());
                $m->setFromBooker(true);
                $abRequest->addMessage($m);
                $this->bookingRequestManager->addMessage($m);
                $this->bookingRequestManager->flush();

                $template = new BookingRespond();
                $template->type = BookingRespond::TYPE_BASIC;
                $template->request = $abRequest;
                $template->message = $m;
                $this->bookingRequestManager->sendEmail($template, false);

                $this->eventDispatcher->dispatch(new BookingMessage\NewEvent($m), 'aw.booking.message.new');

                return new JsonResponse([
                    'status' => 'success',
                    'form' => $this->renderView('@AwardWalletMain/Booking/Message/seatAssignmentsForm.html.twig', [
                        'form' => $createForm($this)->createView(),
                    ]),
                ]);
            } else {
                return new JsonResponse([
                    'status' => 'fail',
                    'errors' => $formErrorHandler->getFormErrors($form, true, false),
                ]);
            }
        }

        return $this->render('@AwardWalletMain/Booking/Message/seatAssignmentsForm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('invoice.item.deposit', 'booking'))->setDesc('Award Booking Deposit'),
            (new Message('invoice.item.additional-fee', 'booking'))->setDesc('Additional Traveler Fee'),
            (new Message('invoice.item.upgrade-fee', 'booking'))->setDesc('Upgrade Fee'),
            (new Message('invoice.item.service-fee', 'booking'))->setDesc('Award Booking Service Fee'),
            (new Message('invoice.item.taxes', 'booking'))->setDesc('Airline Taxes'),
            (new Message('invoice.item.award-booking-standard', 'booking'))->setDesc('"Standard Award" Booking'),
            (new Message('invoice.item.award-booking-magic', 'booking'))->setDesc('"Magic Award" Booking'),
            (new Message('invoice.item.airline-fees', 'booking'))->setDesc('Airline-imposed taxes/fees'),
            (new Message('invoice.item.award-monitoring', 'booking'))->setDesc('Award Monitoring Service'),
            (new Message('invoice.item.ticket-cancellation', 'booking'))->setDesc('Ticket Cancellation'),
            (new Message('invoice.item.booking-fee', 'booking'))->setDesc('Close-in Booking fee'),
            (new Message('invoice.item.refund-deposit', 'booking'))->setDesc('Refund of Initial Deposit'),
            (new Message('invoice.item.award_magic_initial_deposit', 'booking'))->setDesc('Initial Award Search Deposit'),
        ];
    }
}
