<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Entity\AbCustomProgram;
use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Form\Type\PasswordMaskType;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Manager\Exception\ProgramManagerRequiredException;
use AwardWallet\MainBundle\Manager\Exception\ProgramOwnerRequiredException;
use AwardWallet\MainBundle\Manager\LogoManager;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/awardBooking")
 */
class ShareController extends AbstractController
{
    private ProgramShareManager $programShareManager;
    private BookingRequestManager $bookingRequestManager;
    private RouterInterface $router;
    private EntityManagerInterface $entityManager;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        ProgramShareManager $programShareManager,
        BookingRequestManager $bookingRequestManager,
        RouterInterface $router,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->programShareManager = $programShareManager;
        $this->bookingRequestManager = $bookingRequestManager;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Share programs in request.
     *
     * @Route("/share/{id}", name="aw_booking_share_index", requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('VIEW', abRequest) and is_granted('AUTHOR', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @Template("@AwardWalletMain/Booking/Share/index.html.twig")
     */
    public function indexAction(
        AbRequest $abRequest,
        LogoManager $logoManager,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory
    ) {
        $logoManager->setBookingRequest($abRequest);
        $usrAccounts = $accountListManager
            ->getAccountList(
                $optionsFactory
                    ->createDefaultOptions()
                    ->set(Options::OPTION_USER, $this->getUser())
                    ->set(Options::OPTION_FILTER, ' AND p.ProviderID IS NOT NULL')
                    ->set(Options::OPTION_INDEXED_BY_HID, true)
            )
            ->getAccounts();
        $sharedTotal = $sharedNewIds = [];

        $programAccountIDs = [];
        $programManagerRequiredIDs = [];
        $savePasswords = [];

        foreach ($abRequest->getAccounts() as $program) {
            if ($program->getRequested()) {
                try {
                    $result = $this->programShareManager->shareProgram($program);

                    if ($result) {
                        $sharedNewIds[] = $program->getAbAccountProgramID();
                    }
                    $sharedTotal[] = $program;

                    if ($program->getAccount()->getSavepassword() == SAVE_PASSWORD_LOCALLY) {
                        $savePasswords[] = $program->getAccount()->getAccountid();
                    }
                } catch (ProgramManagerRequiredException $error) {
                    $programManagerRequiredIDs[] = $program->getAbAccountProgramID();
                    // cant auto share
                }
            }
            $programAccountIDs[] = $program->getAccount()->getAccountid();
        }

        $customPrograms = [];

        foreach ($abRequest->getCustomPrograms() as $custom) {
            if ($custom->getRequested() === true) {
                $customPrograms[] = $custom;
            }
        }

        $providerAccounts = [];

        foreach ($usrAccounts as $usrAccount) {
            if (in_array($usrAccount['ID'], $programAccountIDs)) {
                continue;
            }

            if ($usrAccount['Access']['edit']) {
                $providerAccounts[$usrAccount['ProviderID']][] = $usrAccount;
            }
        }

        if (count($sharedTotal)) {
            $this->bookingRequestManager->shareAccounts($this->getUser(), $abRequest, $sharedTotal, $sharedNewIds);
        }

        $label = '%label%'; // Translation dumper bug

        return [
            'usrRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class),
            'reqRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class),
            'request' => $abRequest,
            'usrAccounts' => $usrAccounts,
            'providerAccounts' => $providerAccounts,
            'notShareableIDs' => $programManagerRequiredIDs,
            'accounts' => $abRequest->getAccounts(),
            'customPrograms' => $customPrograms,
            'savePasswords' => $savePasswords,
            'passwordForm' => $this->createFormBuilder()->add('password', PasswordMaskType::class, [
                'label' => $label,
            ])->getForm()->createView(),
        ];
    }

    /**
     * Clarify custom programs in request.
     *
     * @Security("is_granted('USER_BOOKING_PARTNER')")
     * @Route("/shareResendMail/{id}", name="aw_booking_share_resendmail", requirements={"id" = "\d+"}, options={"expose"=true})
     * @ParamConverter("message", class="AwardWalletMainBundle:AbMessage")
     */
    public function resendMailAction(AbMessage $message)
    {
        if (!$message->isShareRequest()) {
            throw new AccessDeniedException();
        }

        $abRequest = $message->getRequest();

        if (!$this->authorizationChecker->isGranted('VIEW', $abRequest)) {
            throw new AccessDeniedException("Access denied to request " . $abRequest->getAbRequestID());
        }

        $agentsRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        $requested = [];

        foreach ($abRequest->getAccounts() as $program) {
            if ($program->getRequested() && in_array($program->getAbAccountProgramID(), $message->getMetadata()->getAPR())) {
                if ($agentsRep->getAccessLevel($abRequest->getUser(), $program->getAccount(), $this->getUser(), $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) < UseragentRepository::ACCESS_WRITE) {
                    $requested[] = $program;
                }
            }
        }

        foreach ($abRequest->getCustomPrograms() as $program) {
            if ($program->getRequested() && in_array($program->getAbCustomProgramID(), $message->getMetadata()->getCPR())) {
                $requested[] = $program;
            }
        }

        $this->bookingRequestManager->requestSharing($this->getUser(), $abRequest, $requested);

        return $this->redirect(
            $this->router->generate(
                'aw_booking_view_index',
                ['id' => $abRequest->getAbrequestid()]
            ) . "#message_" . $abRequest->getMessages()->last()->getAbMessageID()
        );
    }

    /**
     * Deny shared programs in request.
     *
     * @Route("/deny/{id}", name="aw_booking_share_deny", requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('VIEW', abRequest) and is_granted('AUTHOR', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function denyAction(AbRequest $abRequest)
    {
        $this->programShareManager->disconnectFromBooker($abRequest->getBooker());

        return $this->redirect($this->router->generate('aw_booking_view_index', ['id' => $abRequest->getAbrequestid()]));
    }

    /**
     * Convert custom program in request.
     *
     * @Route("/customToAccount/{id}", name="aw_booking_share_custom_to_account", requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('VIEW', abRequest) and is_granted('AUTHOR', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function customToAccountAction(Request $request, AbRequest $abRequest)
    {
        $customID = $request->query->get('customID');
        $accountID = $request->query->get('accountID');
        //        $providerID = $request->query->get('providerID');

        $accountsRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        /** @var EntityRepository $customsRep */
        $customsRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\AbCustomProgram::class);
        $account = $accountsRep->find($accountID);
        /** @var AbCustomProgram $custom */
        $custom = $customsRep->find($customID);

        if (!($account && $custom)) {
            throw new ProgramOwnerRequiredException();
        }

        $this->programShareManager->convertCustomToProgram($custom, $account);

        return $this->redirect(
            $this->router->generate(
                'aw_booking_share_index',
                ['id' => $abRequest->getAbrequestid()]
            )
        );
    }

