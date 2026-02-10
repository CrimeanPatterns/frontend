<?php

namespace AwardWallet\MainBundle\Controller\User;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use AwardWallet\MainBundle\Service\AccountAccessApi\AuthStateManager;
use AwardWallet\MainBundle\Service\AccountAccessApi\BusinessFinder;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApproveBusinessConnectionController extends AbstractController
{
    private UseragentRepository $useragentRepository;
    private BusinessFinder $businessFinder;
    private AuthStateManager $authStateManager;

    public function __construct(
        UseragentRepository $useragentRepository,
        BusinessFinder $businessFinder,
        AuthStateManager $authStateManager
    ) {
        $this->useragentRepository = $useragentRepository;
        $this->businessFinder = $businessFinder;
        $this->authStateManager = $authStateManager;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/user/connections/approve", name="aw_api_connection", host="%host%", options={"expose"=true})
     * @return Response
     */
    public function apiConnectionAction(Request $request, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $id = $request->get('id');
        $access = $request->get('access', ACCESS_READ_ALL);
        $granularSharing = $request->get('granularSharing', false);

        if (strtolower($granularSharing) == 'false') {
            $granularSharing = false;
        }
        $granularSharing = (bool) $granularSharing;

        if (empty($id)) {
            $logger->info("id is empty");

            throw $this->createNotFoundException();
        }
        $business = $this->businessFinder->findByCode($id);

        if ($business === null) {
            $logger->info("business not found: $id");

            throw $this->createNotFoundException();
        }

        $authKey = $request->query->get("authKey") ?? '';
        $authState = $this->authStateManager->loadAuthState($business, $authKey, $access);

        if ($authState === null) {
            $logger->info("auth state not found: $authKey");

            throw $this->createNotFoundException();
        }

        $connection = $this->useragentRepository->findOneBy(['agentid' => $business->getUserid(), 'clientid' => $this->getUser()->getUserid()]);

        $access = intval($access);

        if (!in_array($access, [ACCESS_READ_NUMBER, ACCESS_READ_BALANCE_AND_STATUS, ACCESS_READ_ALL, ACCESS_WRITE])) {
            $access = ACCESS_READ_ALL;
        }

        $accessLevelsAll = $this->useragentRepository->getAgentAccessLevelsAll();
        $accessText = $translator->trans(/** @Ignore */ $accessLevelsAll[$access]);

        $formWithoutAccountsToEdit = $this->createFormBuilder()
            ->add('id', HiddenType::class, ['data' => $id])
            ->add('authKey', HiddenType::class, ['data' => $authKey])
            ->add('access', HiddenType::class, ['data' => $access])
            ->add('granularSharing', HiddenType::class, ['data' => $granularSharing])
            ->add('shareAccounts', HiddenType::class, ['data' => 0])
            ->add('toConnectionEdit', HiddenType::class, ['data' => 1])
            ->getForm();

        $formWithoutAccounts = $this->createFormBuilder()
            ->add('id', HiddenType::class, ['data' => $id])
            ->add('authKey', HiddenType::class, ['data' => $authKey])
            ->add('access', HiddenType::class, ['data' => $access])
            ->add('granularSharing', HiddenType::class, ['data' => $granularSharing])
            ->add('shareAccounts', HiddenType::class, ['data' => 0])
            ->add('toConnectionEdit', HiddenType::class, ['data' => 0])
            ->getForm();

        $formWithAccounts = $this->createFormBuilder()
            ->add('id', HiddenType::class, ['data' => $id])
            ->add('authKey', HiddenType::class, ['data' => $authKey])
            ->add('access', HiddenType::class, ['data' => $access])
            ->add('granularSharing', HiddenType::class, ['data' => $granularSharing])
            ->add('shareAccounts', HiddenType::class, ['data' => 1])
            ->add('toConnectionEdit', HiddenType::class, ['data' => 0])
            ->getForm();

        return $this->render('@AwardWalletMain/Share/apiConnection.html.twig', [
            'business' => $business,
            'access' => $access,
            'accessText' => $accessText,
            'granularSharing' => $granularSharing,
            'formWithoutAccountsToEdit' => $formWithoutAccountsToEdit->createView(),
            'formWithoutAccounts' => $formWithoutAccounts->createView(),
            'formWithAccounts' => $formWithAccounts->createView(),
            'denyUrl' => $this->authStateManager->getDenyUrl($business, $authState),
            'userAgent' => $connection,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/user/connections/approve/confirm", name="aw_api_connection_confirm", methods={"POST"}, host="%host%", options={"expose"=true})
     */
    public function apiConnectionConfirmAction(
        Request $request,
        AwTokenStorageInterface $tokenStorage,
        ProgramShareManager $programShareManager,
        RouterInterface $router
    ) {
        $form = $this->createFormBuilder()
            ->add('id', HiddenType::class)
            ->add('authKey', HiddenType::class)
            ->add('access', HiddenType::class)
            ->add('granularSharing', HiddenType::class)
            ->add('shareAccounts', HiddenType::class)
            ->add('toConnectionEdit', HiddenType::class)
            ->getForm();

        $form->handleRequest($request);
        $id = $form->get('id')->getData();
        $access = $form->get('access')->getData();
        $granularSharing = $form->get('granularSharing')->getData();
        $shareAccounts = $form->get('shareAccounts')->getData();
        $toConnectionEdit = $form->get('toConnectionEdit')->getData();
        $authKey = $form->get('authKey')->getData();

        $business = $this->businessFinder->findByCode($id);

        if ($business === null) {
            throw $this->createNotFoundException();
        }

        $authState = $this->authStateManager->loadAuthState($business, $authKey ?? '', $access);

        if ($authState === null) {
            throw $this->createNotFoundException();
        }

        $access = intval($access);

        if (!in_array($access, [ACCESS_READ_NUMBER, ACCESS_READ_BALANCE_AND_STATUS, ACCESS_READ_ALL, ACCESS_WRITE])) {
            $access = ACCESS_READ_ALL;
        }

        if (strtolower($granularSharing) == 'false') {
            $granularSharing = false;
        }
        $granularSharing = (bool) $granularSharing;

        // affects only $granularSharing==true
        $shareAccounts = (bool) $shareAccounts || !$granularSharing;

        $toConnectionEdit = (bool) $toConnectionEdit;

        if ($this->useragentRepository->findBy([
            'clientid' => $business->getUserid(),
            'agentid' => $tokenStorage->getBusinessUser()->getUserid(),
            'isapproved' => true,
            'accesslevel' => [
                UseragentRepository::ACCESS_ADMIN,
                UseragentRepository::ACCESS_BOOKING_MANAGER,
            ],
        ])) {
            throw $this->createNotFoundException();
        }

        $connection = $programShareManager->apiSharingConfirm(
            $business,
            $shareAccounts,
            $access
        );

        if ($toConnectionEdit) {
            return $this->redirect($router->generate('aw_user_connection_edit', ['userAgentId' => $connection->getUseragentid()]));
        } else {
            return $this->redirect($this->authStateManager->getSuccessUrl($business, $this->getUser(), $authState));
        }
    }
}
