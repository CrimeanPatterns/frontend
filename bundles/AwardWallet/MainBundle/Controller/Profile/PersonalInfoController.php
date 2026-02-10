<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\Controller\HomeController;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Type\ChangeUserEmailType;
use AwardWallet\MainBundle\Form\Type\ChangeUserPasswordType;
use AwardWallet\MainBundle\Form\Type\ProfilePersonalType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonalInfoController extends AbstractController
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private AwTokenStorageInterface $tokenStorage;
    private SessionInterface $session;
    private RouterInterface $router;
    private TranslatorInterface $translator;
    private ReauthenticatorWrapper $reauthenticator;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorageInterface $tokenStorage,
        SessionInterface $session,
        RouterInterface $router,
        TranslatorInterface $translator,
        ReauthenticatorWrapper $reauthenticator
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
        $this->router = $router;
        $this->translator = $translator;
        $this->reauthenticator = $reauthenticator;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/user/personal", name="aw_profile_personal", options={"expose"=true})
     * @Template("@AwardWalletMain/Profile/PersonalInfo/index.html.twig")
     * @return array|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        Handler $profilePersonalHandlerDesktop
    ) {
        if (
            $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')
            && !$this->authorizationChecker->isGranted('BUSINESS_ACCOUNTS')
        ) {
            throw new AccessDeniedException();
        }

        $userProfileWidget->setActiveItem('personal');

        $user = $this->tokenStorage->getToken()->getUser();
        $form = $this->createForm(ProfilePersonalType::class, $user);

        if ($profilePersonalHandlerDesktop->handleRequest($form, $request)) {
            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    /** @Desc("You have successfully changed your personal info") */
                    'notice.personal-success-changed'
                )
            );

            return $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA') ?
                $this->redirect($this->router->generate('aw_profile_overview_business')) :
                $this->redirect($this->router->generate('aw_profile_overview'));
        }

        return [
            'form' => $form->createView(),
            'user' => $user,
        ];
    }

    /**
     * @Route("/user/change-email", name="aw_user_change_email")
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Profile/PersonalInfo/changeEmail.html.twig")
     */
    public function changeEmailAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        Handler $profileEmailHandlerDesktop
    ) {
        $userProfileWidget->setActiveItem('personal');
        $action = Action::getChangeEmailAction();

        $form = $this->createForm(ChangeUserEmailType::class, $this->tokenStorage->getToken()->getUser(), ['reauthRequired' => false]);

        if (
            $this->reauthenticator->isReauthenticated($action)
            && $profileEmailHandlerDesktop->handleRequest($form, $request)
        ) {
            $this->reauthenticator->reset($action);
            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    /** @Desc("You have successfully changed your email address") */
                    'notice.email-success-changed'
                )
            );

            return $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA') ?
                $this->redirect($this->router->generate('aw_profile_overview_business')) :
                $this->redirect($this->router->generate('aw_profile_overview'));
        }

        $formView = $form->createView();

        return [
            'form' => $formView,
        ];
    }

    /**
     * @Route("/user/change-password", name="aw_profile_change_password", options={"expose"=true})
     * @Route("/{_locale}/user/change-password", name="aw_profile_change_password_locale", defaults={"_locale"="en"}, requirements={"_locale" = "%route_locales%"})
     * @Route("/user/change-password-feedback/{id}/{code}", name="aw_profile_change_password_feedback", requirements={"id" = "\d+", "code" = "[0-9a-f]{32}"})
     * @Template("@AwardWalletMain/Profile/PersonalInfo/changePassword.html.twig")
     */
    public function changePasswordAction(
        $id = null,
        $code = null,
        Request $request,
        UserProfileWidget $userProfileWidget,
        HomeController $homeController,
        Handler $profilePasswordHandlerDesktop
    ) {
        $userProfileWidget->setActiveItem('personal');
        $isAuth = $this->authorizationChecker->isGranted('ROLE_USER');

        $needReauth = false;

        $validResetLink = false;

        if (!empty($id) && !empty($code)) {
            $user = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($id);

            if (
                $user
                && $user->getResetpasswordcode() == $code
                && (new \DateTime())->diff($user->getResetpassworddate())->d < 3
            ) {
                $validResetLink = true;
            }
        }

        if (!$isAuth && !empty($id)) {
            if ($validResetLink) {
            } else {
                return [];
            }
        } elseif ($isAuth) {
            if ($validResetLink) {
                if ($id != $this->getUser()->getUserid()) {
                    $this->session->invalidate();
                    $response = new RedirectResponse($request->getPathInfo());
                    $response->headers->removeCookie("PwdHash");

                    return $response;
                }
            }

            if (!empty($id)) {
                return new RedirectResponse($this->generateUrl("aw_users_logout", ["BackTo" => $request->getRequestUri()]));
            }
            $needReauth = true;
            $user = $this->getUser();
        } else {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ChangeUserPasswordType::class, $user);
        $action = Action::getChangePasswordAction();

        if (
            (!$needReauth || $this->reauthenticator->isReauthenticated($action))
            && $profilePasswordHandlerDesktop->handleRequest($form, $request)
        ) {
            if ($needReauth) {
                $this->reauthenticator->reset($action);
            }

            if (!empty($id)) {
                return $this->redirect($this->router->generate('aw_login'));
            }

            if ($request->query->has("backTo")) {
                return $this->redirect(
                    $request->getSchemeAndHttpHost() . $request->query->get("backTo")
                );
            } else {
                $this->session->getFlashBag()->add(
                    'notice',
                    $this->translator->trans(
                        /** @Desc("You have successfully changed your password") */
                        'notice.pass-success-changed'
                    )
                );

                return $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA') ?
                    $this->redirect($this->router->generate('aw_profile_overview_business')) :
                    $this->redirect($this->router->generate('aw_profile_overview'));
            }
        }

        return [
            'form' => $form->createView(),
            'user' => $user,
            'load_external_scripts' => false,
            'needReauth' => $needReauth,
        ];
    }
}
