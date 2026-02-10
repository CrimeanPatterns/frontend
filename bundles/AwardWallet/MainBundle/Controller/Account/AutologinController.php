<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Form\Account\AnswerHelper;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\Util;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AutologinController extends AbstractController
{
    /**
     * this action has /account/overview route to spoof referer: Commission Junction requires that redirect should be opened
     * without intermediate pages.
     *
     * @Security("is_granted('ROLE_USER')")
     * @Route("/overview", name="aw_autologin_extension")
     * @Template("@AwardWalletMain/Account/Autologin/autologin.html.twig")
     */
    public function autologinAction()
    {
        return [];
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/redirect", name="aw_account_redirect", options={"expose": true})
     */
    public function redirectAction(
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerInterface $securityLogger,
        LoggerInterface $logger,
        UpdaterEngineInterface $updaterEngine,
        SessionInterface $session,
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorageInterface $tokenStorage,
        Util $util,
        LocalPasswordsManager $localPasswordsManager,
        AnswerHelper $answerHelper
    ) {
        $id = intval($request->query->get("ID"));

        if (!empty($id)) {
            $account = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($id);

            if (empty($account)) {
                throw new AccessDeniedHttpException();
            }
        }
        $mode = filter_var($request->query->get("Mode", "autologin"), FILTER_SANITIZE_STRING);
        $step = $request->query->get("step");

        if ($request->query->has('Goto') && !empty($session->get('RedirectTo' . $id)) && ($session->get('RedirectTo' . $id) == $request->query->get('Goto'))) {
            $goto = $request->query->get('Goto');
        }
        $offerVar = $session->get("OfferVar");
        $table = $request->query->get("table");
        $itID = intval($request->query->get("itID"));
        $loginWithExtension = false;

        $cookies = [];

        $params = [
            'itineraryAutologin' => false,
            "accountId" => 0,
            "login" => "",
            "password" => "",
            "properties" => [],
        ];

        if (!empty($step)) {
            $params['step'] = $step;
        }

        if (!empty($goto)) {
            $params['goto'] = $goto;
        }

        if (!empty($offerVar)) {
            $params['offerVar'] = $offerVar;
        }

        if (isBusinessMismanagement()) {
            ScriptRedirect('/');
        }

        // Redirect to Itinerary ?
        $kind = $table;
        $table = ArrayVal(Itinerary::$table, $kind);

        if (!empty($table) && !empty($itID)) {
            $repo = $entityManager->getRepository(Itinerary::getItineraryClass($table));
            /** @var Itinerary $it */
            $it = $repo->find($itID);

            if (empty($it) || !$authorizationChecker->isGranted('AUTOLOGIN', $it)) {
                throw new AccessDeniedHttpException();
            }

            if (empty($id) && !empty($it->getAccount())) {
                $id = $it->getAccount()->getAccountid();
            }
            $provider = $it->getProvider();

            $params['providerCode'] = $it->getProvider()->getCode();

            if (in_array($it->getProvider()->getItineraryautologin(), [ITINERARY_AUTOLOGIN_ACCOUNT, ITINERARY_AUTOLOGIN_BOTH, ITINERARY_AUTOLOGIN_CONFNO])) {
                if (!empty($it->getConfFields())) {
                    $params["properties"]["confFields"] = $it->getConfFields();
                }
                $params["properties"]["confirmationNumber"] = $it->getConfirmationNumber(true);
                $params['itineraryAutologin'] = true;
                $params['fromPartner'] = null;
                $loginWithExtension = true;
            }

            // get any accountId of this provider, if there is no link to account in itinerary
            if (empty($id) && empty($it->getConfFields())) {
                $id = $util->getAnyAccount(
                    $it->getUserid()->getUserid(),
                    $it->getProvider()->getProviderid(),
                    $it->getNames()
                );
            }
        }

        if (!empty($id)) {
            /** @var \AwardWallet\MainBundle\Entity\Account $account */
            $account = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($id);
            $provider = $account->getProviderid();
            $redirectOnly = isGranted('REDIRECT', $account);
            $allowAutologin = isGranted('AUTOLOGIN', $account);

            if (!$allowAutologin && !$redirectOnly && !(!empty($session->get('AllowRedirectTo')) && ($session->get('AllowRedirectTo') == $id))) {
                throw new AccessDeniedHttpException();
            }

            $params['accountId'] = $id;
            $params['providerCode'] = $account->getProviderid()->getCode();
            $params['login'] = $account->getLogin();
            $params['login2'] = $account->getLogin2();
            $params['login3'] = $account->getLogin3();

            // refs#17733 disable clickUrl for HHonors Diamond members
            $eliteLevelRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class);
            $eliteLevel = $eliteLevelRep->getEliteLevelFieldsByValue(
                $account->getProviderId()->getProviderid(),
                $account->getEliteLevel()
            );
            $params['skipCashbackUrl'] =
                ($provider->getProviderid() === 22) // Hilton
                && !empty($eliteLevel)
                && \in_array($eliteLevel['Rank'], [2, 3]); // [Gold, Diamond]

            $loginWithExtension = !$account->isDisableClientPasswordAccess() && ($loginWithExtension || (in_array($account->getProviderid()->getAutologin(), [AUTOLOGIN_EXTENSION, AUTOLOGIN_MIXED]) && $account->getProviderid()->isAutologinV3()));

            if ($allowAutologin) {
                if (!$authorizationChecker->isGranted('CLIENT_PASSWORD_ACCESS')) {
                    $logger->warning("missing referer on password request");
                }
                $securityLogger->info("password access for autologin, accountId: {$id}, userId: {$tokenStorage->getBusinessUser()->getUserid()}", ["provider" => $params["providerCode"], "isSecure" => $request->isSecure(), "loginWithExtension" => $loginWithExtension]);

                if ($account->getSavepassword() == SAVE_PASSWORD_LOCALLY) {
                    $params['password'] = $localPasswordsManager->getPassword($id);
                } else {
                    $params['password'] = $account->getPass();
                }
            }
            $params['properties'] = array_merge($params['properties'], $this->loadProperties($entityManager->getConnection(), $id, null));
        }

        // get frame url
        if ($mode == "download") {
            $frameURL = "/account/downloadFile.php?ID=" . $id . "&deal=" . urlencode($request->query->get("deal")) . "&coupon=" . urlencode($request->query->get("coupon"));
        } else {
            /** @var Account $account */
            if (empty($id)) {
                $frameURL = "about:blank";
            } else {
                $account = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($id);
                $provider = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->findOneBy(['code' => $params['providerCode']]);
                $frameURL = '//' . $request->getHttpHost() . $updaterEngine->getRedirectFrameUrl($account, $tokenStorage->getBusinessUser(), $provider);
            }
        }

        if ($request->query->has('SubAccountID')) {
            $props = $this->loadProperties($entityManager->getConnection(), $id, intval($request->query->get('SubAccountID')));

            if (isset($props['RedirectURL'])) {
                $request->query->set('Goto', $props['RedirectURL']);
                $session->set('RedirectTo' . $id, $props['RedirectURL']);
            }
        }

        if ($request->query->has('Goto')) {
            $frameURL .= "&Goto=" . urlencode($request->query->get('Goto'));
        }

        $onLoad = "startRedirecting()";

        if ($loginWithExtension) {
            $onLoad = "initExtension()";
        }

        if (isset($allowAutologin) && isset($redirectOnly) && !$allowAutologin && $redirectOnly && AUTOLOGIN_DISABLED !== $provider->getAutologin()) {
            $onLoad = 'location.replace("' . htmlspecialchars($provider->getLoginurl(), ENT_QUOTES) . '");';
        }

        $askLocalPassword = !empty($account) && $account->getSavepassword() == SAVE_PASSWORD_LOCALLY && empty($params['password']);

        if (!isset($provider)) {
            throw new BadRequestHttpException();
        }

        $programName = preg_replace("/(\(.*\))/", "<span class='silver'>$1</span>", $provider->getDisplayname());

        // TODO: remove this after proper mobile autologin implementation
        if (1 === (int) $request->query->get('fromApp')) {
            $onLoad = "processRedirect()";
            //            $mode = 'download';
            $loginWithExtension = false;
        }

        if (!empty($account) && true === $loginWithExtension && 26 === $provider->getProviderid()) { // refs #16395 | +Account/ExtensionController.php:L319
            $answers = $answerHelper->getAnswers($account, ['js' => true, 'questionAsKey' => true]);

            if (!empty($answers)) {
                $params['answers'] = $answers;
            }
        }

        $securityLogger->info("autologin, accountId: {$id}, userId: {$tokenStorage->getBusinessUser()->getUserid()}");
        $response = $this->render("@AwardWalletMain/Account/Autologin/redirect.html.twig", [
            "params" => $params,
            "loginWithExtension" => $loginWithExtension,
            "mode" => $mode,
            "displayName" => $provider->getDisplayname(),
            "providerName" => $provider->getName(),
            "autologin" => $provider->getAutologin() != AUTOLOGIN_DISABLED,
            "askLocalPassword" => $askLocalPassword,
            "login" => !empty($account) ? $account->getLogin() : null,
            "userName" => !empty($account) ? $account->getOwnerFullName() : null,
            "frameURL" => $frameURL,
            "onLoad" => $onLoad,
            "programName" => $programName,
            "loginUrl" => $provider->getLoginurl(),
        ]);

        foreach ($cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/redirect.php", name="aw_account_old_redirect")
     */
    public function oldRedirectAction(Request $request, RouterInterface $router)
    {
        return new RedirectResponse($router->generate("aw_account_redirect", $request->query->all()), 301);
    }

    private function loadProperties(Connection $connection, $accountId, $subAccountId)
    {
        $params['accountId'] = $accountId;
        $sql = "
        select 
            pp.Code, 
            ap.Val
        from 
            AccountProperty ap, 
            ProviderProperty pp 
        where 
            ap.ProviderPropertyID = pp.ProviderPropertyID
            and ap.AccountID = :accountId";

        if (!empty($subAccountId)) {
            $sql .= " and (ap.SubAccountID = :subAccountId or ap.SubAccountID is null)";
            $params["subAccountId"] = $subAccountId;
        } else {
            $sql .= " and ap.SubAccountID is null";
        }

        return $connection->executeQuery($sql, $params)->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
