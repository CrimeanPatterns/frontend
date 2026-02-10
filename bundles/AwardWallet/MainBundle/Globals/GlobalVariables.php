<?php

namespace AwardWallet\MainBundle\Globals;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GlobalVariables
{
    public $fileVersion = 1;
    public $metaDescription = "AwardWallet helps you track frequent flyer miles and hotel points as well as book reward tickets.";
    public $metaKeywords = "Incentive reward center, Marriott reward, cheap airfare, reward incentive program,  American Express rewards, trip reward, air miles reward program, frequent flyer miles, track award program balances, air mile reward, award software, award program, delta sky miles, reward program reward network";
    public $smallScreen = false;
    public $bodyOnLoad = "";
    public $needSwitcher = false;
    public $switchText = [
        SITE_MODE_PERSONAL => 'BUSINESS INTERFACE',
        SITE_MODE_BUSINESS => 'PERSONAL INTERFACE',
    ];
    public $accountLevels = [
        ACCOUNT_LEVEL_FREE => "Regular",
        ACCOUNT_LEVEL_AWPLUS => "AwardWallet Plus",
        ACCOUNT_LEVEL_BUSINESS => "AwardWallet Business",
    ];
    public $personalInterfaceMaxUsers = PERSONAL_INTERFACE_MAX_USERS;
    public $eliteUsers = [];
    public $passwordMask = "****************";
    public $secondsPerDay = 86400;

    private $parameters = [];
    /**
     * @var string
     */
    private $host;
    /**
     * @var UsrRepository
     */
    private $usrRepository;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        string $host,
        UsrRepository $usrRepository,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        ?RequestStack $requestStack,
        AwTokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        global $eliteUsers;
        $this->eliteUsers = $eliteUsers;
        $this->host = $host;
        $this->usrRepository = $usrRepository;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    public function getRoot()
    {
        global $sPath;

        return $sPath;
    }

    public function getSiteName()
    {
        return SITE_NAME;
    }

    public function getServerName()
    {
        return $this->host;
    }

    public function getFileVersion()
    {
        if (defined('FILE_VERSION')) {
            $this->fileVersion = FILE_VERSION;
        }

        return $this->fileVersion;
    }

    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    public function isSiteModeBusiness()
    {
        return SITE_MODE == SITE_MODE_BUSINESS;
    }

    public function isSiteModePersonal()
    {
        return SITE_MODE == SITE_MODE_PERSONAL;
    }

    public function isSmallScreen()
    {
        return $this->smallScreen;
    }

    public function getConnection()
    {
        global $Connection;

        return $Connection;
    }

    public function getBodyOnLoad()
    {
        return $this->bodyOnLoad;
    }

    public function isNeedSwitcher()
    {
        return $this->needSwitcher = (SITE_MODE == SITE_MODE_BUSINESS || (isset($_SESSION["HaveABusinessAccount"]) && $_SESSION["HaveABusinessAccount"]) || isset($_SESSION['SuccessfullConvert'])
                 || isset($_SESSION['AdminOfBusinessAccount']));
    }

    public function isBusinessMismanagementFromPersonal()
    {
        if ($this->isSiteModePersonal() && $this->isNeedSwitcher()) {
            $userRep = $this->usrRepository;
            $businessId = $userRep->getBusinessIdByUserAdmin($_SESSION['UserID']);
            $businessUser = $userRep->find($businessId);

            if (!$businessUser) {
                return false;
            }

            return $businessUser->getMismanagement() == 1;
        }

        return false;
    }

    public function getSwitchText()
    {
        return $this->translator->trans( /** @Ignore */ ucfirst(strtolower($this->switchText[SITE_MODE])));
    }

    // brands
    public function getBrandPrefix()
    {
        switch (SITE_BRAND) {
            case SITE_BRAND_CWT: $brandPref = 'CWT';

                break;

            case SITE_BRAND_BWR: $brandPref = 'BWR';

                break;

            default:
                $brandPref = '';
        }

        return $brandPref;
    }

    public function getCanonicalUrl()
    {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $this->host . $_SERVER['REQUEST_URI'];
    }

    public function getBrandFullName()
    {
        switch (SITE_BRAND) {
            case SITE_BRAND_CWT: $brandName = 'Carlson Wagonlit Travel';

                break;

            case SITE_BRAND_BWR: $brandName = 'Best Western Rewards';

                break;

            default:
                $brandName = false;
        }

        return $brandName;
    }

    public function isExistsCacheScripts()
    {
        return file_exists($this->getRoot() . "/cache/scripts-" . $this->getFileVersion() . ".js");
    }

    public function isImpersonated()
    {
        return $this->authorizationChecker->isGranted("ROLE_IMPERSONATED");
    }

    public function isLocalProd()
    {
        if (
            ($requestStack = $this->requestStack)
            && ($request = $requestStack->getCurrentRequest())
        ) {
            $siteName = $request->getHost();
        } else {
            $siteName = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        }

        return strpos($siteName, '.local') !== false;
    }

    public function isSiteStateDebug()
    {
        return ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG;
    }

    public function isRetina()
    {
        if (!isset($_COOKIE['MobileBrowser']) || $_COOKIE['MobileBrowser'] == '') {
            return false;
        }
        parse_str($_COOKIE['MobileBrowser']);

        if (!isset($dpiw) || intval($dpiw) < 150) {
            return false;
        }

        return true;
    }

