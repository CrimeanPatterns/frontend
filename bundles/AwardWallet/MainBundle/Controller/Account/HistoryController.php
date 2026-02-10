<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\AccountHistoryType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountInfo\Info;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Loyalty\EmailApiHistoryParser;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\HistoryFormatterInterface;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryQuery;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryService;
use AwardWallet\MainBundle\Service\AccountHistory\NextPageToken;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

class HistoryController extends AbstractController
{
    public const PER_PAGE = 100;

    public const ACCOUNT_HISTORY_LINKS = [
        'delta' => 'https://www.delta.com/acctactvty/manageacctactvty.action',
        'mileageplus' => 'https://www.united.com/web/en-US/apps/mileageplus/statement/recentActivity.aspx',
        'rapidrewards' => 'https://www.southwest.com/myaccount/rapid-rewards/recent-activity/details?int=',
    ];

    /**
     * @var Usr
     */
    private $user;
    /**
     * @var Info
     */
    private $accountInfo;
    /**
     * @var HistoryService
     */
    private $historyService;
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var FormFactory
     */
    private $formFactory;
    /**
     * @var EmailApiHistoryParser
     */
    private $emailApiHistoryParser;

    private $emailCallbackPassword;
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var HistoryFormatterInterface
     */
    private $formatter;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        Info $accountInfo,
        HistoryService $historyService,
        HistoryFormatterInterface $formatter,
        EntityManager $em,
        RequestStack $requestStack,
        Router $router,
        FormFactory $formFactory,
        EmailApiHistoryParser $emailApiHistoryParser,
        $emailCallbackPassword,
        Environment $twig,
        SessionInterface $session
    ) {
        $this->user = $tokenStorage->getBusinessUser();
        $this->accountInfo = $accountInfo;
        $this->historyService = $historyService;
        $this->authorizationChecker = $authorizationChecker;
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->formFactory = $formFactory;
        $this->emailApiHistoryParser = $emailApiHistoryParser;
        $this->emailCallbackPassword = $emailCallbackPassword;
        $this->twig = $twig;
        $this->session = $session;
        LocalizeService::defineDateTimeFormat();
        $this->formatter = $formatter;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('READ_EXTPROP', account)")
     * @Route("/history/{accountId}/{subAccountId}", name="aw_subaccount_history_view", options={"expose"=true}, requirements={"accountId": "\d+", "subAccountId": "\d+"})
     * @Route("/history/{accountId}", name="aw_account_v2_history_view", options={"expose"=true}, requirements={"accountId": "\d+"})
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @ParamConverter("subAccount", class="AwardWalletMainBundle:Subaccount", options={"id" = "subAccountId"})
     * @Template("@AwardWalletMain/Account/History/v2view.html.twig")
     */
    public function v2viewAction(Request $request, Account $account, ?Subaccount $subAccount = null)
    {
        $query = (new HistoryQuery($account))
                 ->setFormatter($this->formatter);

        if ($subAccount instanceof Subaccount) {
            $query->setSubAccount($subAccount);
        }

        $awFree = $this->user->getAccountlevel() == ACCOUNT_LEVEL_FREE;

        $result = [
            'historyData' => !$awFree ? $this->historyService->getHistory($query) : [],
            'displayName' => $subAccount ? null : $account->getProviderid()->getDisplayname(),
            'accountNumber' => $account->getLogin(),
            'userName' => $account->getOwnerFullName(),
            'awFree' => $awFree,
            'providerCode' => $account->getProviderid()->getCode(),
            'autologinUrl' => $this->getAutologinUrl($account),
            'subAccountName' => $subAccount ?
                $this->getSubaccNameForHeader($subAccount->getId(), $account->getProviderid()->getProviderid()) :
                null,
            'exportUrl' => $subAccount instanceof Subaccount ?
                $this->router->generate('aw_subaccount_history_export', ['accountId' => $account->getAccountid(), 'subAccountId' => $subAccount->getSubaccountid()])
                :
                $this->router->generate('aw_account_history_export', ['accountId' => $account->getAccountid()]),
        ];

        if (Provider::AMEX_ID === $account->getProviderid()->getId()
            && !empty($account->getLogin2())
            && !in_array($account->getLogin2(), Account::LOGIN2_USA_VALUES, true)
        ) {
            $result['isNonUSAccount'] = true;
        }

        return $result;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('READ_EXTPROP', account)")
     * @Route("/history-data/{accountId}/{subAccountId}", name="aw_subaccount_history_data", options={"expose"=true}, requirements={"accountId": "\d+", "subAccountId": "\d+"})
     * @Route("/history-data/{accountId}", name="aw_account_history_data", options={"expose"=true}, requirements={"accountId": "\d+"})
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @ParamConverter("subAccount", class="AwardWalletMainBundle:Subaccount", options={"id" = "subAccountId"})
     * @return JsonResponse
     */
    public function dataAction(Request $request, Account $account, ?Subaccount $subAccount = null)
    {
        $params = array_merge($request->request->all(), $request->query->all());

        $nextPageToken = !empty($params['nextPage']) ? NextPageToken::createFromString($params['nextPage']) : null;
        $descriptionFilter = !empty($params['descriptionFilter']) ? $params['descriptionFilter'] : null;
        $query = (new HistoryQuery($account, $descriptionFilter, $nextPageToken))
                 ->setFormatter($this->formatter);

        if ($subAccount instanceof Subaccount) {
            $query->setSubAccount($subAccount)
                  ->setOfferCards(isset($params['offerFilterIds']) && is_array($params['offerFilterIds']) ? $params['offerFilterIds'] : null);
        }

        $history = $this->historyService->getHistory($query);

        return new JsonResponse($history);
    }

    /**
     * @Route("/history/edit/{uuid}", name="aw_account_history_edit", options={"expose":true})
     * @ParamConverter("history", class="AwardWalletMainBundle:AccountHistory", options={"uuid" = "uuid"})
     * @Security("is_granted('ROLE_USER')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editAction(AccountHistory $history, Request $request)
    {
        if (!$history || !$this->authorizationChecker->isGranted('EDIT', $history->getAccount())) {
            throw new AccessDeniedException();
        }
        $account = $history->getAccount();

        $form = $this->formFactory->create(AccountHistoryType::class, $history, ['account' => $account]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccountHistory $data */
            $data = $form->getData();
            $this->em->persist($data);
            $this->em->flush();
            $route = $data->getSubaccount() ?
                $this->router->generate('aw_subaccount_history_view', ['accountId' => $account->getAccountid(), 'subAccountId' => $data->getSubaccount()->getSubaccountid()])
                :
                $this->router->generate('aw_account_history_view', ['accountId' => $account->getAccountid()]);

            return new RedirectResponse($route);
        }

        return new Response($this->twig->render('@AwardWalletMain/Account/History/edit.html.twig', [
            'displayName' => "<span>{$account->getProviderid()->getDisplayname()}</span>",
            'form' => $form->createView(),
            'account' => $account,
            'custom' => $history->isCustom(),
        ]));
    }

    /**
     * @Route("/history/add/{accountId}", name="aw_account_history_add", requirements={"accountId": "\d+"})
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @Template("@AwardWalletMain/Account/History/add.html.twig")
     * @Security("is_granted('ROLE_USER')")
     */
    public function addAction(Account $account, Request $request)
    {
        if (!$this->authorizationChecker->isGranted('EDIT', $account)) {
            throw new AccessDeniedException();
        }

        $history = new AccountHistory();
        $history->setAccount($account);

        if ($subAccountId = $request->query->get('subAccountId')) {
            $subAccount = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class)->find($subAccountId);

            if (!$subAccount || $account->getAccountid() != $subAccount->getAccountid()->getAccountid()) {
                throw new AccessDeniedException();
            }
            $history->setSubaccount($subAccount);
        }

        $form = $this->formFactory->create(AccountHistoryType::class, $history, ['account' => $account]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccountHistory $data */
            $data = $form->getData();
            $data->setCustom(true);
            $this->em->persist($data);
            $this->em->flush();
            $route = $data->getSubaccount() ?
                $this->router->generate('aw_subaccount_history_view', ['accountId' => $account->getAccountid(), 'subAccountId' => $data->getSubaccount()->getSubaccountid()])
                :
                $this->router->generate('aw_account_history_view', ['accountId' => $account->getAccountid()]);

            return new RedirectResponse($route);
        }

        return [
            'displayName' => "<span>{$account->getProviderid()->getDisplayname()}</span>",
            'form' => $form->createView(),
            'account' => $account,
        ];
    }

    /**
     * @Route("/history/json/remove", name="aw_account_history_json_remove", options={"expose":true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function jsonRemoveAction(Request $request)
    {
        $ids = json_decode($this->requestStack->getCurrentRequest()->getContent());

        if (empty($ids)) {
            $ids = $request->get('ids');
        }

        if (empty($ids)) {
            throw new AccessDeniedException('Access Denied.');
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $em = $this->em;

        $historyItems = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AccountHistory::class)->findBy(['uuid' => $ids]);
        $removed = [];

        foreach ($historyItems as $history) {
            if ($this->authorizationChecker->isGranted('EDIT', $history->getAccount()) && $history->isCustom()) {
                $removed[] = $history->getUuid();
                $em->remove($history);
            }
        }
        $em->flush();

        return (new JsonResponse(['success' => true, 'removed' => $removed]))->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    /**
     * @Route("/history/json/note", name="aw_account_history_json_note", options={"expose":true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function jsonNoteAction(Request $request)
    {
        $data = json_decode($this->requestStack->getCurrentRequest()->getContent(), true);

        if (empty($data)) {
            throw new AccessDeniedException('Access Denied.');
        }

        if (!is_array($data) || !array_key_exists('ids', $data) || empty($data['ids'])) {
            throw new AccessDeniedException('Access Denied.');
        }
        $ids = $data['ids'];

        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $note = $data['note'];

        $historyItems = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AccountHistory::class)->findBy(['uuid' => $ids]);
        $changed = [];

        foreach ($historyItems as $history) {
            if ($this->authorizationChecker->isGranted('EDIT', $history->getAccount())) {
                $changed[] = $history->getUuid();
                $history->setNote($note);
            }
        }
        $this->em->flush();

        return (new JsonResponse(['success' => true, 'changed' => $changed]))->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    /**
     * @Route("/history/upload/{accountId}", name="aw_account_history_upload", methods={"POST"}, options={"expose":true})
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @return JsonResponse
     */
    public function uploadHistoryAction(Account $account, Request $request)
    {
        if (!$this->authorizationChecker->isGranted('EDIT', $account)) {
            throw new AccessDeniedException('Access Denied.');
        }

        if (!$this->emailApiHistoryParser->validateApiRequest($account, $request)) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse([
            'success' => $this->emailApiHistoryParser->sendParseEmailRequest($account, $request),
        ]);
    }

    /**
     * @Route("/history/callback", name="aw_account_history_callback", methods={"POST"}, options={"expose":false})
     * @ParamConverter(
     *     "apiResponse", class="AwardWallet\Common\API\Email\V2\ParseEmailResponse",
     *     converter="email_api.history_converter"
     * )
     * @return Response
     */
    public function callbackAction(?ParseEmailResponse $apiResponse = null, Request $request)
    {
        if ($request->getUser() !== 'awardwallet' || $request->getPassword() !== $this->emailCallbackPassword) {
            return new Response('Access denied', 403);
        }

        if (
            !$apiResponse
            || null === $apiResponse->loyaltyAccount
            || empty($apiResponse->loyaltyAccount->history)
            || !$this->emailApiHistoryParser->validateApiResponse($apiResponse)
        ) {
            return new Response('Malformed request');
        }

        $this->emailApiHistoryParser->saveApiResponse($apiResponse);

        return new Response('OK');
    }

    public function getAutologinUrl(Account $account)
    {
        $provider = $account->getProviderid();

        if (array_key_exists($provider->getCode(), self::ACCOUNT_HISTORY_LINKS)) {
            $link = self::ACCOUNT_HISTORY_LINKS[$provider->getCode()];
            $this->session->set("RedirectTo" . $account->getAccountid(), $link);

            return $this->router->generate("aw_account_redirect", ["ID" => $account->getAccountid(), "Goto" => $link]);
        }

        return null;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('READ_EXTPROP', account)")
     * @Route("/history/{accountId}/{subAccountId}/export.csv", name="aw_subaccount_history_export", options={"expose"=true}, requirements={"accountId": "\d+", "subAccountId": "\d+"})
     * @Route("/history/{accountId}/export.csv", name="aw_account_history_export", options={"expose"=true}, requirements={"accountId": "\d+"})
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @ParamConverter("subAccount", class="AwardWalletMainBundle:Subaccount", options={"id" = "subAccountId"})
     * @return Response
     */
    public function exportCsvAction(Account $account, ?Subaccount $subAccount = null)
    {
        if ('cli' !== PHP_SAPI) {
            ini_set('memory_limit', '3072M');
            set_time_limit(3072);
        }

        $query = (new HistoryQuery($account))
            ->setFormatter($this->formatter);

        if ($subAccount) {
            $query->setSubAccount($subAccount);
        }

        $response = new StreamedResponse();
        $response->setCallback(function () use ($query) {
            $csv = fopen('php://output', 'w+');

            try {
                foreach ($this->historyService->exportCsv($query) as $row) {
                    fputcsv($csv, $row);
                }
            } finally {
                fclose($csv);
            }
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');

        return $response;
    }

    //    /**
    //     * @Security("is_granted('ROLE_USER')")
    //     * @Route("/history/{accountId}", name="aw_account_history_view", options={"expose"=true}, requirements={"accountId": "\d+"})
    //     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
    //     * @Template()
    //     * @param Account $account
    //     * @param null $subAccountId
    //     * @return array
    //     */
    //	public function viewAction(Account $account, $subAccountId = null)
    //	{
    //		$user = $this->user;
    //
    //		if (!$this->authorizationChecker->isGranted('READ_EXTPROP', $account)) throw new AccessDeniedException('Access Denied.');
    //
    //		// custom account
    //		if (empty($account->getProviderid())) throw new AccessDeniedException('Access Denied.');
    //
    //		$form = $this->formFactory->create(Type\AccountType::class, $account);
    //		$history = $this->accountInfo->getAccountHistory($account, self::PER_PAGE, 0, true, $subAccountId);
    //
    //		$total = is_array($history) ? $history['total'] : 0;
    //
    //		return [
    //			'displayName' => preg_replace('/\((.*?)\)/', '<span>(\\1)</span>', $form->getConfig()->getAttribute('header')),
    //			'accountNumber' => $account->getLogin(),
    //			'userName' => $account->getOwnerFullName(),
    //			'accountId' => $account->getAccountid(),
    //            'providerCode' => $account->getProviderid()->getCode(),
    //            'subAccountId' => $subAccountId,
    //			'total' => $total,
    //			'perPage' => self::PER_PAGE,
    //			'historyData' => $history,
    //			'awFree'  => $user->getAccountlevel() == ACCOUNT_LEVEL_FREE,
    //            'user' => $account->getUserid(),
    //            'familyMember' => $account->getUseragentid() && $account->getUseragentid()->isFamilyMember() ? $account->getUseragentid() : null,
    //            'autologinUrl' => $this->getAutologinUrl($account),
    //            'subAccountName' => $this->getSubaccNameForHeader($subAccountId, $account->getProviderid()->getProviderid())
    //		];
    //	}

    private function getSubaccNameForHeader($subAccountId, $providerId)
    {
        if (!in_array($providerId, Provider::EARNING_POTENTIAL_LIST)) {
            return null;
        }

        $repo = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class);
        $subAcc = $repo->find($subAccountId);

        if (empty($subAcc) || empty($subAcc->getCreditcard())) {
            return null;
        }

        return $subAcc->getCreditCardFormattedDisplayName();
    }
}
