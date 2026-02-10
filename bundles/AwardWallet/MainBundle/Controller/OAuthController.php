<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Scanner\MailboxFinder;
use AwardWallet\MainBundle\Scanner\UserAgentValidator;
use AwardWallet\MainBundle\Security\LoginRedirector;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\AppleExchangeCodeRequest;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\ExchangeCodeRequest;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\UserName;
use AwardWallet\MainBundle\Security\OAuth\Factory;
use AwardWallet\MainBundle\Security\OAuth\OAuthAction;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\CallbackData;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\OAuthCallbackHandler;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\RedirectCallbackStorage;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\AuthorizedUser;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\CallbackTextError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\CodeExchangeTextError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\ExistingUserError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\InvalidCsrfTextError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\InvalidHost;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\InvalidState;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\LoggedIn;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\MailboxAdded;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\MissingMailboxAccess;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\NotBusinessAdministratorError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\Registered;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\UnauthorizedUser;
use AwardWallet\MainBundle\Security\OAuth\OAuthType;
use AwardWallet\MainBundle\Security\OAuth\StateFactory;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\OAuthMailbox;
use AwardWallet\MainBundle\Service\User\Constants;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OAuthController extends AbstractController
{
    use JsonTrait;

    private const SESSION_BACK_TO = 'oauth_back_to';

    private string $protoAndHost;
    private OAuthCallbackHandler $oauthCallbackHandler;
    private RouterInterface $router;
    private AuthorizationCheckerInterface $authorizationChecker;

    // string values could not be bound ("bind") into action arguments in symfony 3.4, need to upgrade to 4.4
    // https://symfony.com/doc/3.4/controller.html#fetching-services-as-controller-arguments
    // "You can only pass services to your controller arguments in this way."
    public function __construct(
        string $protoAndHost,
        OAuthCallbackHandler $oauthCallbackHandler,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->protoAndHost = $protoAndHost;
        $this->oauthCallbackHandler = $oauthCallbackHandler;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route("/oauth/start/{type}", name="aw_usermailbox_oauth", methods={"GET"}, requirements={"type": "^(microsoft|google|yahoo|aol|apple)$"}, options={"expose"=true})
     */
    public function startAction($type, Request $request, UserAgentValidator $userAgentValidator, Factory $oauthFactory, RouterInterface $router, StateFactory $stateFactory)
    {
        $agentId = $userAgentValidator->checkAgentId($request->query->get('agentId'));
        $action = $request->query->getAlpha("action", OAuthAction::MAILBOX);

        if (!is_string($action) || !StateFactory::isValidAction($action)) {
            throw new BadRequestHttpException("unknown action");
        }

        if ($this->authorizationChecker->isGranted('ROLE_USER') && $action !== OAuthAction::MAILBOX) {
            return $this->redirect($request->getSession()->get(self::SESSION_BACK_TO, $router->generate('aw_home')));
        }

        $profileAccess = in_array($action, [OAuthAction::REGISTER, OAuthAction::LOGIN]);
        $mailboxAccess = $action === OAuthAction::MAILBOX || $request->query->getBoolean('mailboxAccess');

        if (is_array($request->query->get('BackTo'))) {
            throw new BadRequestHttpException("Array in BackTo");
        }

        $targetUrl = urlPathAndQuery($request->query->get('BackTo'));

        if ($targetUrl !== '/') {
            $request->getSession()->set(UserManager::SESSION_KEY_AUTHORIZE_SUCCESS_URL, $targetUrl);
        }

        return new RedirectResponse($oauthFactory->getByType($type)->getConsentUrl(
            $stateFactory->createState($type, $agentId, $mailboxAccess, $profileAccess, $action, $request),
            $this->getRedirectUri($type),
            $mailboxAccess,
            $profileAccess,
            $request->query->get('loginHint')
        ));
    }

    /**
     * stand-alone controller for apple callback, because apple callback is sent with POST
     * and chrome since version 80 will strip out all cookies by SameSite rule
     * we do not need to start session, it will overwrite PHPSESSID cookie,
     * this route marked as session-less in security.yml.
     *
     * @Route("/callback/oauth/apple", name="aw_apple_oauthcallback", methods={"POST"})
     */
    public function appleCallback(
        Request $request,
        RedirectCallbackStorage $redirectCallbackStorage,
        LoggerInterface $securityLogger,
        TranslatorInterface $translator
    ) {
        $securityLogger->info("received apple callback");
        $code = $request->request->get("code");

        if (StringUtils::isEmpty($code)) {
            $securityLogger->info('code is empty!');

            return $this->redirectToRoute('aw_login', ['error' => $translator->trans('error.auth.failure')]);
        }

        $redirectCallbackStorage->save($code, $request->request->all());

        return $this->redirectToRoute("aw_usermailbox_oauthcallback", ["type" => "apple", "code" => $code, "postRedirected" => true]);
    }

    /**
     * @Route("/mailboxes/callback/oauth/{type}", methods={"GET"}, name="aw_usermailbox_oauthcallback", requirements={"type": "^(microsoft|google|yahoo|aol|apple)$"})
     */
    public function callbackAction(
        $type,
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        RedirectCallbackStorage $redirectCallbackStorage,
        LoginRedirector $loginRedirector,
        string $requiresChannel
    ) {
        $isPost = $request->getMethod() === 'POST';
        $input = $isPost ? $request->request : $request->query;
        $code = $input->get("code");

        if ($input->has("postRedirected")) {
            $storedData = $redirectCallbackStorage->load($code);

            if (!$storedData) {
                return $this->redirectToRoute("aw_home");
            }

            $callbackData = $this->populateCallbackData($type, $storedData);
        } else {
            $callbackData = $this->populateCallbackData($type, $input->all());
        }

        $request->attributes->set(
            Constants::REQUEST_PLATFORM_KEY,
            $this->getUaPlatform($request->headers->get('user_agent'))
        );
        $result = $this->oauthCallbackHandler->handle($type, $callbackData, $request);

        if (
            ($result instanceof InvalidState)
            || ($result instanceof AuthorizedUser)
        ) {
            return $this->redirectToRoute("aw_home");
        }

        if ($result instanceof InvalidHost) {
            return $this->redirect($requiresChannel . '://' . $callbackData->getState()->getHost() . $request->getRequestUri());
        }

        if ($result instanceof MissingMailboxAccess) {
            return $this->redirectToRoute('aw_usermailbox_oauth', [
                'type' => $type,
                'agentId' => $callbackData->getState()->getAgentId(),
                'action' => OAuthAction::MAILBOX,
                'mailboxAccess' => true,
                'loginHint' => $result->getLoginHint(),
            ]);
        }

        if (
            ($result instanceof InvalidCsrfTextError)
            || ($result instanceof CodeExchangeTextError)
        ) {
            $state = $callbackData->getState();

            return $this->redirectByAction(
                $state ?
                    $state->getAction() :
                    ($this->authorizationChecker->isGranted('ROLE_USER') ?
                        OAuthAction::MAILBOX :
                        OAuthAction::LOGIN
                    ),
                ["error" => $result->getTextError()]);
        }

        if ($result instanceof UnauthorizedUser) {
            return $this->redirectToRoute("aw_usermailbox_view");
        }

        if ($result instanceof CallbackTextError) {
            if ($callbackData->getState()) {
                return $this->redirectByAction($callbackData->getState()->getAction());
            }

            return $this->redirectToRoute('aw_home');
        }

        if ($result instanceof LoggedIn) {
            return $this->redirect($loginRedirector->getLoginTargetPage($callbackData->getState()->getQuery()));
        }

        if ($result instanceof Registered) {
            return $this->render('@AwardWalletMain/Users/register_oauth_complete.twig', [
                'redirectUrl' => $result->getTargetUrl(),
                'authMethod' => $type,
            ]);
        }

        if (
            ($result instanceof ExistingUserError)
            || ($result instanceof NotBusinessAdministratorError)
        ) {
            return $this->redirectToRoute("aw_login", array_merge(["error" => $result->getTextError()], $callbackData->getState()->getQuery()));
        }

        if ($result instanceof MailboxAdded) {
            return $this->redirect($urlGenerator->generate("aw_usermailbox_view"));
        }

        throw new BadRequestHttpException('Unknown action');
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/update-mailbox/{mailboxId}", name="aw_usermailbox_update_oauth", methods={"GET"}, requirements={"mailboxId": "^\d+$"}, options={"expose"=true})
     * @Route("/mailboxes/update-oauth/{mailboxId}", name="aw_usermailbox_update_oauth_old_mobile", methods={"GET"}, requirements={"mailboxId": "^\d+$"}, options={"expose"=true})
     */
    public function updateAction(
        Request $request,
        int $mailboxId,
        MailboxFinder $mailboxFinder,
        LoggerInterface $securityLogger,
        Factory $oauthFactory,
        StateFactory $stateFactory,
        AwTokenStorageInterface $tokenStorage
    ) {
        $mailbox = $mailboxFinder->findById($tokenStorage->getBusinessUser(), $mailboxId);

        if ($mailbox === null) {
            throw new BadRequestHttpException();
        }

        if ($mailbox instanceof OAuthMailbox) {
            $securityLogger->info("reauthenticating oauth mailbox {$mailboxId}, userData: " . $mailbox->getUserData());
            $userData = json_decode($mailbox->getUserData(), true);
            $oauth = $oauthFactory->getByType($mailbox->getType());

            return new RedirectResponse($oauth->getConsentUrl(
                $stateFactory->createState($mailbox->getType(), $userData['userAgent'] ?? null, true, false, OAuthAction::MAILBOX, $request),
                $this->getRedirectUri($mailbox->getType()),
                true,
                false,
                $mailbox->getEmail()
            ));
        }

        throw new BadRequestHttpException();
    }

    /**
     * @Route("/oauth/unlink/{id}", name="aw_usermailbox_oauth_unlink", methods={"GET"}, requirements={"id": "\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('UNLINK', userOAuth)")
     * @ParamConverter("userOAuth", class="AwardWalletMainBundle:UserOAuth")
     */
    public function unlinkAction(
        UserOAuth $userOAuth,
        LoggerInterface $securityLogger,
        EntityManagerInterface $em
    ) {
        $user = $userOAuth->getUser();

        if ($user->getOAuth()->count() === 1 && empty($user->getPassword())) {
            return $this->errorJsonResponse('setPass');
        }

        $securityLogger->info("oauth unlink", [
            'extra' => json_encode([
                'provider' => $userOAuth->getProvider(),
                'email' => $userOAuth->getEmail(),
                'fullName' => $userOAuth->getFullName(),
            ]),
        ]);
        $em->remove($userOAuth);
        $em->flush();

        return $this->successJsonResponse();
    }

    protected function populateCallbackData(string $type, array $data): CallbackData
    {
        $callbackData = (new CallbackData())
            ->setCode($data['code'] ?? null)
            ->setSerializedState($data['state'] ?? null)
            ->setError($data['error'] ?? null)
            ->setErrorDescription($data['error_description'] ?? null)
            ->setRawCallbackData($data);

        if (OAuthType::APPLE === $type) {
            $userMeta = null;

            if (isset($data['user'])) {
                $userInfo = @\json_decode($data['user'], true);
                $userMeta = new UserName(
                    $userInfo['firstName'] ?? '',
                    $userInfo['lastName'] ?? ''
                );
            }

            $exchangeCodeRequest = new AppleExchangeCodeRequest(
                $data['code'] ?? '',
                $this->getRedirectUri($type),
                $userMeta
            );
        } else {
            $exchangeCodeRequest = new ExchangeCodeRequest(
                $data['code'] ?? '',
                $this->getRedirectUri($type)
            );
        }

        $callbackData->setExchangeCodeRequest($exchangeCodeRequest);

        return $callbackData;
    }

    private function redirectByAction(string $action, array $params = []): RedirectResponse
    {
        if ($action === OAuthAction::MAILBOX) {
            return $this->redirectToRoute("aw_usermailbox_view", $params);
        }

        if (count($params) === 0) {
            return $this->redirectToRoute('aw_home', $params);
        }

        return $this->redirectToRoute('aw_login', $params);
    }

    private function getRedirectUri(string $type): string
    {
        if (OAuthType::APPLE === $type) {
            return $this->protoAndHost . $this->router->generate("aw_apple_oauthcallback", [], UrlGeneratorInterface::ABSOLUTE_PATH);
        }

        return $this->protoAndHost . $this->router->generate("aw_usermailbox_oauthcallback", ["type" => $type], UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    private function getUaPlatform(string $userAgent): string
    {
        if (UserAgentUtils::isMobileBrowser($userAgent)) {
            return Constants::REQUEST_PLATFORM_MOBILE;
        }

        $device = UserAgentUtils::getBrowser($userAgent);

        if (false !== strpos($device['soft'], 'Mobile')
            || false !== strpos($device['browser'], 'Mobile')) {
            return Constants::REQUEST_PLATFORM_MOBILE;
        }

        return Constants::REQUEST_PLATFORM_DESKTOP;
    }
}
