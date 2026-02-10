<?php

namespace AwardWallet\MainBundle\Service\AutologinV3Handler;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Answer;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\AutologinWithExtensionNotAllowedException;
use AwardWallet\MainBundle\Loyalty\Resources\AutologinWithExtensionRequest;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Security\Voter\SiteVoter;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\AccessDenied;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\AutologinV3DisabledForProvider;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\BrowserConnectionData;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\GetConnectionResultInterface;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\ImpersonatedError;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\MissingLocalPassword;
use AwardWallet\MainBundle\Updater\ExtensionV3SessionMap;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AutologinV3Handler
{
    private SiteVoter $siteVoter;
    private AuthorizationCheckerInterface $authorizationChecker;
    private AwTokenStorageInterface $tokenStorage;
    private ApiCommunicator $apiCommunicator;
    private LoggerInterface $logger;
    private ExtensionV3SessionMap $sessionMap;
    private LocalPasswordsManager $localPasswordsManager;

    public function __construct(
        SiteVoter $siteVoter,
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorageInterface $tokenStorage,
        ApiCommunicator $apiCommunicator,
        LoggerInterface $logger,
        ExtensionV3SessionMap $sessionMap,
        LocalPasswordsManager $localPasswordsManager
    ) {
        $this->siteVoter = $siteVoter;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->apiCommunicator = $apiCommunicator;
        $this->logger = $logger;
        $this->sessionMap = $sessionMap;
        $this->localPasswordsManager = $localPasswordsManager;
    }

    public function getConnection(Account $account, ?string $targetUrl = null): GetConnectionResultInterface
    {
        if (!$this->authorizationChecker->isGranted('USE_PASSWORD_IN_EXTENSION', $account)) {
            if ($this->siteVoter->isImpersonationSandboxEscaped()) {
                return new ImpersonatedError($this->tokenStorage->getUser());
            }

            return new AccessDenied();
        }

        if (!$account->getProviderid()->isAutologinV3()) {
            return new AutologinV3DisabledForProvider();
        }

        if (
            ($account->getSavepassword() === \SAVE_PASSWORD_LOCALLY)
            && !$this->localPasswordsManager->hasPassword($account->getId())
        ) {
            return new MissingLocalPassword();
        }

        /** @var Usr $user */
        $user = $this->tokenStorage->getUser();
        $userData = (new UserData())->setAccountId($account->getAccountid());
        $request = new AutologinWithExtensionRequest();
        $request
            ->setProvider($account->getProviderid()->getCode())
            ->setLogin($account->getLogin())
            ->setLogin2($account->getLogin2())
            ->setLogin3($account->getLogin3())
            ->setPassword($account->getPass())
            ->setUserid($account->getUser()->getId())
            ->setUserdata($userData)
            ->setLoginId($account->getLoginId())
            ->setAnswers(it($account->getAnswers())
                ->filter(fn (Answer $answer) => $answer->getValid())
                ->map(fn (Answer $answer) => new \AwardWallet\MainBundle\Loyalty\Resources\Answer($answer->getQuestion(), $answer->getAnswer()))
                ->toArray()
            )
            ->setAffiliateLinksAllowed($user->isFree() || (!$user->isFree() && !$user->isLinkAdsDisabled()))
            ->setTargetUrl($targetUrl)
        ;

        try {
            $response = $this->apiCommunicator->AutologinWithExtension($request);
        } catch (AutologinWithExtensionNotAllowedException $exception) {
            return new AutologinV3DisabledForProvider();
        }

        $this->logger->info("got autologin v3 connection token", ["accountId" => $account->getId()]);
        $this->sessionMap->setAccountId($response->getBrowserExtensionSessionId(), $account->getId());

        return new BrowserConnectionData(
            $response->getBrowserExtensionSessionId(),
            $response->getBrowserExtensionConnectionToken(),
        );
    }
}
