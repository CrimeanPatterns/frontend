<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\ClientVerificationHandler;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Security\Utils;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LoginController extends AbstractController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @Route("/security/login-by-key", name="aw_security_login_by_key", methods={"POST"})
     */
    public function loginByKeyAction(
        Request $request,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ConnectionInterface $connection
    ) {
        $row = $connection->executeQuery("select * from MobileKey
      		where UserID = :userId and MobileKey = :key and Kind = " . UserManager::KEY_KIND_IMPERSONATE,
            ["userId" => $request->request->get("UserID"), "key" => $request->request->get("Key")]
        )->fetch(\PDO::FETCH_ASSOC);

        if (empty($row)) {
            return new RedirectResponse("/");
        }
        $params = unserialize($row['Params']);
        $ur = $entityManager->getRepository(Usr::class);
        /** @var Usr $user */
        $user = $ur->find($row['UserID']);

        if (empty($user)) {
            return new RedirectResponse("/");
        }
        $connection->executeUpdate("delete from MobileKey where MobileKeyID = :keyId", ["keyId" => $row['MobileKeyID']]);
        $originalToken = unserialize($params['OriginalToken']);

        try {
            $this->userManager->loadToken($user, false, $params["LoginType"], $params["AwPlus"], $originalToken);

            return new RedirectResponse($params['TargetURL']);
        } catch (AuthenticationException $e) {
            $logger->warning($e->getMessage(), ["trace" => $e->getTraceAsString()]);

            return $this->forward('AwardWallet\MainBundle\Controller\LogoutController::logoutAction');
        }
    }

    /**
     * @Route("/login_check", name="aw_users_logincheck", methods={"POST"}, options={"expose"=true})
     */
    public function loginCheckAction()
    {
        throw new \LogicException('unreachable');
    }

    /**
     * @Route("/login_fail", name="aw_users_loginfail")
     */
    public function loginFailAction()
    {
    }

    /**
     * @Route("/user/check", name="aw_login_client_check", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('CSRF')")
     */
    public function getClientCheckAction(
        Request $request,
        ClientVerificationHandler $clientVerification,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $check = $clientVerification->getClientCheck();

        return new JsonResponse([
            'expr' => $check['jsExpression'],
            'csrf_token' => $csrfTokenManager->getToken("authenticate")->getValue(),
        ]);
    }

    /**
     * @Route("/security/switch-site", name="aw_security_switch_site", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function switchSiteAction(
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorageInterface $tokenStorage
    ) {
        $goto = urlPathAndQuery($request->query->get('Goto', '/'));
        $toBusiness = !$authorizationChecker->isGranted('SITE_BUSINESS_AREA');
        $user = $this->getUser();
        $token = $tokenStorage->getToken();
        $loginType = UserManager::LOGIN_TYPE_USER;

        if (Utils::tokenHasRole($token, 'ROLE_IMPERSONATED')) {
            $loginType = UserManager::LOGIN_TYPE_IMPERSONATE;
        } elseif (Utils::tokenHasRole($token, 'ROLE_IMPERSONATED_FULLY')) {
            $loginType = UserManager::LOGIN_TYPE_IMPERSONATE_FULLY;
        }

        return $this->userManager->loginUserByKey($user, $toBusiness, $goto, $loginType);
    }
}