    public function isMobileSite(Request $request)
    {
        if (preg_match("/\/(mobile|_wdt)\/.+/ims", $request->getPathInfo())) {
            return true;
        }

        return false;
    }

    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    public function hasParameter($key)
    {
        return isset($this->parameters[$key]);
    }

    public function getParameter($key)
    {
        if (!$this->hasParameter($key)) {
            return null;
        }

        return $this->parameters[$key];
    }

    public function getAccountChecker(Provider $provider, $isMobile = false)
    {
        $class = "TAccountChecker" . ucfirst(strtolower($provider->getCode()));

        if (!class_exists($class)) {
            $class = "TAccountChecker";
        }
        /** @var \TAccountChecker $checker */
        $checker = new $class();

        if ($isMobile) {
            $checker->Skin = 'mobile';
        }
        $checker->authorizationChecker = $this->authorizationChecker;
        $checker->setUserFields($this->tokenStorage->getUser());
        $checker->logger = $this->logger;
        $checker->globalLogger = $checker->logger;
        $checker->AccountFields = [
            'ProviderID' => $provider->getProviderid(),
            'ProviderCode' => $provider->getCode(),
            'DisplayName' => $provider->getDisplayname(),
            'ShortName' => $provider->getShortname(),
            'ProgramName' => $provider->getProgramname(),
            'Code' => $provider->getCode(),
            'Partner' => 'awardwallet',
        ];

        return $checker;
    }

    public function googleMapKey()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            if ($_SERVER['HTTP_HOST'] == 'awardwallet.local') {
                return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBSuuR8A2qe-omiXDlLjUIjHkdwf7xRW3EEGhYCFBdJVhBcQPTjXfL-5CA';
            }

            if ($_SERVER['HTTP_HOST'] == 'test.awardwallet.com') {
                return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBTW00WWfNlIzaR1Bxvy1xkfV5IAABRIGcHkVsC28ZvP_3RqnQW5XIXifg';
            }

            if ($_SERVER['HTTP_HOST'] == 'sprint.awardwallet.com') {
                return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBQsvP3FuTh6TsBZOKNw1fYSkKa5bRT61yPEriFnF2Dwhch-Xu8pZgIu4Q';
            }

            if ($_SERVER['HTTP_HOST'] == 'business.awardwallet.com') {
                return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBSL6hmMcQuzt0veTmEueLWt_cw9PxSNaBB62lJ7xInsQYtfL04Jra3_GA';
            }

            if ($_SERVER['HTTP_HOST'] == 'business.awardwallet.local') {
                return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBRrcWJDrBFsdpmMVmXmqAFIghnYERQFzIEJCuv0eC563y8X3f1EjlvZLQ';
            }

            if ($_SERVER['HTTP_HOST'] == 'business.test.awardwallet.com') {
                return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBSJmk-f8WDe02iCNsv0K2pM13LB-xRRLHndrs_HJnG-TCFDMhMN4hNwww';
            }

            if ($_SERVER['HTTP_HOST'] == 'business.sprint.awardwallet.com') {
                return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBRBiL2sKbyKDD9PNGdhnw1cmsP1axR12LRG1P9jqyPmqk8C8VICDW19Yw';
            }

            if ($_SERVER['HTTP_HOST'] == 'iframe.test.awardwallet.com') {
                return 'ABQIAAAAUVlD2zcUDrGzUH4tQpXBmBQfsWQ1NE5_A7DbcexwhbVHFNTUpxQitcJKCLERfLjpDRDRX-h_RikFrA';
            }

            if ($_SERVER['HTTP_HOST'] == 'aw1.awardwallet.com') {
                return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBSxkLohAPwA9BUw7qAuDcI8QFZlzhSYR9sJ7oG_SPlA_lWXHr1HezNm6w';
            }

            if ($_SERVER['HTTP_HOST'] == 'aw2.awardwallet.com') {
                return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBTjqYW3l-k94ZFd6pfrcF0pzrznEhTkpklklp4I1hnNqmY9FuXzFPfNtg';
            }
        }

        return 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBQ8lW716zarLhk6AW2Z7Z8rVIlJ-RT6v4k0uWRbkS5lu6idXzPj8LfaBg';
    }

    public function getProviderMultinames()
    {
        global $providerMultiname;

        return $providerMultiname;
    }

    public function getMobileVersion()
    {
        return 2;
    }

    public function getMobileHtmlVersion()
    {
        return 1;
    }

    public function getItunesLink()
    {
        return 'https://itunes.apple.com/us/app/awardwallet/id388442727?mt=8';
    }

    public function getPlayMarketLinkMobile()
    {
        return 'market://details?id=com.itlogy.awardwallet';
    }

    public function getStoreVersions()
    {
        return [
            'ios' => '2.1',
            'android' => '2.3',
        ];
    }

    public function getPersonalHost()
    {
        if ($this->authorizationChecker->isGranted('NOT_SITE_BUSINESS_AREA')) {
            return $this->requestStack->getMasterRequest()->getHost();
        } else {
            return $this->host;
        }
    }

    public function getBusinessUser(): Usr
    {
        return $this->tokenStorage->getBusinessUser();
    }
}
