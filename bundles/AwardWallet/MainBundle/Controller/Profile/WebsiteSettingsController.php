<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\TwoFactorAuthType;
use AwardWallet\MainBundle\Form\Type\WebsiteSettingsType;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationException;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use AwardWallet\MainBundle\Service\ThemeResolver;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/")
 */
class WebsiteSettingsController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route("/user/settings", name="aw_profile_settings")
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Template("@AwardWalletMain/Profile/WebsiteSettings/index.html.twig")
     */
    public function indexAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        RouterInterface $router,
        SessionInterface $session,
        TranslatorInterface $translator,
        ThemeResolver $themeResolver
    ) {
        $userProfileWidget->setActiveItem('websettings');
        $user = $this->getUser();
        $form = $this->createForm(WebsiteSettingsType::class, $user);
        $form->get('appearance')->setData($themeResolver->getCurrentTheme() ?? '');
        $form->handleRequest($request);
        $response = new Response();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->checkImpersonation();
            $response = $this->redirect($router->generate('aw_profile_overview'));

            $response->headers->clearCookie('rkbtyn', '/blog');
            $response->headers->clearCookie('refCode', '/blog'); // deprecated, remove after 02/02/24
            $response->headers->clearCookie('user', '/blog');

            $response->headers->setCookie(
                AwCookieFactory::createLax(
                    ThemeResolver::COOKIE_NAME,
                    $form->get('appearance')->getData(),
                    time() + 2 * 365 * SECONDS_PER_DAY
                )
            );

            $this->entityManager->flush();
            $session->getFlashBag()->add(
                'notice',
                $translator->trans(/** @Desc("AwardWallet website settings have been updated") */ 'edit-website-settings.success')
            );

            return $response;
        }

        return $this->render('@AwardWalletMain/Profile/WebsiteSettings/index.html.twig', [
            'form' => $form->createView(),
        ], $response);
    }

    /**
     * @Route("/user/settings/2fact/setup", name="aw_profile_2factor", options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Template("@AwardWalletMain/Profile/WebsiteSettings/twoFactor.html.twig")
     */
    public function twoFactorAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        RouterInterface $router,
        TwoFactorAuthenticationService $twoFactService,
        ReauthenticatorWrapper $reauth,
        SessionInterface $session
    ) {
        /** @var Usr $user */
        $user = $this->getUser();

        if (!$user->twoFactorAllowed()) {
            return [
                'isNeedSetPassword' => true,
            ];
        }

        if ($user->enabled2Factor()) {
            return $this->redirect($router->generate('aw_profile_2factor_cancel'));
        }

        // left menu
        $userProfileWidget->setActiveItem('websettings');

        // secret string
        $secret = $twoFactService->generateSecret();

        $form = $this->createForm(TwoFactorAuthType::class, [
            'secret' => $secret,
        ]);
        $form->handleRequest($request);

        $flashBag = $session->getFlashBag();

        // confirm errors
        if ($flashBag->has('error')) {
            foreach ($flashBag->get('error') as $error) {
                $form->addError(new FormError($error));
            }
        }

        if (
            $reauth->isReauthenticated(Action::get2FactSetupAction())
            && ($form->isSubmitted() && $form->isValid())
        ) {
            $this->checkImpersonation();
            $data = $form->getData();

            try {
                $recoveryCode = $twoFactService->storeCheckpoint($user, $data['secret'], $data['code']);
                $session->set("2fact.confirm", [
                    'recovery' => $recoveryCode,
                    'secret' => $data['secret'],
                ]);

                return $this->redirect($router->generate('aw_profile_2factor_confirm'));
            } catch (TwoFactorAuthenticationException $e) {
                $form->get('code')->addError(new FormError($e->getMessage()));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/user/settings/2fact/confirm", name="aw_profile_2factor_confirm")
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Template("@AwardWalletMain/Profile/WebsiteSettings/twoFactorConfirm.html.twig")
     */
    public function twoFactorConfirmAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        RouterInterface $router,
        TwoFactorAuthenticationService $twoFactService,
        ReauthenticatorWrapper $reauth,
        TranslatorInterface $trans,
        SessionInterface $session
    ) {
        /** @var Usr $user */
        $user = $this->getUser();

        if (!$user->twoFactorAllowed()) {
            return $this->redirect($router->generate('aw_profile_overview'));
        }

        if ($user->enabled2Factor()) {
            return $this->redirect($router->generate('aw_profile_2factor_cancel'));
        }

        if (!$session->has('2fact.confirm')) {
            throw $this->createAccessDeniedException();
        }

        // left menu
        $userProfileWidget->setActiveItem('websettings');
        $data = $session->get('2fact.confirm');
        // get empty form with csrf
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->checkImpersonation();

            if (!$reauth->isReauthenticated(Action::get2FactSetupAction())) {
                $session->getFlashBag()->add('error', $trans->trans('error.auth.two-factor.password.expired'));

                return $this->redirect($router->generate('aw_profile_2factor'));
            }

            try {
                $twoFactService->saveTwoFactorCredentials($user, $data['secret']);
                $reauth->reset(Action::get2FactSetupAction());
            } catch (TwoFactorAuthenticationException $e) {
                $session->getFlashBag()->add('error', $e->getMessage());

                return $this->redirect($router->generate('aw_profile_2factor'));
            }
            $session->remove('2fact.confirm');
            $session->getFlashBag()->add(
                'notice',
                $trans->trans('two-fact.setup-complete')
            );

            return $this->redirect($router->generate('aw_profile_overview'));
        }

        return [
            'form' => $form->createView(),
            'recoverCode' => $data['recovery'],
        ];
    }

    /**
     * @Route("/user/settings/2fact/cancel", name="aw_profile_2factor_cancel")
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Template("@AwardWalletMain/Profile/WebsiteSettings/twoFactorCancel.html.twig")
     */
    public function twoFactorCancelAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        RouterInterface $router,
        TwoFactorAuthenticationService $twoFactService,
        ReauthenticatorWrapper $reauth,
        TranslatorInterface $trans,
        SessionInterface $session
    ) {
        /** @var Usr $user */
        $user = $this->getUser();

        if (!$user->twoFactorAllowed()) {
            return $this->redirect($router->generate('aw_profile_overview'));
        }

        if (!$user->enabled2Factor()) {
            return $this->redirect($router->generate('aw_profile_2factor'));
        }

        // left menu
        $userProfileWidget->setActiveItem('websettings');
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if (
            $reauth->isReauthenticated(Action::get2FactCancelAction())
            && ($form->isSubmitted() && $form->isValid())
        ) {
            $this->checkImpersonation();

            try {
                $twoFactService->cancelTwoFactor($user);
                $session->getFlashBag()->add(
                    'notice',
                    $trans->trans('two-fact.cancel-complete')
                );
                $reauth->reset(Action::get2FactCancelAction());

                return $this->redirect($router->generate('aw_profile_overview'));
            } catch (TwoFactorAuthenticationException $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/2factorauth/disableByManager", name="aw_2factorauth_disable", methods={"POST"})
     * @Security("is_granted('CSRF') and is_granted('ROLE_STAFF_ASSISTANT') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function disableByManagerAction(Request $request, UsrRepository $usrRepository)
    {
        $userIds = explode(',', $request->get('selected'));

        if (count($userIds)) {
            foreach ($userIds as $id) {
                /** @var Usr $user */
                if ($user = $usrRepository->find($id)) {
                    $user->setGoogleAuthSecret(null);
                    $user->setGoogleAuthRecoveryCode(null);
                    $this->entityManager->persist($user);
                }
            }

            $this->entityManager->flush();
        }

        return new JsonResponse('Ok');
    }

    /**
     * @throws ImpersonatedException
     */
    private function checkImpersonation()
    {
        if ($this->authorizationChecker->isGranted('USER_IMPERSONATED')) {
            throw new ImpersonatedException();
        }
    }
}
