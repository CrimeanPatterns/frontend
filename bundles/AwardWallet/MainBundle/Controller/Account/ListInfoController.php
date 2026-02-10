<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Twig\AwTwigExtension;
use AwardWallet\MainBundle\Globals\AccountInfo\Info as AccountInfo;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\EliteLevelProgressDrawer;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Loyalty\EmailApiHistoryParser;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Repository\CreditCardRepository;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\DesktopHistoryFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryQuery;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryService;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListInfoController extends AbstractController
{
    private HistoryService $historyService;
    private DesktopHistoryFormatter $formatter;
    private AuthorizationCheckerInterface $authorizationChecker;
    private RouterInterface $router;
    private CreditCardMatcher $cardMatcher;
    private CreditCardRepository $cardRepository;
    private Connection $connection;
    private Logger $logger;
    private AwTokenStorageInterface $tokenStorage;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;
    private TranslatorInterface $translator;
    private AwTwigExtension $twigExtension;
    private EliteLevelProgressDrawer $progressDrawer;
    private AccountInfo $accountInfo;
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(
        HistoryService $historyService,
        DesktopHistoryFormatter $formatter,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        CreditCardMatcher $cardMatcher,
        CreditCardRepository $cardRepository,
        Connection $connection,
        Logger $logger,
        AwTokenStorageInterface $tokenStorage,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        TranslatorInterface $translator,
        AwTwigExtension $twigExtension,
        EliteLevelProgressDrawer $progressDrawer,
        AccountInfo $accountInfo,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->historyService = $historyService;
        $this->formatter = $formatter;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->cardMatcher = $cardMatcher;
        $this->cardRepository = $cardRepository;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
        $this->translator = $translator;
        $this->twigExtension = $twigExtension;
        $this->progressDrawer = $progressDrawer;
        $this->accountInfo = $accountInfo;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/info/{id}",
     *      name="aw_account_accountinfo",
     *      options={"expose"=true},
     *      requirements={"id" = "(a|c)\d+"}
     * )
     * @Route("/info/{id}/{sid}",
     *      name="aw_account_subaccountinfo",
     *      options={"expose"=true},
     *      requirements={"id" = "(a|c)\d+", "sid" = "\d+"}
     * )
     * @param null $sid
     * @return JsonResponse
     */
    public function accountInfoAction(
        $id,
        $sid = null
    ) {
        $isCoupon = strpos($id, 'c') !== false;
        $id = intval(substr($id, 1));
        $user = $this->tokenStorage->getBusinessUser();
        $listOptions = $this->optionsFactory
            ->createDesktopInfoOptions(
                (new Options())
                    ->set(Options::OPTION_USER, $user)
                    ->set(Options::OPTION_LOAD_CARD_IMAGES, true)
                    ->set(Options::OPTION_LOAD_BLOG_POSTS, true)
            );
        $info = ($isCoupon) ?
            $this->accountListManager->getCoupon($listOptions, $id) :
            $this->accountListManager->getAccount($listOptions, $id);
        $isCustomVaccineCard = $isCoupon && !empty($info['CustomFields']) && array_key_exists(Providercoupon::FIELD_KEY_VACCINE_CARD, $info['CustomFields']);
        $isCustomInsuranceCard = $isCoupon && !empty($info['CustomFields']) && array_key_exists(Providercoupon::FIELD_KEY_INSURANCE_CARD, $info['CustomFields']);
        $isVisa = $isCoupon && !empty($info['CustomFields']) && array_key_exists(Providercoupon::FIELD_KEY_VISA, $info['CustomFields']);
        $isDriversLicense = $isCoupon && !empty($info['CustomFields']) && array_key_exists(Providercoupon::FIELD_KEY_DRIVERS_LICENSE, $info['CustomFields']);

        if (!isset($info)) {
            throw $this->createNotFoundException();
        }

        // Access Control #
        if ($isCoupon && (!isset($info['Access']['read']) || !$info['Access']['read'])) {
            throw $this->createAccessDeniedException();
        } elseif (!$isCoupon && (!isset($info['Access']['read_extproperties']) || !$info['Access']['read_extproperties'])) {
            throw $this->createAccessDeniedException();
        }

        if (isset($sid) && (!isset($info['SubAccountsArray']) || !is_array($info['SubAccountsArray']))) {
            throw $this->createNotFoundException();
        }

        if (isset($sid)) {
            $subaccount = null;

            foreach ($info['SubAccountsArray'] as $sub) {
                if ($sub['SubAccountID'] == $sid) {
                    $subaccount = $sub;

                    break;
                }
            }

            if (!$subaccount) {
                throw $this->createNotFoundException();
            } else {
                unset($info['Balance'], $info['LastChangeDate'], $info['LastChangeDateTs'], $info['LastChangeDateFrendly'],
                    $info['ChangedPositive'], $info['LastChange'], $info['ChangedOverPeriodPositive'],
                    $info['SubAccountsArray'], $info['MileValue']
                );

                if (!isset($subaccount['Properties']) || !is_array($subaccount['Properties'])) {
                    $subaccount['Properties'] = [];
                }

                // TODO hardcoded list of properties, case #14505
                $mergePropertyList = ['Name', 'CustomerSupportPhone', 'NextAccountUpdate'];
                $origProps = $info['Properties'];
                $info = array_merge($info, $subaccount);

                foreach ($mergePropertyList as $name) {
                    if (isset($origProps[$name])) {
                        $info['Properties'][$name] = $origProps[$name];
                    }
                }
            }
        }

        if ($isCoupon) {
            $account = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class)->find($id);
            unset($info['Properties']['NextAccountUpdate']);

            $info['Description'] = $account->getDescription();
            $info['MainProperties']['LoginFieldLast'] = $info['LoginFieldLast'] = $info['Balance'];

            if (!empty($account->getPin()) && $this->authorizationChecker->isGranted('EDIT', $account)) {
                $info['Properties']['PIN'] = [
                    'Name' => $this->translator->trans('coupon.pin'),
                    'Code' => 'PIN',
                    'Val' => $account->getPin(),
                    'Visible' => 1,
                ];
            }

            if (!empty($info['Description'])) {
                $info['comment'] = $this->twigExtension->auto_link(nl2br(htmlspecialchars($info['Description'])));
                $info['Properties']['AccountComment'] = [
                    'Name' => $this->translator->trans('account.label.comment'),
                    'Code' => 'AccountComment',
                    'Val' => StringHandler::strLimit(htmlspecialchars($info['Description']), 35),
                    'Visible' => 1,
                ];
            }
        } else {
            $account = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($id);
        }

        if ($account instanceof Account && $account->getProviderid()) {
            // Elite level tab
            $this->progressDrawer->setUser($user);
            $info['EliteLevelTab'] = $this->progressDrawer->draw($id, $info, $sid);

            // Balance chart
            $info['BalanceChartTab'] = $this->accountInfo->getAccountBalanceChartQuery(
                $account->getAccountid(),
                15,
                isset($sid) ? (int) $sid : null
            );

            // Promotions
            $info['PromotionsTab'] = $this->accountInfo->getPromotions(
                $user,
                $account,
                8
            );

            // Credit cards
            $info['DetectedCreditCardTab'] = null;

            if (isset($info['MainProperties']['DetectedCards'])) {
                $info['DetectedCreditCardTab'] = $this->prepareDetectedCards(
                    $info['MainProperties']['DetectedCards']['DetectedCards'],
                    $account->getProviderid()->getProviderid(),
                    $account->getUser()->getId(),
                );
            }

            // History
            if ($account->getProviderid()->getCancheckhistory()) {
                $historyQuery = (new HistoryQuery($account))
                    ->setLimit(20)
                    ->setFormatter($this->formatter);

                if ($sid !== null) {
                    /** @var Subaccount $subAcc */
                    $subAcc = $this->getDoctrine()->getRepository(Subaccount::class)->find($sid);
                    $historyQuery->setSubAccount($subAcc);

                    $info['HistoryTab'] = $this->historyService->isHasHistory($historyQuery);
                } else {
                    $info['HistoryTab'] = $this->historyService->getHistory($historyQuery);
                }
            }

            $info['CanUploadHistory'] = $info['Access']['edit'] && array_key_exists($account->getProviderid()->getCode(), EmailApiHistoryParser::SUPPORTED_PROVIDERS);

            if (Provider::AMEX_ID === $account->getProviderid()->getId()
                && !empty($account->getLogin2())
                && !in_array($account->getLogin2(), Account::LOGIN2_USA_VALUES, true)
            ) {
                $info['isNonUSAccount'] = true;
            }
        }

        // Card images
        $info['CardImageTab'] = [
            'csrf' => $this->csrfTokenManager->getToken('cardImage')->getValue(), // set in the header(X-CSRF-TOKEN) when sending a file
            'isCreditCard' => PROVIDER_KIND_CREDITCARD === $info['Kind'] || ($account instanceof Account && $account->getProviderid() && PROVIDER_KIND_CREDITCARD === $account->getProviderid()->getKind()),
        ];

        $isCustom = isset($info['isCustom']) && $info['isCustom'];
        $info['HideProperties']
            = !$this->authorizationChecker->isGranted('USER_AWPLUS') && !$isCustom && !$isCoupon;
        $info['SubAccountDetails'] = $sid;

        if (array_key_exists('Properties', $info)) {
            foreach ($info['Properties'] as $propKey => $prop) {
                if (!empty($prop['ProviderPropertyID'])) {
                    $propName = $this->translator->trans('name.' . $prop['ProviderPropertyID'], [], 'providerproperty');

                    if ($propName !== 'name.' . $prop['ProviderPropertyID']) {
                        $info['Properties'][$propKey]['Name'] = $propName;
                    }
                    unset($info['Properties'][$propKey]['ProviderPropertyID']);
                }
            }
        }

        return (new JsonResponse($info))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function prepareDetectedCards(array $detectedCards, int $providerId, int $userId): array
    {
        $userCreditCards = $this->connection->fetchAllKeyValue(
            "select CreditCardID, LastSeenDate from UserCreditCard where UserID = :userId and IsClosed = 0",
            ["userId" => $userId]
        );

        return array_map(function (array $card) use ($providerId, $userCreditCards) {
            $cardId = $this->cardMatcher->identify($card['DisplayName'], $providerId);

            if ($cardId !== null) {
                /** @var CreditCard $cardEntity */
                $cardEntity = $this->cardRepository->find($cardId);

                $format = $cardEntity->getDisplayNameFormat();

                if ($format) {
                    $card['DisplayName'] = CreditCard::formatCreditCardName($card['DisplayName'], $format, $providerId);
                }

                $clickUrl = $cardEntity->getClickURL();

                if ($clickUrl !== null) {
                    $clickUrl = $this->addClickUrlParams($clickUrl);
                    $card['DisplayName'] = " <a href=\"{$clickUrl}\" target=\"_blank\">{$card['DisplayName']}</a>";
                }

                if (
                    $this->authorizationChecker->isGranted("ROLE_STAFF")
                    || $this->authorizationChecker->isGranted('USER_IMPERSONATED')
                    || $this->authorizationChecker->isGranted('USER_IMPERSONATED_AS_SUPER')
                ) {
                    $card['DisplayName'] .= " - <a href=\"" . $this->router->generate("credit_card_edit",
                        ['id' => $cardId]) . "\" target=\"_blank\">edit</a>, parsed: " . ($card['ParsedDisplayName'] ?? 'ERROR FOUND - ParsedDisplayName');

                    if (!array_key_exists('ParsedDisplayName', $card)) {
                        $this->logger->warning('ParsedDisplayName not found with DetectedCards');
                    }

                    if (isset($userCreditCards[$cardId])) {
                        $card['DisplayName'] .= " <span title='Found in UserCreditCard table, Last Seen: {$userCreditCards[$cardId]}'>✅</span>";
                    }
                }
            }

            return $card;
        }, $detectedCards);
    }

    private function addClickUrlParams(string $clickUrl): string
    {
        if (strpos($clickUrl, '?') === false) {
            $clickUrl .= "?";
        } else {
            $clickUrl .= "&";
        }

        return $clickUrl . "cid=detected-cards&mid=web&source=aw_app";
    }
}
