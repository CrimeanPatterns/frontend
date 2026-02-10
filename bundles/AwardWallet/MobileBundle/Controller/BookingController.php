<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Event\BookingMessage;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MobileBundle\Form\Type\AbMessageType;
use AwardWallet\MobileBundle\View\Booking\Messages\MessageCriterion;
use AwardWallet\MobileBundle\View\Booking\Messages\MessagesFormatter;
use AwardWallet\MobileBundle\View\Booking\Messages\MessagesLoader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/booking/request/{abRequest}/chunk/{oldestSeenMessageId}",
     *     name="awm_newapp_booking_messages_chunk",
     *     defaults= {"oldestSeenMessageId" = null},
     *     requirements = {
     *         "oldestSeenMessageId" = "\d+",
     *         "abRequest" = "\d+"
     *     }
     * )
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @param int|null $oldestSeenMessageId
     * @return JsonResponse
     */
    public function loadPastMessagesAction(
        AbRequest $abRequest,
        $oldestSeenMessageId = null,
        MessagesLoader $awMobileViewBookingMessageLoader
    ) {
        if (!$this->isGranted('VIEW', $abRequest)) {
            throw $this->createNotFoundException();
        }

        $messages = $awMobileViewBookingMessageLoader->loadMessageViewBeforeOldest($abRequest, $this->getCurrentUser(), $oldestSeenMessageId);

        return new JsonResponse(['messages' => $messages]);
    }

    /**
     * @Route("/booking/request/{abRequest}/sync/{markAsRead}",
     *     name="awm_newapp_booking_messages_sync",
     *     methods={"POST"},
     *     defaults = {"markAsRead" = 1},
     *     requirements = {
     *         "markAsRead" = "1|0",
     *         "abRequest" = "\d+"
     *     }
     * )
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @JsonDecode
     * @return JsonResponse
     */
    public function syncMessagesAction(
        Request $request,
        AbRequest $abRequest,
        $markAsRead,
        MessagesLoader $awMobileViewBookingMessageLoader,
        BookingRequestManager $bookingRequestManager,
        EntityManagerInterface $entityManager
    ) {
        if (!$this->isGranted('VIEW', $abRequest)) {
            throw $this->createNotFoundException();
        }

        $messagesMetadata = $request->get('messages', []);

        if (!is_array($messagesMetadata)) {
            return new JsonResponse(['success' => false]);
        }

        $keys = array_keys($messagesMetadata);
        $messagesIds = array_filter(
            $keys,
            function ($value, $key) { return ((string) (int) $key === (string) $key) && is_int($value); },
            ARRAY_FILTER_USE_BOTH
        );

        if (count($keys) !== count($messagesIds)) {
            return new JsonResponse(['success' => false]);
        }

        $response = new JsonResponse([
            'messages' => $messages = $awMobileViewBookingMessageLoader->syncRequestMessages($abRequest, $this->getCurrentUser(), $messagesMetadata),
            'success' => true,
        ]);

        if (
            $markAsRead
            && $messages
            && ($id = $messages[count($messages) - 1]->id)
        ) {
            $this->markReadAction($abRequest, $id, $bookingRequestManager, $entityManager);
        }

        return $response;
    }

    /**
     * @Route("/booking/request/{abRequest}/read/{lastReadId}",
     *     name="awm_newapp_booking_messages_read",
     *     requirements = {
     *         "abRequest" = "\d+",
     *         "lastReadId" = "\d+"
     *     }
     * )
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @param int $lastReadId
     * @return JsonResponse
     */
    public function markReadAction(
        AbRequest $abRequest,
        $lastReadId,
        BookingRequestManager $bookingRequestManager,
        EntityManagerInterface $entityManager
    ) {
        if (!$this->isGranted('VIEW', $abRequest)) {
            throw $this->createNotFoundException();
        }

        $messages = $entityManager
            ->getRepository(AbMessage::class)
            ->findBy(['RequestID' => $abRequest->getAbRequestID(), 'AbMessageID' => $lastReadId]);

        if ($messages) {
            $bookingRequestManager->markAsRead(
                $abRequest,
                $this->getCurrentUser(),
                $messages[0]->getCreateDate()
            );
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/booking/request/{abRequest}/message/add",
     *     name="awm_newapp_booking_messages_add",
     *     methods={"PUT"},
     *     requirements = {
     *         "abRequest" = "\d+"
     *     }
     * )
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     * @return JsonResponse
     */
    public function addMessageAction(
        Request $request,
        AbRequest $abRequest,
        Handler $awFormAbMessageHandler,
        MessagesFormatter $awMobileViewBookingMessagesFormatter
    ) {
        if (!$this->isGranted('VIEW', $abRequest)) {
            throw $this->createNotFoundException();
        }

        $message = (new AbMessage($user = $this->getCurrentUser(), $abRequest, AbMessage::TYPE_COMMON));
        $form = $this->createForm(AbMessageType::class, $message, [
            'method' => 'PUT',
            'csrf_protection' => false,
        ]);
        // TODO: change shitty client form request data structure
        $request->request->replace([
            $form->getName() => $request->request->all(),
        ]);

        if ($awFormAbMessageHandler->handleRequest($form, $request)) {
            return $this->successJsonResponse([
                'message' => $awMobileViewBookingMessagesFormatter->formatMessage($message, new MessageCriterion($abRequest, $this->getCurrentUser())),
            ]);
        }

        return $this->errorJsonResponse($this->getFormError($form));
    }

    /**
     * @Route("/booking/request/{abRequest}/message/edit/{abMessage}",
     *     name="awm_newapp_booking_messages_edit",
     *     methods={"POST"},
     *     requirements = {
     *         "abRequest" = "\d+",
     *         "abMessage" = "\d+"
     *     }
     * )
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @ParamConverter("abMessage", class="AwardWalletMainBundle:AbMessage")
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     * @return JsonResponse
     */
    public function editMessageAction(
        Request $request,
        AbRequest $abRequest,
        AbMessage $abMessage,
        Handler $awFormAbMessageHandler,
        MessagesFormatter $awMobileViewBookingMessagesFormatter
    ) {
        if (
            !$this->isGranted('VIEW', $abRequest)
            || !$abRequest->getMessages()->contains($abMessage)
            || !$this->isGranted('EDIT', $abMessage)
        ) {
            throw $this->createNotFoundException();
        }

        $user = $this->getCurrentUser();
        $form = $this->createForm(AbMessageType::class, $abMessage, [
            'method' => 'POST',
            'csrf_protection' => false,
        ]);
        // TODO: change shitty client form request data structure
        $request->request->replace([
            $form->getName() => $request->request->all(),
        ]);

        if ($awFormAbMessageHandler->handleRequest($form, $request)) {
            return $this->successJsonResponse([
                'message' => $awMobileViewBookingMessagesFormatter
                    ->formatMessage($abMessage, new MessageCriterion($abRequest, $this->getCurrentUser())),
            ]);
        }

        return $this->errorJsonResponse($this->getFormError($form));
    }

    /**
     * @Route("/booking/request/{abRequest}/message/delete/{abMessage}",
     *     name="awm_newapp_booking_messages_delete",
     *     methods={"DELETE"},
     *     requirements = {
     *         "abRequest" = "\d+",
     *         "abMessage" = "\d+"
     *     }
     * )
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @ParamConverter("abMessage", class="AwardWalletMainBundle:AbMessage")
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     * @return JsonResponse
     */
    public function deleteMessageAction(
        Request $request,
        AbRequest $abRequest,
        AbMessage $abMessage,
        EntityManagerInterface $manager,
        EventDispatcherInterface $eventDispatcher
    ) {
        if (
            !$this->isGranted('VIEW', $abRequest)
            || !$abRequest->getMessages()->contains($abMessage)
            || !$this->isGranted('DELETE', $abMessage)
        ) {
            throw $this->createNotFoundException();
        }

        $messageId = $abMessage->getAbMessageID();
        $manager->remove($abMessage);
        $manager->flush();

        $eventDispatcher->dispatch(new BookingMessage\DeleteEvent($abMessage, $messageId), 'aw.booking.message.delete');

        return $this->successJsonResponse();
    }

    /**
     * @Route("/booking/request/{abRequest}/resend",
     *     name="awm_newapp_booking_resend",
     *     methods={"POST"},
     *     requirements = {"abRequest" = "\d+"}
     * )
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     * @return JsonResponse
     */
    public function resendEmail(Request $request, AbRequest $abRequest, BookingRequestManager $bookingRequestManager)
    {
        if (!$this->isGranted('VIEW', $abRequest)) {
            throw $this->createNotFoundException();
        }

        $bookingRequestManager->sendEmailOnNewRequest($abRequest);

        return $this->successJsonResponse();
    }

    /**
     * @Route(
     *     "/booking/request/{id}/confirm/{hash}",
     *     name="awm_newapp_booking_confirm",
     *     requirements={"id" = "\d+", "hash" = "\w+"}
     * )
     */
    public function confirmEmailAction(
        int $id,
        string $hash,
        BookingRequestManager $bookingRequestManager
    ): JsonResponse {
        $abRequest = $this->getDoctrine()->getRepository(AbRequest::class)->find($id);

        if (!is_null($abRequest) && $abRequest->getConfirmationCode() === $hash) {
            $bookingRequestManager->confirmContactEmail($abRequest);

            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false]);
    }

    private function getFormError(FormInterface $form)
    {
        /** @var FormErrorIterator $error */
        $errors = $form->getErrors(true);

        if (sizeof($errors) > 0) {
            return $errors[0]->getMessage();
        }

        return 'Unknown error';
    }
}
