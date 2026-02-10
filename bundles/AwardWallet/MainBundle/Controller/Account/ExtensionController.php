<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\Common\Parsing\ParsingConstants;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ElitelevelRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Account\AnswerHelper;
use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\FrameworkExtension\Twig\AwTwigExtension;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Saver;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\Converters\PropertiesItinerariesConverter;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Security\Voter\SiteVoter;
use Aws\S3\S3Client;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ExtensionController extends AbstractController
{
    private ?Usr $user;

    private EntityManagerInterface $em;

    private AuthorizationCheckerInterface $checker;

    private AccountProcessor $accountProcessor;

    private LoggerInterface $logger;

    private AwTwigExtension $awTwigExtension;

    private Connection $db;

    private Saver $saver;

    public function __construct(
        EntityManagerInterface $em,
        AwTokenStorage $tokenStorage,
        AuthorizationCheckerInterface $checker,
        AccountProcessor $accountProcessor,
        LoggerInterface $logger,
        AwTwigExtension $awTwigExtension,
        Connection $db,
        Saver $saver
    ) {
        $this->user = $tokenStorage->getBusinessUser();
        $this->em = $em;
        $this->checker = $checker;
        $this->accountProcessor = $accountProcessor;
        $this->logger = $logger;
        $this->awTwigExtension = $awTwigExtension;
        $this->db = $db;
        $this->saver = $saver;
    }

    /**
     * @Route("/receive-by-confirmation", name="aw_account_extension_receive_by_confirmation", methods={"POST"}, options={"expose"=true})
     * @Route("/receiveByConfirmation.php", name="aw_account_extension_receive_by_confirmation_old", methods={"POST"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function receiveByConfirmation(
        Request $request,
        PropertiesItinerariesConverter $propertiesItinerariesConverter,
        ProviderRepository $providerRepository,
        AwTokenStorage $tokenStorage,
        UseragentRepository $useragentRepository,
        ItinerariesProcessor $itinerariesProcessor,
        RouterInterface $router
    ): JsonResponse {
        $owner = $this->createOwnerFromRequest($tokenStorage->getBusinessUser(), $request, $useragentRepository, $this->checker);

        if ($owner === null) {
            return new JsonResponse(["answer" => "error", "message" => "Access denied"]);
        }

        $providerCode = $request->request->get('providerCode');
        /** @var Provider $provider */
        $provider = $providerRepository->findOneBy(['code' => $providerCode]);

        if ($provider === null) {
            $this->logger->warning("missing provider: $providerCode");

            return new JsonResponse(["answer" => "error", "message" => "Internal error. Please try again later."]);
        }

        $fields = $this->extractConfFields($request, $providerCode);

        $props = $request->request->get('properties');

        if (!is_array($props)) {
            $this->logger->warning("invalid properties while expecting itineraries");
            $props = [];
        }
        $itineraries = $propertiesItinerariesConverter->extractItinerariesFromProperties($provider, $props);
        $options = SavingOptions::savingByConfirmationNumber($owner, $providerCode, $fields);
        $report = $itinerariesProcessor->save($itineraries, $options);

        $itIds = it(array_merge($report->getAdded(), $report->getUpdated()))
            ->map(function (Itinerary $itinerary) { return $itinerary->getIdString(); })
            ->toArray();

        if (count($itIds) === 0) {
            return new JsonResponse(['answer' => 'error', 'message' => 'We could not get any reservations by this number']);
        }

        $agentId = $request->request->getInt('clientId');

        return new JsonResponse([
            'answer' => 'ok',
            'redirectUrl' => $router->generate('aw_timeline')
                . 'itineraries/' . implode(',', $itIds)
                . ($agentId ? "?agentId={$agentId}" : ''),
        ]);
    }

    /**
     * @Route("/browser-check/{accountId}", name="aw_account_extension_browsercheck", methods={"POST"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id"="accountId"})
     * @throws \Doctrine\DBAL\DBALException
     */
    public function browserCheck(
        Request $request,
        Account $account,
        LoggerInterface $logger,
        LoggerInterface $securityLogger,
        LocalPasswordsManager $localPasswordsManager,
        AnswerHelper $answerHelper,
        ManagerRegistry $doctrine,
        SiteVoter $siteVoter
    ): JsonResponse {
        if (!$this->checker->isGranted('CLIENT_PASSWORD_ACCESS')) {
            $this->logger->warning("missing referer on password request");
        }

        if (!$this->checker->isGranted('USE_PASSWORD_IN_EXTENSION', $account)) {
            $error = "Access denied";

            if ($siteVoter->isImpersonationSandboxEscaped()) {
                $error = 'You are impersonated as <b>' . $this->user->getUsername() . '</b>. You can`t use this feature.<br>Request password access to <b>' . $account->getAccountid() . '</b> to check this account.';
            }

            return new JsonResponse(['error' => $error]);
        }

        $provider = $account->getProviderid();
        $response = [
            'receiveFromBrowser' => (CHECK_IN_MIXED == $provider->getCheckinbrowser()) || (!$provider->getCancheck() && $provider->getCancheckitinerary()),
        ];

        $this->db->update(
            'Usr',
            [
                'ExtensionVersion' => $request->query->get('Version'),
                'ExtensionBrowser' => substr($request->headers->get('User-Agent'), 0, 250),
                'ExtensionLastUseDate' => date('Y-m-d H:i:s'), ],
            ['UserID' => $this->user->getUserid()],
            [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR]
        );

        $allowReadPassword = $this->checker->isGranted('USE_PASSWORD_IN_EXTENSION', $account);

        if ($response['receiveFromBrowser'] || $allowReadPassword) {
            $accountRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Account::class);

            // refs#17733 disable clickUrl for HHonors Diamond members
            /** @var ElitelevelRepository $eliteLevelRep */
            $eliteLevelRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class);
            $eliteLevel = $eliteLevelRep->getEliteLevelFieldsByValue(
                $account->getProviderId()->getProviderid(),
                $account->getEliteLevel()
            );
            $skipCashbackUrl =
                ($provider->getProviderid() === 22) // Hilton
                && !empty($eliteLevel)
                && \in_array($eliteLevel['Rank'], [2, 3]); // [Gold, Diamond]

            $response = array_merge($response, [
                'login' => $account->getLogin(),
                'login2' => $account->getLogin2(),
                'login3' => $account->getLogin3(),
                'accountId' => '' . $account->getAccountid(),
                'providerCode' => $provider->getCode(),
                'skipCashbackUrl' => $skipCashbackUrl,
                'properties' => [],
                'balance' => $accountRep->formatFullBalance($account->getBalance(), $provider->getCode(), $provider->getBalanceformat(), false),
                'canUpdate' => $this->checker->isGranted('UPDATE', $account),
            ]);

            $props = $this->db->fetchAll('
                SELECT pp.Code, ap.Val
                FROM AccountProperty ap, ProviderProperty pp
                WHERE   ap.ProviderPropertyID = pp.ProviderPropertyID
				    AND ap.AccountID = ' . $account->getAccountid() . ' 
				    AND ap.SubAccountID IS NULL
                ORDER BY pp.SortIndex ASC
            ');

            foreach ($props as $prop) {
                $response['properties'][$prop['Code']] = $prop['Val'];
            }

            $historyValid = !empty($account->getHistoryVersion()) && $account->getHistoryVersion() == $provider->getCacheversion();

            if ($historyValid) {
                $response['historyStartDate'] = \AccountAuditorAbstract::getAccountHistoryLastDate($account->getAccountid());
            } else {
                $response['historyStartDate'] = 0;
            }

            if (in_array($account->getProviderid()->getProviderid(), Provider::EARNING_POTENTIAL_LIST)) {
                $historyRepo = $this->em->getRepository(AccountHistory::class);
                $response['subAccountHistoryStartDate'] = [];

                /** @var Subaccount $subAcc */
                foreach ($account->getSubAccountsEntities() as $subAcc) {
                    if ($historyValid) {
                        $response['subAccountHistoryStartDate'][$subAcc->getCode()] = $historyRepo->getLastHistoryRowDateBySubAccount($subAcc->getId());
                    }
                }
            }

            $request->getSession()->set('ExtensionAccountID', $account->getAccountid());

            if ($allowReadPassword) {
                if (SAVE_PASSWORD_LOCALLY === $account->getSavepassword()) {
                    $response['password'] = $localPasswordsManager->getPassword($account->getAccountid());

                    if (empty($response['password'])) {
                        $response['error'] = ACCOUNT_MISSING_PASSWORD_MESSAGE;
                        $response['requirePassword'] = true;
                    }
                } else {
                    $securityLogger->info("sent password for extension, accountId: {$account->getAccountid()}, userId: {$account->getUser()->getUserid()}");
                    $response['password'] = $account->getPass();
                }
            }

            if (26 === $provider->getProviderid()) {// #refs 13507 - United Airlines secret question  | +Account/AutologinController.php:L182
                $answers = $answerHelper->getAnswers($account, ['js' => true, 'questionAsKey' => true]);

                if (!empty($answers)) {
                    $response['answers'] = $answers;
                }
            }
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/receiveBrowserLog.php", methods={"POST"})
     * @Route("/receive-browser-log", methods={"POST"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function receiveBrowserLogAction(
        Request $request,
        ParsingConstants $parsingConstants, // do not remove, it's here for configuring \TAccountChecker::$logDir
        S3Client $s3Client
    ): Response {
        ini_set('memory_limit', '300M');

        if (strpos($request->headers->get('content-type'), 'application/json') !== false) {
            $requestData = JsonRequestHandler::parse($request);
            $accountId = (int) ($requestData['accountId'] ?? '');
            $isJson = true;
        } else {
            $accountId = $request->request->get('accountId', '');
            $isJson = false;
        }

        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);

        if ($account === null) {
            $this->logger->info("account {$accountId} not found, ignored, request keys: " . implode(", ", $request->request->keys()));

            return new Response("received.ignored.");  // logs from check by conf no
        }

        if (
            !$account
            || !($this->checker->isGranted('UPDATE', $account)
                || $this->checker->isGranted('AUTOLOGIN', $account))
        ) {
            $this->logger->info("access denied, ignored");

            throw new AccessDeniedException();
        }

        if ($isJson && isset($requestData)) {
            $log = $requestData['log'] ?? '';
        } else {
            $log = $request->request->get('log', '');
            $log = json_decode($log, true);
        }

        $responseLog = 'received.';
        $pass = $account->getPass();

        if (is_array($log)) {
            $responseLog .= 'valid.';
            $mainLog = "";
            $files = [];
            $isAutologin = false;
            $cntScreenshots = 0;

            foreach ($log as $line) {
                if (!isset($line['type']) || !isset($line['content'])) {
                    continue;
                }

                if (is_array($line['content']) || is_object($line['content'])) {
                    $line['content'] = json_encode($line['content']);
                }

                if (!empty($pass)) {
                    $line['content'] = str_replace($pass, 'PASSWORD', $line['content']);
                }

                if ($line['type'] == 'message') {
                    if (isset($line['time'])) {
                        $mainLog .= '[' . $line['time'] . "] ";
                    }
                    $mainLog .= $line['content'] . "<br/>";

                    if ($line['content'] === 'only autologin') {
                        $isAutologin = true;
                    }
                }

                if ($line['type'] == 'file') {
                    $key = "step" . sprintf("%02d", count($files) - $cntScreenshots);

                    if (!empty($line['step'])) {
                        $key .= "-" . $line['step'];
                    }

                    if (isset($line['screenshot'])) {
                        $cntScreenshots++;
                        [$type, $data] = explode(';', $line['screenshot']);
                        [, $data] = explode(',', $data);
                        $data = base64_decode($data);
                        $files[$key . ".png"] = $data;
                    }

                    $key .= ".html";
                    $files[$key] = $line['content'];
                }
            }

            $extMethod = $isAutologin ? 'autologin' : 'check';
            $file = \TAccountChecker::ArchiveLogsToZip($mainLog, "account-{$accountId}-{$extMethod}-" . time(), $files);

            $this->logger->info("uploading log to " . basename($file));
            $s3Client->upload('awardwallet-logs', basename($file), file_get_contents($file));
            unlink($file);

            $responseLog .= 'saved';
        }

        return new Response($responseLog);
    }

    private function createOwnerFromRequest(
        Usr $user,
        Request $request,
        UseragentRepository $useragentRepository
    ): ?Owner {
        $userAgent = null;
        $familyMember = null;
        $selectedUserId = $request->request->getInt('selectedUserId');

        $userAgentId = $request->request->getInt('clientId');
        $familyMemberId = $request->request->getInt('familyMemberId');

        if (!empty($userAgentId)) {
            /** @var Useragent $userAgent */
            $userAgent = $useragentRepository->findOneBy(['useragentid' => $userAgentId]);

            if ($userAgent === null) {
                $this->logger->warning("userAgent not found: $userAgentId");

                return null;
            }

            if (empty($familyMemberId)) {
                if ($userAgent->getClientid()->getUserid() !== $selectedUserId) {
                    $this->logger->warning("selectedUserId {$selectedUserId} mismatch with userAgent: $userAgentId");

                    return null;
                }
                $user = $userAgent->getClientid();
            } else {
                $user = $userAgent->getAgentid();
            }
        }

        if (!empty($familyMemberId)) {
            /** @var Useragent $familyMember */
            $familyMember = $useragentRepository->findOneBy(['useragentid' => $familyMemberId]);

            if ($familyMember === null) {
                $this->logger->warning("family member not found: $familyMemberId");

                return null;
            }

            if ($userAgent === null) {
                $this->logger->warning("family member without userAgent: $familyMemberId");

                return null;
            }
        }

        if ($userAgent === null && $familyMember === null) {
            if ($selectedUserId !== $user->getUserid()) {
                $this->logger->warning("invalid selectedUserId: $selectedUserId, while current user is: " . $user->getUserid());

                return null;
            }

            return new Owner($user);
        }

        /** @var Useragent $authAgent */
        if ($familyMember !== null) {
            $authAgent = $familyMember;
        } else {
            $authAgent = $userAgent;
        }

        if (!$this->checker->isGranted('EDIT_TIMELINE', $authAgent)) {
            $this->logger->warning("access denied to timeline of: " . $authAgent->getUseragentid());

            return null;
        }

        return new Owner($user, $familyMember);
    }

    private function extractConfFields(Request $request, string $providerCode): array
    {
        $result = [];
        $checker = GetAccountChecker($providerCode);
        $fields = array_keys($checker->GetConfirmationFields());

        foreach ($fields as $field) {
            if ($request->request->has($field)) {
                $result[$field] = $request->request->get($field);
            }
        }

        return $result;
    }
}
