<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Configuration\AwCache;
use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Configuration\Reauthentication;
use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Factory\AccountFactory;
use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Handler\Subscriber\Subscriber;
use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\Form\Type\Helpers\AttachProvidercouponToAccountHelper;
use AwardWallet\MainBundle\Form\Type\Helpers\DocumentHelper;
use AwardWallet\MainBundle\Form\Type\Mobile\Loyalty\DocumentType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TranslatorHijacker;
use AwardWallet\MainBundle\Globals\AccountInfo\Info;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\AccountManager;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Security\PasswordChecker;
use AwardWallet\MainBundle\Service\PopularityHandler;
use AwardWallet\MainBundle\Service\ProgramStatus\Finder;
use AwardWallet\MainBundle\Service\ProgramStatus\MobileAddAccountDescriptor;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use AwardWallet\MainBundle\Validator\Constraints\Cause\CauseAwareInterface;
use AwardWallet\MainBundle\Validator\Constraints\Cause\ExistingAccount;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\StoreLocationFinderTask;
use AwardWallet\MobileBundle\Form\Type;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AccountController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    public const MAX_BALANCE_CHART_ENTRIES = 30;

    public const DOCUMENT_ROUTE_TYPES = Providercoupon::KEY_TYPE_PASSPORT
        . '|' . Providercoupon::KEY_TYPE_TRAVELER_NUMBER
        . '|' . Providercoupon::KEY_TYPE_VACCINE_CARD
        . '|' . Providercoupon::KEY_TYPE_INSURANCE_CARD
        . '|' . Providercoupon::KEY_TYPE_VISA
        . '|' . Providercoupon::KEY_TYPE_DRIVERS_LICENSE
        . '|' . Providercoupon::KEY_TYPE_PRIORITY_PASS;
    private LocalizeService $localizeService;
    private AwTokenStorageInterface $awTokenStorage;

    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;
    private ApiVersioningService $apiVersioningService;
    private Desanitizer $desanitizer;
    private FormDehydrator $formDehydrator;
    private Info $accountInfo;

    public function __construct(
        LocalizeService $localizeService,
        AwTokenStorageInterface $awTokenStorage,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        ApiVersioningService $apiVersioningService,
        Desanitizer $desanitizer,
        FormDehydrator $formDehydrator,
        Info $accountInfo
    ) {
        $this->localizeService = $localizeService;
        $localizeService->setRegionalSettings();
        $this->awTokenStorage = $awTokenStorage;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
        $this->apiVersioningService = $apiVersioningService;
        $this->desanitizer = $desanitizer;
        $this->formDehydrator = $formDehydrator;
        $this->accountInfo = $accountInfo;
    }

    /**
     * @Route("/provider/{providerId}/{partialForm}",
     *      name="awm_newapp_account_add",
     *      methods={"GET", "POST"},
     *      requirements={"providerId" = "\d+|custom"},
     *      defaults = {
     *          "partialForm" = null
     *      }
     * )
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider", options={"id" = "providerId"})
     * @JsonDecode
     * @return JsonResponse
     */
    public function newAccountAction(
        Request $request,
        ?Provider $provider = null,
        $partialForm = null,
        Process $process,
        AccountFactory $accountFactory,
        AwTokenStorageInterface $awTokenStorage,
        TranslatorHijacker $translatorHijacker,
        Handler $awFormAccountHandlerMobile,
        LocalPasswordsManager $localPasswordsManager,
        ProviderTranslator $providerTranslator
    ) {
        if ($provider && !$this->isGranted('ADD', $provider)) {
            throw $this->createNotFoundException();
        }

        $translator = $translatorHijacker->setContext('mobile');

        $user = $awTokenStorage->getBusinessUser();
        $savePassword = isset($provider) && $provider->getPasswordrequired() ?
            $user->getSavepassword() :
            SAVE_PASSWORD_DATABASE;

        if (!empty($provider) && $provider->getCode() == 'aa') {
            $savePassword = SAVE_PASSWORD_LOCALLY;
        }

        $form = $this->getFormStub($provider);

        if (is_null($form)) {
            $form = $this->createForm(
                Type\AccountType::class,
                $accountFactory->create()
                    ->setProviderid($provider)
                    ->setUser($user)
                    ->setSavepassword($savePassword)
                    ->setDonttrackexpiration($this->apiVersioningService->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)),
                [
                    'provider' => $provider,
                    'method' => 'POST',
                ]
            );

            $formHandler = $awFormAccountHandlerMobile;
            $formHandler->addHandlerSubscriber(
                (new Subscriber())
                    ->setOnCommit(function (HandlerEvent $event) use ($process) {
                        $form = $event->getForm();
                        /** @var AccountModel $formModel */
                        $formModel = $form->getData();
                        $account = $formModel->getEntity();

                        if ($task = StoreLocationFinderTask::createFromLoyalty($account)) {
                            $process->execute($task);
                        }

                        $event->setResponse(new JsonResponse([
                            'needUpdate' => $this->isGranted('UPDATE', $account),
                            'account' => $this->loadMapper()->getAccount($account->getAccountid()),
                        ]));
                    })
                    ->setOnException(function () use ($localPasswordsManager) {
                        $localPasswordsManager->clear();
                    })
            );

            if ($response = $formHandler->handleRequestTransactionally($form, $request)) {
                return $response;
            }
        }

        return new JsonResponse(
            array_merge(
                $this->createMobileView(
                    $form,
                    $provider ?
                        $providerTranslator->translateDisplayNameByEntity($provider) :
                        $translator->trans(/** @Desc("%providerName% (%programName%)") */ 'custom.account.form.title', [
                            '%providerName%' => $translator->trans('custom.account.list.title', [], 'mobile'),
                            '%programName%' => $translator->trans('custom.account.list.notice', [], 'mobile'),
                        ], 'mobile'),
                    $provider ? $provider->getKind() : 'custom',
                    null,
                    $provider
                ),
                ['context' => ['providerId' => $provider ? $provider->getProviderid() : 'custom']]
            )
        );
    }

    /**
     * @Route("/account/{accountId}/{partialForm}",
     *      name="awm_newapp_account_edit",
     *      methods={"GET", "PUT"},
     *      requirements={"accountId" = "\d+"},
     *      defaults = {
     *          "partialForm" = null
     *      }
     * )
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @JsonDecode
     * @return JsonResponse
     */
    public function editAccountAction(
        Request $request,
        Account $account,
        $partialForm = null,
        TranslatorHijacker $translatorHijacker,
        Handler $awFormAccountHandlerMobile,
        AuthorizationCheckerInterface $authorizationChecker,
        LocalPasswordsManager $localPasswordsManager,
        ProviderTranslator $providerTranslator,
        LoggerInterface $logger
    ) {
        $translator = $translatorHijacker->setContext('mobile');

        if (!$authorizationChecker->isGranted('EDIT', $account)) {
            throw $this->createNotFoundException();
        }

        if (
            $authorizationChecker->isGranted('ROLE_STAFF')
            && in_array($account->getUser()->getId(), [986564, 35510])
            && $request->isMethod('PUT')
        ) {
            $logger->info(
                'mobile:editAccount:put_data',
                [
                    'request_string' => $request->getContent(),
                ]
            );
        }

        $provider = $account->getProviderid();

        $form = $this->getFormStub($provider);

        if (is_null($form)) {
            $form = $this->createForm(Type\AccountType::class, $account, [
                'provider' => $provider,
                'method' => 'PUT',
            ]);

            $awFormAccountHandlerMobile->addHandlerSubscriber(
                (new Subscriber())
                    ->setOnCommit(function (HandlerEvent $event) use ($account) {
                        $needUpdate = ($account->credentialsChanged || $account->disabledChanged)
                            && $this->isGranted('UPDATE', $account)
                            && !$account->isDisabled();

                        $event->setResponse(new JsonResponse([
                            'needUpdate' => $needUpdate,
                            'account' => $this->loadMapper()->getAccount($account->getAccountid()),
                        ]));
                    })
                    ->setOnException(function () use ($localPasswordsManager) {
                        $localPasswordsManager->clear();
                    })
            );

            if ($response = $awFormAccountHandlerMobile->handleRequestTransactionally($form, $request)) {
                return $response;
            }
        }

        return new JsonResponse(
            array_merge(
                $this->createMobileView(
                    $form,
                    $provider ?
                        $providerTranslator->translateDisplayNameByEntity($provider) :
                        $translator->trans(/** @Desc("%providerName% (%programName%)") */ 'custom.account.form.title', [
                            '%providerName%' => $translator->trans('custom.account.list.title', [], 'mobile'),
                            '%programName%' => $translator->trans('custom.account.list.notice', [], 'mobile'),
                        ], 'mobile'),
                    $provider ?
                        $provider->getKind() :
                        $account->getKind(),
                    $account->getLogin(),
                    $provider
                )
            )
        );
    }

    /**
     * @Route("/account/{accountId}",
     *      name="awm_newapp_account_remove",
     *      methods={"DELETE"},
     *      requirements={"accountId" = "\d+"}
     * )
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function deleteAccount(
        Request $request,
        Account $account,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        if (!$authorizationChecker->isGranted('DELETE', $account)) {
            throw $this->createNotFoundException();
        }

        $this->checkImpersonation($authorizationChecker);
        $this->checkCsrfToken($authorizationChecker);
        $entityManager->getRepository(Account::class)->deleteAccount($accountId = $account->getAccountid());

        return new JsonResponse([
            'accountId' => $accountId,
        ]);
    }

    /**
     * @Route("/discovered", name="awm_newapp_account_remove_discovered", methods={"DELETE"})
     */
    public function deleteDiscovered(
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $awTokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->checkImpersonation($authorizationChecker);
        $this->checkCsrfToken($authorizationChecker);

        /** @var Account[] $discoveredList */
        $discoveredList =
            $entityManager->getRepository(Account::class)
            ->getPendingsQuery($awTokenStorage->getBusinessUser())
            ->getQuery()
            ->execute();

        foreach ($discoveredList as $discovered) {
            $entityManager->getRepository(Account::class)->deleteAccount($discovered->getAccountid());
        }

        return $this->successJsonResponse();
    }

    /**
     * @Route("/provider/coupon/{partialForm}",
     *      name="awm_newapp_coupon_add",
     *      methods={"GET", "POST"},
     *      defaults = {
     *          "partialForm" = null
     *      }
     * )
     * @JsonDecode
     * @return JsonResponse
     */
    public function addCouponAction(
        Request $request,
        $partialForm = null,
        Process $process,
        AwTokenStorageInterface $awTokenStorage,
        TranslatorInterface $translator,
        Handler $awFormProviderCouponHandlerMobile
    ) {
        $form = $this->createForm(
            Type\ProviderCouponType::class,
            (new Entity\Providercoupon())
                ->setUser($awTokenStorage->getBusinessUser())
                ->setDonttrackexpiration($this->apiVersioningService->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)),
            ['method' => 'POST']
        );

        $awFormProviderCouponHandlerMobile->addHandlerSubscriber(
            (new Subscriber())
                ->setOnCommit(function (HandlerEvent $event) use ($process) {
                    $form = $event->getForm();
                    /** @var AccountModel $formModel */
                    $formModel = $form->getData();
                    /** @var Entity\Providercoupon $coupon */
                    $coupon = $formModel->getEntity();

                    if ($task = StoreLocationFinderTask::createFromLoyalty($coupon)) {
                        $process->execute($task);
                    }

                    $event->setResponse(new JsonResponse([
                        'account' => $this->loadMapper()->getCoupon($coupon->getProvidercouponid()),
                    ]));
                })
        );

        if ($response = $awFormProviderCouponHandlerMobile->handleRequestTransactionally($form, $request)) {
            return $response;
        }

        return new JsonResponse(
            array_merge(
                $this->createMobileView(
                    $form,
                    $translator->trans('custom.account.form.title',
                        [
                            '%providerName%' => $translator->trans('vouchers.gift.card.list.title', [], 'mobile'),
                            '%programName%' => $translator->trans('custom.account.list.notice', [], 'mobile'),
                        ],
                        'mobile'
                    ),
                    'coupon'
                ),
                ['context' => ['providerId' => 'custom']]
            )
        );
    }

    /**
     * @Route("/coupon/{couponId}/{partialForm}",
     *      name = "awm_newapp_coupon_edit",
     *      methods={"GET", "PUT"},
     *      requirements = {"couponId" = "\d+"},
     *      defaults = {
     *          "partialForm" = null
     *      }
     * )
     * @JsonDecode
     * @ParamConverter("providercoupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     * @return JsonResponse
     */
    public function editCouponAction(
        Request $request,
        Entity\Providercoupon $providercoupon,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        Handler $awFormProviderCouponHandlerMobile,
        $partialForm = null
    ) {
        if ($providercoupon->getKind() === PROVIDER_KIND_DOCUMENT) {
            throw $this->createNotFoundException();
        }

        if (!$authorizationChecker->isGranted('EDIT', $providercoupon)) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(Type\ProviderCouponType::class, $providercoupon, ['method' => 'PUT']);

        $awFormProviderCouponHandlerMobile->addHandlerSubscriber(
            (new Subscriber())
                ->setOnCommit(function (HandlerEvent $event) use ($providercoupon) {
                    $event->setResponse(new JsonResponse([
                        'account' => $this->loadMapper()->getCoupon($providercoupon->getProvidercouponid()),
                    ]));
                })
        );

        if ($response = $awFormProviderCouponHandlerMobile->handleRequestTransactionally($form, $request)) {
            return $response;
        }

        return new JsonResponse(
            array_merge(
                $this->createMobileView(
                    $form,
                    $translator->trans(
                        'custom.account.form.title',
                        [
                            '%providerName%' => $translator->trans('vouchers.gift.card.list.title', [], 'mobile'),
                            '%programName%' => $translator->trans('custom.account.list.notice', [], 'mobile'),
                        ],
                        'mobile'
                    ),
                    'coupon'
                ),
                ['context' => ['providerId' => 'custom']]
            )
        );
    }

    /**
     * @Route("/coupon/{couponId}",
     *      name = "awm_newapp_coupon_remove",
     *      methods={"DELETE"},
     *      requirements = {"couponId" = "\d+"}
     * )
     * @ParamConverter("coupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     */
    public function deleteCoupon(
        Request $request,
        Entity\Providercoupon $coupon,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $entityManager
    ) {
        if (!$authorizationChecker->isGranted('DELETE', $coupon)) {
            throw $this->createNotFoundException();
        }

        $this->checkImpersonation($authorizationChecker);
        $this->checkCsrfToken($authorizationChecker);
        $id = $coupon->getProvidercouponid();

        $entityManager->remove($coupon);
        $entityManager->flush();

        return new JsonResponse([
            'couponId' => $id,
        ]);
    }

    /**
     * @Route("/provider/{type}/{partialForm}",
     *      name="awm_document_add",
     *      methods={"GET", "POST"},
     *      requirements = {
     *          "type"=AccountController::DOCUMENT_ROUTE_TYPES
     *      },
     *      defaults = {
     *          "partialForm" = null
     *      }
     * )
     * @JsonDecode
     * @return JsonResponse
     */
    public function addDocumentAction(
        Request $request,
        string $type,
        $partialForm = null,
        AwTokenStorageInterface $awTokenStorage,
        TranslatorInterface $translator,
        Handler $awFormDocumentHandlerMobile,
        DocumentHelper $documentTypeHelper
    ) {
        $document = (new Entity\Providercoupon())
            ->setKind(PROVIDER_KIND_DOCUMENT)
            ->setTypeid(
                (function () use ($type): int {
                    switch ($type) {
                        case Providercoupon::KEY_TYPE_PASSPORT:
                            return Entity\Providercoupon::TYPE_PASSPORT;

                        case Providercoupon::KEY_TYPE_TRAVELER_NUMBER:
                            return Entity\Providercoupon::TYPE_TRUSTED_TRAVELER;

                        case Providercoupon::KEY_TYPE_VACCINE_CARD:
                            return Entity\Providercoupon::TYPE_VACCINE_CARD;

                        case Providercoupon::KEY_TYPE_INSURANCE_CARD:
                            return Entity\Providercoupon::TYPE_INSURANCE_CARD;

                        case Providercoupon::KEY_TYPE_VISA:
                            return Entity\Providercoupon::TYPE_VISA;

                        case Providercoupon::KEY_TYPE_DRIVERS_LICENSE:
                            return Entity\Providercoupon::TYPE_DRIVERS_LICENSE;

                        case Providercoupon::KEY_TYPE_PRIORITY_PASS:
                            return Entity\Providercoupon::TYPE_PRIORITY_PASS;

                        default:
                            throw new \InvalidArgumentException('Invalid type');
                    }
                })()
            )
            ->setUser($awTokenStorage->getBusinessUser());

        $form = $this->createForm(
            DocumentType::class,
            $document,
            ['method' => 'POST']
        );

        $awFormDocumentHandlerMobile->addHandlerSubscriber(
            (new Subscriber())
                ->setOnCommit(function (HandlerEvent $event) {
                    $form = $event->getForm();
                    /** @var AccountModel $formModel */
                    $formModel = $form->getData();
                    /** @var Entity\Providercoupon $coupon */
                    $coupon = $formModel->getEntity();

                    $event->setResponse(new JsonResponse([
                        'account' => $this->loadMapper()->getCoupon($coupon->getProvidercouponid()),
                    ]));
                })
        );

        if ($response = $awFormDocumentHandlerMobile->handleRequestTransactionally($form, $request)) {
            return $response;
        }

        $formTitle = $documentTypeHelper->getTranslatedDocumentTitle($document);

        return new JsonResponse(
            array_merge(
                $this->createMobileView(
                    $form,
                    $formTitle,
                    $type
                ),
                ['context' => ['providerId' => $type]]
            )
        );
    }

    /**
     * @Route("/document/{couponId}/{partialForm}",
     *      name = "awm_document_edit",
     *      methods={"GET", "PUT"},
     *      requirements = {"couponId" = "\d+"},
     *      defaults = {
     *          "partialForm" = null
     *      }
     * )
     * @JsonDecode
     * @ParamConverter("providercoupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     * @return JsonResponse
     */
    public function editDocumentAction(
        Request $request,
        Entity\Providercoupon $providercoupon,
        $partialForm = null,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        Handler $awFormDocumentHandlerMobile,
        DocumentHelper $documentTypeHelper
    ) {
        if ($providercoupon->getKind() !== PROVIDER_KIND_DOCUMENT) {
            throw $this->createNotFoundException();
        }

        if (!$authorizationChecker->isGranted('EDIT', $providercoupon)) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(DocumentType::class, $providercoupon, ['method' => 'PUT']);

        $awFormDocumentHandlerMobile->addHandlerSubscriber(
            (new Subscriber())
                ->setOnCommit(function (HandlerEvent $event) use ($providercoupon) {
                    $event->setResponse(new JsonResponse([
                        'account' => $this->loadMapper()->getCoupon($providercoupon->getProvidercouponid()),
                    ]));
                })
        );

        if ($response = $awFormDocumentHandlerMobile->handleRequestTransactionally($form, $request)) {
            return $response;
        }

        $formKind = Providercoupon::DOCUMENT_TYPE_TO_KEY_MAP[$providercoupon->getTypeid()] ?? null;
        $formTitle = $documentTypeHelper->getTranslatedDocumentTitle($providercoupon);

        return new JsonResponse(
            array_merge(
                $this->createMobileView(
                    $form,
                    $formTitle,
                    $formKind
                ),
                ['context' => ['providerId' => 'custom']]
            )
        );
    }

    /**
     * @Route("/providers/{kind}/{scope}",
     *      name="awm_newapp_provider_find",
     *      methods={"GET", "POST"},
     *      requirements = {
     *          "kind" = "(popular|\d+)",
     *          "scope" = "all"
     *      }
     * )
     * @AwCache(expires="+15 minutes", maxage="900", etagContentHash="sha256")
     * @param $kind provider kind
     * @return JsonResponse
     */
    public function findProgramToAddByKind(
        Request $request,
        $kind = null,
        $scope = null,
        TranslatorInterface $translator,
        PopularityHandler $popularityHandler,
        AuthorizationCheckerInterface $authorizationChecker,
        ApiVersioningService $apiVersioningService,
        Finder $finder,
        MobileAddAccountDescriptor $mobileAddAccountDescriptor,
        Desanitizer $desanitizer
    ) {
        $providerRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);

        /** @var Connection $connection */
        $connection = $this->getDoctrine()->getConnection();

        if ('POST' === $request->getMethod()) {
            $data = JsonRequestHandler::parse($request);

            if (
                !isset($data['queryString'])
                || ('' === trim($searchString = $data['queryString']))
                || (strlen($searchString) > 60)
            ) {
                throw $this->createNotFoundException("Missing 'queryString' field");
            }

            $searchStringGlob = $connection->quote('%' . addcslashes($searchString, "%_") . '%', \PDO::PARAM_STR);
            $searchString = preg_quote($searchString, '/');
            $searchStringRegexKeywords = $connection->quote("(^\s*{$searchString}\s+,|,\s*{$searchString}\s*,|,\s*{$searchString}$|^\s*{$searchString}\s*$)");

            if (isset($data['scope'])) {
                $scope = $data['scope'];
            }
        }

        $user = $this->getCurrentUser();
        $filter = '';

        //        $fields = "
        //            p.ProviderID,
        //            p.ProgramName,
        //            p.DisplayName,
        //            p.Name,
        //            p.Kind";
        //
        //        $order = "ORDER BY Name, Corporate";
        if (isset($kind)) {
            //            if (!isset($searchString)) {
            //                $order = "
            //                    ORDER BY
            //                        IF(p.ProviderID IN (7,16,26), 1, 0) ASC,
            //                        Accounts DESC";
            //            }
            //
            if ('popular' !== $kind) {
                $filter = ' AND p.Kind = ' . $connection->quote($kind, \PDO::PARAM_INT);
            }
        }

        if (isset($searchStringGlob, $searchStringRegexKeywords)) {
            $filter .= "
                AND (
                    (
                        p.Name COLLATE UTF8_GENERAL_CI LIKE {$searchStringGlob} OR
                        p.DisplayName COLLATE UTF8_GENERAL_CI LIKE {$searchStringGlob} OR
                        p.ProgramName COLLATE UTF8_GENERAL_CI LIKE {$searchStringGlob} OR
                        p.Code COLLATE UTF8_GENERAL_CI LIKE {$searchStringGlob}
                    ) OR
                    p.KeyWords COLLATE UTF8_GENERAL_CI REGEXP {$searchStringRegexKeywords}
                )";
        }

        $providers = $popularityHandler->getPopularPrograms(
            $user,
            $filter,
            "ORDER BY Popularity DESC, OldPopularity DESC, p.Accounts DESC",
            ($scope === 'all') ?
                '((' . $user->getProviderFilter('p.State') . ') OR p.State = ' . PROVIDER_RETAIL . ')' :
                null,
            true
        );

        if (empty($providers) && $authorizationChecker->isGranted('SITE_MOBILE_APP')) {
            if (
                isset($data['queryString'])
                && $request->isMethod('POST')
                && $apiVersioningService->supports(MobileVersions::ADD_ACCOUNT_WITH_STATUS_PROVIDER)
            ) {
                $result = $finder->findProviders(
                    $data['queryString'],
                    $mobileAddAccountDescriptor,
                    $user
                );
                $trim = fn (string $msg) => \trim(
                    \preg_replace(
                        '/\s+(<\/?(?!b|big|i|small|tt|abbr|acronym|cite|code|dfn|em|kbd|strong|samp|var|a|bdo|br|img|map|object|q|span|sub|sup|button|input|label|select|textarea)(?=\w+)[^>]*>)\s+/',
                        '$1',
                        \preg_replace(['/\R+/', '/\s{2,}/'], ' ', $msg)
                    )
                );

                if (count($result) === 0) {
                    return new JsonResponse([
                        'success' => false,
                        'details' => $this->checkEncoding(
                            $trim(
                                $this->renderView('@AwardWalletMain/ContactUs/programSearchNotFound.mobile.html.twig', [
                                    'name' => $data['queryString'],
                                ])
                            )
                        ),
                    ]);
                }

                return new JsonResponse([
                    'success' => true,
                    'details' => $this->checkEncoding(
                        $trim(
                            $this->renderView('@AwardWalletMain/ContactUs/programSearchResult.mobile.html.twig', [
                                'programs' => $result,
                            ])
                        )
                    ),
                ]);
            }

            return new JsonResponse([
                'error' => $translator->trans('award.account.list.search.not-found'),
            ]);
        }

        foreach ($providers as $key => $provider) {
            $providers[$key] = $desanitizer->tryDesanitizeArray($provider, [
                'ProgramName',
                'DisplayName',
                'Name',
            ]);
        }

        $result = [
            'providers' => $providers,
        ];

        if (isset($data['queryString'])) {
            $result['queryString'] = $data['queryString'];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/providers/completion/{withAccounts}",
     *     name="awm_newapp_provider_completion",
     *     methods={"POST"},
     *     requirements={
     *         "withAccounts" = "[10]"
     *     },
     *     defaults={
     *         "withAccounts" = 0
     *     }
     * )
     * @AwCache(expires="+15 minutes", maxage="900", etagContentHash="sha256")
     * @JsonDecode
     * @return JsonResponse
     */
    public function findProgramName(
        Request $request,
        int $withAccounts,
        EntityManagerInterface $entityManager,
        AttachProvidercouponToAccountHelper $awFormAttachProvidercouponToAccountHelper,
        Desanitizer $desanitizer
    ) {
        $queryString = $request->request->get('queryString');

        if (StringHandler::isEmpty($queryString)) {
            return $this->jsonResponse([
                'queryString' => (string) $queryString,
                'completions' => [],
            ]);
        }

        $requestFields = $request->query->get('requestFields');
        $fields = empty($requestFields) ? null : array_map('trim', explode(',', $requestFields));

        if (!empty($fields)) {
            if (in_array('currency', $fields)) {
                $currency = $this->getDoctrine()->getConnection()->fetchAll('SELECT CurrencyID, Name FROM Currency');
                $currencyPair = [];

                foreach ($currency as $item) {
                    $currencyPair[$item['CurrencyID']] = $item['Name'];
                }
            }
        }

        $completions = [];
        $providers = $entityManager->getRepository(Provider::class)
            ->findProviderByText($queryString, 'ASC', 7);

        /** @var Account[][] $accounts */
        $accounts = [];

        if ($withAccounts && $providers) {
            $accounts = $entityManager->getRepository(Account::class)->getPossibleAccountsForPossibleOwnersByProviders(
                array_column($providers, 'ProviderID'),
                $this->getCurrentUser(),
                true
            );
        }

        foreach ($providers as $provider) {
            $attachAccounts = [];

            foreach (
                $accounts[$provider['ProviderID']] ??
                $accounts[$provider['DisplayName']] ?? [] as $ownerId => $accountsPerOwner
            ) {
                /** @var Account $account */
                foreach ($accountsPerOwner as $account) {
                    $attachAccounts[$ownerId][] = [
                        'value' => $account->getAccountid(),
                        'label' => $awFormAttachProvidercouponToAccountHelper->getAccountLabel($account),
                    ];
                }
            }

            $displayName = $desanitizer->tryDesanitizeChars($provider['DisplayName']);

            $res = [
                'value' => $displayName,
                'label' => $displayName,
                'provider' => (int) $provider['ProviderID'],
                'kind' => (string) $provider['Kind'],
                'additionalData' => [
                    'attachAccounts' => $attachAccounts,
                ],
            ];

            if (!empty($fields)) {
                if (in_array('currency', $fields) && !empty($provider['Currency'])) {
                    $res['additionalData']['currency'] = array_key_exists($provider['Currency'], $currencyPair) ? $currencyPair[$provider['Currency']] : '';
                }
            }

            $completions[] = $res;
        }

        return $this->jsonResponse([
            'queryString' => $queryString,
            'completions' => $completions,
        ]);
    }

    /**
     * @Route("/documents/disease", name="awm_disease_completion", methods={"POST"})
     * @AwCache(expires="+15 minutes", maxage="900", etagContentHash="sha256")
     * @JsonDecode
     */
    public function findDiseaseAction(Request $request): JsonResponse
    {
        $queryString = $request->request->get('queryString');
        $queryStringNorm = \mb_strtolower($queryString);

        return $this->json([
            'queryString' => $queryString,
            'completions' =>
                it(DocumentHelper::getDiseaseList())
                ->filter(fn (string $disease) => \mb_strstr(\mb_strtolower($disease), $queryStringNorm) !== false)
                ->map(fn (string $disease) => [
                    'value' => $disease,
                    'label' => $disease,
                ])
                ->toArray(),
        ]);
    }

    /**
     * @Route("/coupon/types", name="awm_coupon_types_completion", methods={"POST"})
     * @AwCache(expires="+15 minutes", maxage="900", etagContentHash="sha256")
     * @JsonDecode
     */
    public function couponTypesAction(Request $request): JsonResponse
    {
        $queryString = $request->request->get('queryString');
        $queryStringNorm = \mb_strtolower($queryString);

        return $this->json([
            'queryString' => $queryString,
            'completions' =>
                it(Providercoupon::TYPES)
                    ->filter(fn (string $typeName) => empty($queryString)
                        || mb_strstr(mb_strtolower($typeName), $queryStringNorm) !== false)
                    ->map(fn (string $typeName) => [
                        'value' => $typeName,
                        'label' => $typeName,
                    ])
                    ->toArray(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/account/localpassword/{accountId}",
     * 		name="awm_newapp_account_localpassword",
     *      methods={"GET", "POST"},
     * 		requirements={
     *			"accountId" = "\d+"
     * 		}
     * )
     * @JsonDecode
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function localPasswordAction(
        Request $request,
        Account $account,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorInterface $translator,
        Desanitizer $desanitizer,
        ProviderTranslator $providerTranslator,
        LocalPasswordsManager $localPasswordsManager,
        FormDehydrator $formDehydrator
    ) {
        if (!$this->isGranted('EDIT', $account)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(Type\LocalPasswordType::class, null, [
            'label' => $translator->trans(
                /** @Desc("Enter password for account ""%account-login%"" (%account-owner-username%)") */
                'error.award.account.missing-password.prompt',
                [
                    '%account-login%' => $account->getLogin(),
                    '%account-owner-username%' => $account->getUserid()->getUsername(),
                ],
                'mobile'
            ),
        ]);
        $request->request->replace([$form->getName() => $request->request->all()]);

        $result = [
            'accountId' => $account->getAccountid(),
            'DisplayName' => $desanitizer->tryDesanitizeChars(
                $providerTranslator->translateDisplayNameByEntity($account->getProviderid())
            ),
        ];

        if ($request->isMethod('POST')) {
            $this->checkImpersonation($authorizationChecker);
            $this->checkCsrfToken($authorizationChecker);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $localPasswordManager = $localPasswordsManager;
                $localPasswordManager->setPassword($account->getAccountid(), $form['password']->getData());
                $result['success'] = true;

                return new JsonResponse($result);
            }
        }

        return new JsonResponse(
            array_merge(
                $result,
                ['formData' => $formDehydrator->dehydrateForm($form)]
            )
        );
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/account/question/{accountId}",
     *      name="awm_newapp_account_security_answer",
     *      methods={"GET", "POST"},
     *      requirements={
     *          "accountId": "\d+"
     *      }
     * )
     * @JsonDecode
     * @return JsonResponse
     */
    public function securityQuestionAction(
        Request $request,
        $accountId,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorHijacker $translatorHijacker,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        Desanitizer $desanitizer,
        ProviderTranslator $providerTranslator,
        AccountManager $accountManager,
        FormDehydrator $formDehydrator
    ) {
        $translatorHijacker->setContext('mobile');
        $account = $accountListManager
            ->getAccount(
                $optionsFactory
                    ->createDefaultOptions()
                    ->set(Options::OPTION_LOAD_PHONES, Options::VALUE_PHONES_FULL),
                $accountId
            );

        if (!$account) {
            throw $this->createNotFoundException('Account not found');
        }

        // Form
        $pattern = [
            'answer' => '',
            'question' => $account['Question'],
        ];

        /** @var \Symfony\Component\Form\Form */
        $form = $this->createForm(Type\AnswerQuestionType::class, $pattern);
        $request->request->replace([$form->getName() => $request->request->all()]);

        $result = [
            'accountId' => $accountId,
            'DisplayName' => $desanitizer->tryDesanitizeChars(
                $providerTranslator->translateDisplayNameByScalars((int) $account['ProviderID'], $account['DisplayName'])
            ),
        ];

        if ($request->isMethod('POST')) {
            $this->checkImpersonation($authorizationChecker);
            $this->checkCsrfToken($authorizationChecker);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();

                if (!empty($formData['question']) && !empty($formData['answer'])) {
                    $accountEnt = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);

                    if (!$accountEnt) {
                        throw $this->createNotFoundException('Account not found');
                    }

                    if ($accountManager->answerSecurityQuestion($accountEnt, $formData['question'], $formData['answer'])) {
                        $result['success'] = true;
                    } else {
                        $result['error'] = 'No access';
                    }

                    return new JsonResponse($result);
                } else {
                    $result['error'] = 'Empty form data';
                }
            } else {
                $result['error'] = 'Invalid form data';
            }
        }

        return new JsonResponse(
            array_merge(
                $result,
                ['formData' => $formDehydrator->dehydrateForm($form)]
            )
        );
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/account/balance-chart/{accountId}",
     *     name="awm_account_balance_chart",
     *     requirements={
     *         "accountId": "\d+"
     *     }
     * )
     * @JsonDecode
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function balanceChartAccountAction(Request $request, Account $account)
    {
        if (!$this->isGranted('READ_BALANCE', $account)) {
            throw $this->createNotFoundException();
        }

        $limit = $request->get('limit', 0);

        if (!\is_int($limit)) {
            $limit = self::MAX_BALANCE_CHART_ENTRIES;
        }

        $limit = max(self::MAX_BALANCE_CHART_ENTRIES, $limit);

        return $this->jsonResponse($this->getBalanceChartData($account, null, $limit));
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/account/balance-chart/{accountId}/{subaccountId}",
     *     name="awm_subaccount_balance_chart",
     *     requirements={
     *         "accountId": "\d+",
     *         "subaccountId": "\d+"
     *     }
     * )
     * @JsonDecode
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @ParamConverter("subaccount", class="AwardWalletMainBundle:Subaccount", options={"id" = "subaccountId"})
     */
    public function balanceChartSubaccountAction(Request $request, Account $account, Entity\Subaccount $subaccount)
    {
        if (!$this->isGranted('READ_BALANCE', $account)) {
            throw $this->createNotFoundException();
        }

        if ($subaccount->getAccountid()->getAccountid() !== $account->getAccountid()) {
            throw $this->createNotFoundException();
        }

        $limit = $request->get('limit', 0);

        if (!\is_int($limit)) {
            $limit = self::MAX_BALANCE_CHART_ENTRIES;
        }

        $limit = max(self::MAX_BALANCE_CHART_ENTRIES, $limit);

        return $this->jsonResponse($this->getBalanceChartData($account, $subaccount, $limit));
    }

    /**
     * @Route("/account/autologin-enable/{accountId}", name="awm_account_autologin_enable", options={"expose"=true}, methods={"POST"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('READ_PASSWORD', account)")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id"="accountId"})
     * @Reauthentication(checkDeviceSupport=true)
     * @JsonDecode
     */
    public function autologinEnable(
        Request $request,
        Account $account,
        PasswordChecker $passwordChecker,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ApiVersioningService $apiVersioning
    ) {
        if ($apiVersioning->notSupports(MobileVersions::LOGIN_OAUTH)) {
            $password = $request->get('password');

            if (!$passwordChecker->checkPasswordSafe($this->getCurrentUser(), $password, $request->getClientIp(), $lockoutError)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => $lockoutError ?? $translator->trans('invalid.password', [], 'validators'),
                ]);
            }
        }

        $account->setDisableClientPasswordAccess(false);
        $entityManager->persist($account);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @Route("/account/get-password/{accountId}",
     *     name = "awm_get_pass",
     *     methods={"POST"},
     *     requirements = {"accountId" = "\d+"}
     * )
     * @Reauthentication(checkDeviceSupport=false)
     * @Security("is_granted('CSRF')")
     */
    public function getPasswordAction(
        string $accountId,
        AccountRepository $accountRepository,
        LocalPasswordsManager $localPasswordsManager,
        PasswordDecryptor $passwordDecryptor,
        LoggerInterface $securityLogger
    ): JsonResponse {
        if ($this->isGranted('USER_IMPERSONATED')) {
            throw $this->createNotFoundException();
        }

        $account = $accountRepository->find($accountId);

        if (
            !$account
            || !$this->isGranted('READ_PASSWORD', $account)
        ) {
            throw $this->createNotFoundException();
        }

        $securityLogger->info("successful reveal password, password access, accountId: {$account->getAccountid()}, userId: {$account->getUser()->getUserid()}");

        return new JsonResponse([
            'password' => ($account->getSavepassword() === SAVE_PASSWORD_DATABASE) ?
                    $passwordDecryptor->decrypt($account->getPass()) :
                    $localPasswordsManager->getPassword($account->getAccountid()),
        ]);
    }

    protected function loadMapper()
    {
        $options = $this->optionsFactory
            ->createMobileOptions(
                (new Options())
                    ->set(Options::OPTION_USER, $this->awTokenStorage->getBusinessUser())
            );

        return new class($this->accountListManager, $options) {
            /**
             * @var AccountListManager
             */
            private $accountListManager;
            /**
             * @var Options
             */
            private $options;

            public function __construct(AccountListManager $accountListManager, Options $options)
            {
                $this->accountListManager = $accountListManager;
                $this->options = $options;
            }

            public function getAccount($id)
            {
                return $this->accountListManager->getAccount($this->options, $id);
            }

            public function getCoupon($id)
            {
                return $this->accountListManager->getCoupon($this->options, $id);
            }
        };
    }

    private function getFormStub(?Provider $provider = null)
    {
        if (empty($provider) || $provider->getCode() !== 'bankofamerica') {
            return null;
        }

        $supportBOAAuth = $this->apiVersioningService->supports(MobileVersions::BANKOFAMERICA_AUTH);

        if ($supportBOAAuth) {
            return null;
        }

        $form = $this->createFormBuilder()->getForm();
        $form->addError(new FormError("We’ve released a new version of this app, please upgrade."));

        return $form;
    }

    private function createMobileView(FormInterface $form, $displayName, $kind, $login = null, ?Provider $provider = null)
    {
        /** @var FormError $error */
        foreach ($form->getErrors() as $error) {
            if (
                ($cause = $error->getCause()) instanceof ConstraintViolation
                && ($cause = $cause->getCause()) instanceof CauseAwareInterface
            ) {
                switch (true) {
                    case $cause instanceof ExistingAccount:
                        $existingAccount = $cause->getCause();

                        if ($useragent = $existingAccount->getUseragentid()) {
                            $name = $useragent->getFullName();
                        } else {
                            $name = $existingAccount->getUserid()->getFullName();
                        }

                        $name = htmlspecialchars($name);

                        return [
                            'existingAccountId' => (string) $existingAccount->getAccountid(),
                            'login' => $existingAccount->getLogin(),
                            'displayName' => $this->desanitizer->tryDesanitizeChars($displayName),
                            'name' => $name,
                        ];
                }
            }
        }

        return array_merge(
            [
                'Kind' => $kind,
                'DisplayName' => $this->desanitizer->tryDesanitizeChars($displayName),
            ],
            [
                'formData' => $this->formDehydrator
                    ->dehydrateForm(
                        $form,
                        !$this->apiVersioningService->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)
                    ),
            ],
            !empty($login) ? ["Login" => $login] : [],
            ($provider && $provider->getCode() === 'capitalcards') ?
                ['logo' => $provider->getCode()] :
                []
        );
    }

    private function getBalanceChartData(Account $account, ?Entity\Subaccount $subaccount, int $limit): array
    {
        $user = $this->awTokenStorage->getBusinessUser();
        $emptyResult = [
            'data' => [],
            'label' => [],
        ];

        if ($user->getAccountlevel() != ACCOUNT_LEVEL_AWPLUS) {
            return $emptyResult;
        }

        $balancesStmt = $this->accountInfo->getBalanceHistoryStatement($account->getAccountid(), $limit, $subaccount ? $subaccount->getSubaccountid() : null);
        $localizer = $this->localizeService;
        $balancesStmt->setFetchMode(\PDO::FETCH_ASSOC);

        $result = $emptyResult;

        foreach ($balancesStmt as $balanceRow) {
            $date = new \DateTime($balanceRow['UpdateDate']);
            $result['label'][] = [
                $localizer->patternDateTime($date, 'd'),
                trim(mb_strtoupper($localizer->patternDateTime($date, 'LLL')), '.'),
                $localizer->patternDateTime($date, 'yyyy'),
            ];
            $intBalance = (int) $balanceRow['Balance'];
            $floatBalance = (float) $balanceRow['Balance'];

            $result['data'][] = ($intBalance == $floatBalance) ? $intBalance : $floatBalance;
        }

        if (\count($result['data']) > 1) {
            return $result;
        } else {
            return $emptyResult;
        }
    }

    private function checkEncoding(string $string): string
    {
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        }

        return $string;
    }
}