    /**
     * Convert custom program in request.
     *
     * @Route("/customAddNew/{id}", name="aw_booking_share_add_account", requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('VIEW', abRequest) and is_granted('AUTHOR', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function customAddNewAction(Request $request, AbRequest $abRequest)
    {
        $customID = $request->query->get('customID', 0);

        /** @var EntityRepository $customsRep */
        $customsRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\AbCustomProgram::class);
        /** @var AbCustomProgram $custom */
        $custom = $customsRep->find($customID);

        if (!$custom) {
            throw $this->createNotFoundException();
        }

        $_SESSION['RedirectBackURL'] =
            $this->router->generate('aw_booking_share_custom_to_account', [
                'id' => $abRequest->getAbRequestID(),
                'customID' => $custom->getAbCustomProgramID(),
                'providerID' => $custom->getProvider()->getProviderid(),
            ]);

        if ($this->authorizationChecker->isGranted("SITE_ND_SWITCH")) {
            return $this->redirect($this->router->generate("aw_account_add", [
                "providerId" => $custom->getProvider()->getProviderid(),
                "AbRequestID" => $abRequest->getAbRequestID(),
                "backTo" => $_SESSION['RedirectBackURL'],
            ]));
        }

        return $this->redirect("/account/edit.php?ProviderID=" . $custom->getProvider()->getProviderid() . "&ID=0&UserAgentID=0&skipping=1&AbRequestID=" . $abRequest->getAbRequestID());
    }
}
