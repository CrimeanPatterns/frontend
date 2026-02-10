<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Scanner\MailboxManager;
use AwardWallet\MainBundle\Scanner\MailboxOwnerHelper;
use AwardWallet\MainBundle\Scanner\MailboxProgress;
use AwardWallet\MainBundle\Scanner\MailboxStatusHelper;
use AwardWallet\MainBundle\Scanner\UserAgentValidator;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/mailboxes")
 */
class UserMailboxController extends AbstractController implements TranslationContainerInterface
{
    private AwTokenStorageInterface $tokenStorage;
    private EmailScannerApi $scannerApi;
    private PageVisitLogger $pageVisitLogger;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        EmailScannerApi $scannerApi,
        PageVisitLogger $pageVisitLogger
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->scannerApi = $scannerApi;
        $this->pageVisitLogger = $pageVisitLogger;
    }

    /**
     * @Security("is_granted('ROLE_USER') && !is_granted('SITE_BUSINESS_AREA')")
     * @Route("/", name="aw_usermailbox_view", methods={"GET"}, options={"expose"=true})
     * @Template("@AwardWalletMain/UserMailbox/view.html.twig")
     */
    public function viewAction(
        MailboxStatusHelper $statusHelper,
        MailboxOwnerHelper $ownerHelper,
        SessionInterface $session,
        SocksClient $socksClient
    ) {
        $user = $this->tokenStorage->getBusinessUser();
        $mailboxes = $this->scannerApi->listMailboxes(["user_" . $user->getUserid()]);
        $flash = $session->getFlashBag()->get('mailbox_type');

        if (empty($flash)) {
            $addedType = null;
        } else {
            $addedType = array_shift($flash);
        }

        $this->pageVisitLogger->log(PageVisitLogger::PAGE_ADD_CHANGE_CONNECTED_MAILBOXES);

        return [
            'mailboxes' => $mailboxes,
            'owners' => it($mailboxes)
                ->map(function (Mailbox $mailbox) {
                    return $mailbox->getUserData();
                })
                ->map([$ownerHelper, "getOwnerByUserData"])
                ->map(function (Owner $owner) {
                    return $owner->getFullName();
                })
                ->toArray(),
            'icons' => array_map([$statusHelper, "mailboxIcon"], $mailboxes),
            'statuses' => array_map([$statusHelper, "mailboxStatus"], $mailboxes),
            'added_type' => $addedType,
            'centrifuge_config' => $socksClient->getClientData(),
            'userId' => $user->getUserid(),
            'family_members' => it($user->getFamilyMembers())
                ->map(function (Useragent $ua) {
                    return ['useragentid' => $ua->getUseragentid(), 'fullName' => $ua->getFullName()];
                })
                ->toArray(),
        ];
    }

    /**
     * @Route("/add", name="aw_usermailbox_add", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('CSRF') and is_granted('ROLE_USER')")
     */
    public function addAction(Request $request, TranslatorInterface $translator, UserAgentValidator $userAgentValidator, MailboxManager $mailboxManager)
    {
        $email = $request->request->get("email");
        $password = $request->request->get("password");
        $agentId = $userAgentValidator->checkAgentId($request->query->get('agentId'));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                "status" => "error",
                "error" => $translator->trans("ndr.popup.title"),
            ]);
        }

        $type = $this->scannerApi->detectType($email)->getType();

        if ($type != Mailbox::TYPE_IMAP) {
            return new JsonResponse(["status" => "redirect", "url" => $this->generateUrl("aw_usermailbox_oauth", ["type" => $type, "agentId" => $agentId])]);
        }

        if (empty($password)) {
            return new JsonResponse(["status" => "ask_password"]);
        }

        $mailboxManager->addImap($this->tokenStorage->getBusinessUser(), $email, $password, $agentId);
        $this->pageVisitLogger->log(PageVisitLogger::PAGE_ADD_CHANGE_CONNECTED_MAILBOXES);

        return new JsonResponse(["status" => "added"]);
    }

    /**
     * @Route("/delete", name="aw_usermailbox_delete", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('CSRF') and is_granted('ROLE_USER')")
     */
    public function deleteAction(Request $request, MailboxManager $mailboxManager)
    {
        $mailbox = $mailboxManager->delete($this->tokenStorage->getBusinessUser(), $request->request->get('id'));

        if (!$mailbox) {
            throw new BadRequestHttpException();
        }

        return new JsonResponse(["success" => true, "error" => null, "content" => ""]);
    }

    /**
     * @Route("/send-progress", name="aw_usermailbox_send_progress", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('CSRF') and is_granted('ROLE_USER')")
     */
    public function sendProgressAction(Request $request, MailboxProgress $mailboxProgress)
    {
        $user = $this->tokenStorage->getBusinessUser();
        $mailboxes = $this->scannerApi->listMailboxes(["user_" . $user->getUserid()]);
        $mailboxProgress->sendProgressUpdates($user->getUserid(), $mailboxes);

        return new JsonResponse("ok");
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('mailbox.status.connecting'))->setDesc('Connecting to the mailbox...'),
            (new Message('mailbox.status.error.connection'))->setDesc('Could not connect to the mailbox.'),
            (new Message('mailbox.status.error.authentication'))->setDesc('Invalid credentials.'),
            (new Message('mailbox.status.error.unknown'))->setDesc('An error occurred while scanning the mailbox. We will try again later.'),
            (new Message('mailbox.status.error.connection-lost'))->setDesc('Connection lost.'),
            (new Message('mailbox.status.listening'))->setDesc('Successfully connected and waiting for new emails to arrive.'),
            (new Message('mailbox.status.scanning'))->setDesc('Successfully connected and scanning existing emails.'),
        ];
    }
}
