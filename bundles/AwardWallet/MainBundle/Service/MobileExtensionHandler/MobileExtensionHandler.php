<?php

namespace AwardWallet\MainBundle\Service\MobileExtensionHandler;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\Entity\Answer;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ElitelevelRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\Updater\UpdaterUtils;
use AwardWallet\MainBundle\Globals\Utils\Result\ResultInterface;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticatorHandler;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors\AccessDenied;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors\LocalPassword;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors\NotFound;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors\UnsupportedProvider;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\Result\fail;
use function AwardWallet\MainBundle\Globals\Utils\Result\success;

class MobileExtensionHandler
{
    public const MOBILE_TYPE = 'mobile';
    public const DESKTOP_TYPE = 'desktop';
    public const EXTENSION_TYPE = 'extension';

    private const ITINERARY_CODES_MAP = [
        'PU' => 'Rental',
        'DO' => 'Rental',
        'PE' => 'Parking',
        'PS' => 'Parking',
        'CI' => 'Reservation',
        'CO' => 'Reservation',
        'E' => 'Restaurant',
        'S' => 'Tripsegment',
        'T' => 'Tripsegment',
    ];
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var AccountRepository
     */
    private $accountRepository;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var Desanitizer
     */
    private $desanitizer;
    /**
     * @var ProviderTranslator
     */
    private $providerTranslator;
    /**
     * @var string
     */
    private $rootDir;
    /**
     * @var EntityRepository
     */
    private $answerRepository;
    /**
     * @var AwTokenStorageInterface
     */
    private $awTokenStorage;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    /**
     * @var ElitelevelRepository
     */
    private $elitelevelRepository;
    /**
     * @var LocalPasswordsManager
     */
    private $localPasswordsManager;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var Util
     */
    private $util;
    /**
     * @var ProviderRepository
     */
    private $providerRepository;
    /**
     * @var MobileReauthenticatorHandler
     */
    private $mobileReauthenticatorHandler;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AccountRepository $accountRepository,
        TranslatorInterface $translator,
        Desanitizer $desanitizer,
        ProviderTranslator $providerTranslator,
        EntityRepository $answerRepository,
        AwTokenStorageInterface $awTokenStorage,
        LoggerInterface $statLogger,
        RequestStack $requestStack,
        ApiVersioningService $apiVersioning,
        ElitelevelRepository $elitelevelRepository,
        LocalPasswordsManager $localPasswordsManager,
        EntityManagerInterface $entityManager,
        ProviderRepository $providerRepository,
        Util $util,
        string $rootDir
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->accountRepository = $accountRepository;
        $this->translator = $translator;
        $this->desanitizer = $desanitizer;
        $this->providerTranslator = $providerTranslator;
        $this->rootDir = $rootDir;
        $this->answerRepository = $answerRepository;
        $this->awTokenStorage = $awTokenStorage;
        $this->logger = $statLogger;
        $this->requestStack = $requestStack;
        $this->apiVersioning = $apiVersioning;
        $this->elitelevelRepository = $elitelevelRepository;
        $this->localPasswordsManager = $localPasswordsManager;
        $this->entityManager = $entityManager;
        $this->util = $util;
        $this->providerRepository = $providerRepository;
    }

    public function loadExtensionForProvider(Provider $provider): ResultInterface
    {
        $srcDir = $this->rootDir . '/../web';
        $extensions = [
            'extension' => [
                $srcDir . '/../engine/' . $provider->getCode() . '/extensionMobile.js',
                $this->create_AWREFCODE_Transformer($this->awTokenStorage->getUser()),
            ],
            'utilities' => $srcDir . '/extension/util.js',
            'mobile api' => $this->getMobileApiSource(),
        ];

        return $this->loadExtensionFiles($extensions);
    }

    public function loadExtensionForProviderByCode(string $providerCode): ResultInterface
    {
        $provider = $this->providerRepository->findOneByCode($providerCode);

        if (!$provider) {
            return fail(new NotFound());
        }

        return $this->loadExtensionForProvider($provider);
    }

    /**
     * @return ResultInterface<Itinerary, NotFound>
     */
    public function getItineraryByItineraryId(string $itineraryId): ResultInterface
    {
        $itineraryId = \explode('.', $itineraryId);

        if (
            (\count($itineraryId) !== 2)
            || !isset(self::ITINERARY_CODES_MAP[$itineraryId[0]])
        ) {
            return fail(new NotFound());
        }

        /** @var Itinerary $itineraryEntity */
        $itineraryEntity = $this->entityManager
            ->getRepository(Itinerary::getItineraryClass(self::ITINERARY_CODES_MAP[$itineraryId[0]]))
            ->find($itineraryId[1]);

        if (!$itineraryEntity) {
            return fail(new NotFound());
        }

        if ($itineraryEntity instanceof Tripsegment) {
            $itineraryEntity = $itineraryEntity->getTripid();
        }

        return success($itineraryEntity);
    }

    public function loadExtensionForItineraryById(string $itineraryId, string $type): ResultInterface
    {
        $itineraryEntityResult = $this->getItineraryByItineraryId($itineraryId);

        if ($itineraryEntityResult->isFail()) {
            return $itineraryEntityResult;
        }

        $itineraryEntity = $itineraryEntityResult->unwrap();

        if (!$this->authorizationChecker->isGranted('AUTOLOGIN', $itineraryEntity)) {
            return fail(new AccessDenied());
        }

        $confNoParamsMixin = [];

        if (in_array($itineraryEntity->getProvider()->getItineraryautologin(), [ITINERARY_AUTOLOGIN_ACCOUNT, ITINERARY_AUTOLOGIN_BOTH, ITINERARY_AUTOLOGIN_CONFNO])) {
            if (!empty($itineraryEntity->getConfFields())) {
                $confNoParamsMixin["properties"]["confFields"] = $itineraryEntity->getConfFields();
            }

            $confNoParamsMixin["properties"]["confirmationNumber"] = $itineraryEntity->getConfirmationNumber(true);
            $confNoParamsMixin['itineraryAutologin'] = true;
            $confNoParamsMixin['fromPartner'] = null;
        }

        $provider = $itineraryEntity->getProvider();
        $account = $itineraryEntity->getAccount();

        // get any accountId of this provider, if there is no link to account in itinerary
        if (
            !$account
            && empty($itineraryEntity->getConfFields())
        ) {
            $accountId = $this->util->getAnyAccount($itineraryEntity->getUserid()->getUserid(), $itineraryEntity->getProvider()->getProviderid(), $itineraryEntity->getNames());

            if ($accountId) {
                $account = $this->accountRepository->find($accountId);
            }
        }

        if ($account) {
            return $this->loadExtensionForAccount($account, $type, $confNoParamsMixin);
        } elseif ($confNoParamsMixin) {
            $confNoParamsMixin = $this->mergeParams($confNoParamsMixin, [
                "accountId" => 0,
                "login" => "",
                "password" => "",
                "properties" => [],
            ]);
            $confNoParamsMixin['account'] = $confNoParamsMixin;

            return $this->loadExtensionByParams($provider, $confNoParamsMixin, $type);
        } else {
            return fail(new NotFound());
        }
    }

    public function loadExtensionForAccountById(int $accountId, string $type): ResultInterface
    {
        /** @var Account $account */
        $account = $this->accountRepository->find($accountId);

        if (!$account) {
            return fail(new NotFound());
        }

        return $this->loadExtensionForAccount($account, $type, ['itineraryAutologin' => false]);
    }

    public function loadExtensionForAccount(Account $account, string $type, array $paramsMixin = []): ResultInterface
    {
        $accessCheckResult = $this->accountAccessCheck($account);

        if ($accessCheckResult->isFail()) {
            return $accessCheckResult;
        }

        $isMobile = (self::MOBILE_TYPE === $type);
        $params = $this->getParamsForAccount($account, $paramsMixin);
        $extensionLoaderResult = $this->loadExtensionByParams($account->getProviderid(), $params, $type);

        if ($extensionLoaderResult->isFail()) {
            return $extensionLoaderResult;
        }

        $content = $extensionLoaderResult->unwrap();
        $user = $this->awTokenStorage->getUser();

        if (!(
            $params['skipCashbackUrl']
            || empty($params['cashbackLink'])
            || (
                $user->isAwPlus()
                && $user->isLinkAdsDisabled()
            )
        )
        ) {
            // we want cashback url here
            $content = $this->clickUrlReplacement($isMobile, $content);
        }

        return success($content);
    }

    public function getMobileApiSource(): string
    {
        $srcDir = $this->rootDir . '/..';

        switch (true) {
            case $this->apiVersioning->supports(MobileVersions::EXTENSION_SIGNED_NATIVE_EVENTS) && $this->apiVersioning->supports(MobileVersions::NATIVE_APP):
                return $srcDir . '/engine/awextension/extensionMobileApi-v3.1.js';

            case $this->apiVersioning->supports(MobileVersions::EXTENSION_NATIVE_EVENTS) && $this->apiVersioning->supports(MobileVersions::NATIVE_APP):
                return $srcDir . '/engine/awextension/extensionMobileApi-v3.js';

            case $this->apiVersioning->supports(MobileVersions::UPDATER_CLIENT_CHECK_LOGS) && $this->apiVersioning->supports(MobileVersions::NATIVE_APP):
                return $srcDir . '/engine/awextension/extensionMobileApi-v2.1.js';

            case $this->apiVersioning->supports(MobileVersions::UPDATER_CLIENT_CHECK) && $this->apiVersioning->supports(MobileVersions::NATIVE_APP):
                return $srcDir . '/engine/awextension/extensionMobileApi-v2.js';

            default:
                return $srcDir . '/engine/awextension/extensionMobileApi.js';
        }
    }

    public function loadExtensionFiles(array $files): ResultInterface
    {
        $extension = '';
        $identity = function ($a) { return $a; };

        foreach ($files as $comment => $fileData) {
            if (\is_array($fileData) && (\count($fileData) === 2)) {
                [$fileName, $transformer] = $fileData;
            } elseif (\is_string($fileData)) {
                $fileName = $fileData;
                $transformer = $identity;
            } else {
                throw new \LogicException('File data must be either tuple [string, callable] or string!');
            }

            if (!file_exists($fileName)) {
                return fail(new NotFound());
            }

            $fileContent = file_get_contents($fileName);

            if (false === $fileContent) {
                return fail(new NotFound());
            }

            $fileContent = $transformer($fileContent);
            $extension .= "\n\n//" . $comment . "\n\n" . $fileContent;
        }

        return success($extension);
    }

    protected function accountAccessCheck(Account $account): ResultInterface
    {
        if (!$this->authorizationChecker->isGranted('AUTOLOGIN', $account)) {
            return fail(new AccessDenied());
        }

        $provider = $account->getProviderid();

        if (!(($provider->getState() >= PROVIDER_ENABLED) || in_array($provider->getState(), [PROVIDER_TEST, PROVIDER_RETAIL], true))) {
            return fail(new UnsupportedProvider(
                $this->translator->trans(
                    /** @Desc("Sorry, we currently do not support %provider-name%") */
                    'provider.not.supported',
                    ['%provider-name%' => $this->desanitizer->tryDesanitizeChars(
                        $this->providerTranslator->translateDisplayNameByEntity($provider)
                    )]
                )
            ));
        }

        if (!$this->hasPassword($account)) {
            return fail(new LocalPassword($account));
        }

        return success();
    }

    protected function loadExtensionByParams(Provider $provider, array $params, string $type): ResultInterface
    {
        $isMobile = self::MOBILE_TYPE === $type;
        $request = $this->requestStack->getMasterRequest();
        $content = '
            var applicationPlatform = \'' . $request->headers->get(MobileHeaders::MOBILE_PLATFORM) . '\';
            var params = ' . json_encode($params) . ";\n\n";
        $srcDir = $this->rootDir . '/../web';
        $providerCode = $provider->getCode();
        $extensions = [
            'extension' => [
                $srcDir . '/../engine/' . $providerCode . '/extension' . ($isMobile ? 'Mobile' : '') . '.js',
                $this->create_AWREFCODE_Transformer($this->awTokenStorage->getUser()),
            ],
            'utilities' => $srcDir . '/extension/util.js',
            'mobile api' => $this->getMobileApiSource(),
        ];

        $extensionLoaderResult = $this->loadExtensionFiles($extensions);

        if ($extensionLoaderResult->isFail()) {
            return $extensionLoaderResult;
        }

        $content .= $extensionLoaderResult->unwrap();

        return success($content);
    }

    protected function getParamsForAccount(Account $account, array $mixin): array
    {
        $params = [
            'login' => $account->getLogin(),
            'login2' => $account->getLogin2(),
            'login3' => $account->getLogin3(),
            'pass' => $account->getPass(),
            'accountId' => $account->getAccountid(),
            'fromPartner' => null,
        ];
        $params['password'] = $params['pass'];
        $params['canUpdate'] = $this->authorizationChecker->isGranted('UPDATE', $account);
        $params['providerCode'] = $account->getProviderid()->getCode();

        $params['answers'] = [];

        /** @var Answer $answer */
        foreach ($this->answerRepository->findBy(['accountid' => $account->getAccountid(), 'Valid' => true]) as $answer) {
            $params['answers'][$answer->getQuestion()] = $answer->getAnswer();
        }

        foreach ($account->getProperties() as $property) {
            $params['properties'][$property->getProviderpropertyid()->getCode()] = $property->getVal();
        }

        $params['parseItineraries'] = UpdaterUtils::shouldCheckTripsByEntity($account);

        $params['historyStartDate'] =
            !empty($account->getHistoryVersion()) && ($account->getHistoryVersion() == $account->getProviderid()->getCacheversion()) ?
                \AccountAuditorAbstract::getAccountHistoryLastDate($account->getAccountid()) :
                0;

        if (in_array($account->getProviderid()->getProviderid(), Provider::EARNING_POTENTIAL_LIST)) {
            $historyRepo = $this->entityManager->getRepository(AccountHistory::class);
            $params['subAccountHistoryStartDate'] = [];

            /** @var Subaccount $subAcc */
            foreach ($account->getSubAccountsEntities() as $subAcc) {
                $params['subAccountHistoryStartDate'][$subAcc->getCode()] =
                    $historyRepo->getLastHistoryRowDateBySubAccount($subAcc->getId());
            }
        }

        if (!empty($link = $account->getProviderid()->getClickurl())) {
            $user = $this->awTokenStorage->getUser();
            $refCode = ($user instanceof Usr ? $user->getRefcode() : 'awardwallet');
            $link = str_ireplace("AWREFCODE", $refCode . '-m', $link);
            $params['cashbackLink'] = $link;
            $this->logger->info("partner autologin", [
                'accountId' => $account->getAccountid(),
                'provider' => $params['providerCode'],
                'ua' => $this->requestStack->getMasterRequest()->headers->get('USER_AGENT'),
                'ip' => $this->requestStack->getMasterRequest()->getClientIp(),
                'isMobile' => true,
                'clickUrl' => $link,
            ]);
        }

        // refs#17733 disable clickUrl for HHonors Diamond members
        $eliteLevel = $this->elitelevelRepository->getEliteLevelFieldsByValue(
            $account->getProviderId()->getProviderid(),
            $account->getEliteLevel()
        );

        $params['skipCashbackUrl'] =
            ($account->getProviderid()->getProviderid() === 22) // Hilton
            && !empty($eliteLevel)
            && \in_array($eliteLevel['Rank'], [2, 3]); // [Gold, Diamond]

        $params = $this->mergeParams($params, $mixin);
        $params['account'] = $params;

        return $params;
    }

    protected function mergeParams(array $params, $paramsMixin): array
    {
        // merge subarrays (1 level only)
        foreach ($paramsMixin as $mixinKey => $mixinValue) {
            if (\array_key_exists($mixinKey, $params)) {
                if (is_array($params[$mixinKey]) && is_array($mixinValue)) {
                    $params[$mixinKey] = \array_merge($params[$mixinKey], $mixinValue);
                } else {
                    $params[$mixinKey] = $mixinValue;
                }
            } else {
                $params[$mixinKey] = $mixinValue;
            }
        }

        return $params;
    }

    protected function clickUrlReplacement($isMobile, $content)
    {
        if ($isMobile) {
            $js = '
        start : function() {
            if ("undefined" != typeof params.autologin && false === params.autologin) {
                browserAPI.log("skip mobile cashbackLink -> call plugin.start()");
                return this.dispatch();
            }
            
            browserAPI.log("startReplacement");
            if ("undefined" !== typeof this.cashbackLinkMobile && false === this.cashbackLinkMobile) {
                browserAPI.log("cashbackLinkMobile: disabled");
                return this.dispatch();
            }
            if (undefined !== params.cashbackLink && "" != params.cashbackLink && "undefined" === typeof params.clickVisited) {
                browserAPI.log("clickUrl mobile");
                params.clickVisited = true;
                api.setNextStep("startDispatch", function () {
                    document.location.href = params.cashbackLink;
                });
                return true;
            }
            return this.dispatch();
        },
        startDispatch : function () {
            browserAPI.log("startDispatch mobile");
            var url = ("function" == typeof plugin.autologin.getStartingUrl ? plugin.autologin.getStartingUrl(params) : plugin.autologin.url);
            if (document.location.href == url)
                return this.dispatch();
            api.setNextStep("dispatch", function () {
                document.location.href = url;
            });
        },';
        } else {
            $js = '
        start : function() {
            if ("undefined" != typeof params.autologin && false === params.autologin) {
                browserAPI.log("skip desktop cashbackLink -> call plugin.start()");
                return plugin.dispatch(params);
            }

            browserAPI.log("startReplacement");
            if ("undefined" !== typeof plugin.cashbackLinkMobile && false === plugin.cashbackLinkMobile) {
                browserAPI.log("cashbackLinkMobile: disabled");
                return plugin.dispatch(params);
            }
            if (undefined !== params.cashbackLink && "" != params.cashbackLink && "undefined" === typeof params.data.clickVisited) {
                browserAPI.log("clickUrl desktop");
                params.data.clickVisited = true;
                provider.saveTemp(params.data);
                provider.setNextStep("startDispatch", function () {
                    document.location.href = params.cashbackLink;
                });
                return true;
            }
            return plugin.dispatch(params);
        },
        startDispatch : function () {
            browserAPI.log("startDispatch desktop");
            if (document.location.href == plugin.getStartingUrl(params))
                return plugin.dispatch(params);
            provider.setNextStep("dispatch", function () {
                document.location.href = plugin.getStartingUrl(params);
            });
        },';
        }

        $content = preg_replace('/\sstart\s*:\s*function/i', $js . "\n\t" . 'dispatch : function', $content);
        $content = str_replace(["setNextStep('start'", 'setNextStep("start"'], ["setNextStep('dispatch'", 'setNextStep("dispatch"'], $content);

        return $content;
    }

    protected function hasPassword(Account $account): bool
    {
        if (SAVE_PASSWORD_LOCALLY == $account->getSavepassword()) {
            if ($this->localPasswordsManager->hasPassword($account->getAccountid())) {
                $account->setPass($this->localPasswordsManager->getPassword($account->getAccountid()));
            } else {
                return false;
            }
        }

        return true;
    }

    protected function create_AWREFCODE_Transformer(?Usr $user): callable
    {
        $refCode = $user ? $user->getRefcode() : 'awardwallet';

        return function (string $extension) use ($refCode) {
            return \str_replace('=AWREFCODE', '=' . $refCode . '-m', $extension);
        };
    }
}
