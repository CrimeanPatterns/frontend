<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MobileBundle\Form\Type\RecoverPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordRecoveryController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/recover", name="awm_newapp_recover_email")
     * @JsonDecode
     * @return Response
     */
    public function recoveryAction(
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorInterface $translator,
        UserManager $userManager,
        FormDehydrator $formDehydrator
    ) {
        $form = $this->createForm(RecoverPasswordType::class, null, ['user_ip' => $request->getClientIp()]);
        $request->request->replace([$form->getName() => $request->request->all()]);

        if ($request->isMethod('POST')) {
            $this->checkImpersonation($authorizationChecker);
            $this->checkCsrfToken($authorizationChecker);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $usrRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

                $login = $form->getData()['loginOrEmail'];
                /** @var Usr $user */
                $user = $usrRep->loadUserByUsername($login);

                if (!$user) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => $translator->trans('landing.dialog.forgot.help'),
                    ]);
                }

                $result = $userManager->sendRestorePasswordEmail($request, $user->getLogin());

                return new JsonResponse([
                    'success' => true === $result,
                    'message' => true === $result ? $translator->trans('landing.dialog.forgot.success_header') : $result,
                ]);
            }
        }

        return new JsonResponse([
            'form' => $formDehydrator->dehydrateForm($form),
        ]);
    }

    /**
     * @Route("/recover/change/{userID}/{code}",
     *      name="awm_newapp_recover_change",
     *      methods={"GET", "POST"},
     *      requirements={"userID" = "\d+", "code" = "[0-9a-f]{32}"}
     * )
     * @JsonDecode
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function changeAction(
        Request $request,
        $userID,
        $code,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        FormDehydrator $formDehydrator,
        Handler $awFormProfilePasswordHandlerMobile
    ) {
        $user = $entityManager->getRepository(Usr::class)->find($userID);

        if (!$user || ($user->getResetpasswordcode() !== $code) || ((new \DateTime())->diff($user->getResetpassworddate())->d >= 3)) {
            return new JsonResponse([
                'error' => $translator->trans('landing.page.restore.text.failure'),
            ]);
        }

        $form = $this->createForm(\AwardWallet\MobileBundle\Form\Type\Profile\ChangePasswordType::class, $user, [
            'method' => 'POST',
            'client_ip' => $request->getClientIp(),
            'user_login' => $user->getLogin(),
            'type_old_password' => false,
            'is_recovery_mode' => true,
        ]);

        if ($awFormProfilePasswordHandlerMobile->handleRequest($form, $request)) {
            return new JsonResponse([
                'success' => true,
                'message' => $translator->trans('user.password-recovery.success', ['%login%' => $user->getLogin()], 'mobile'),
            ]);
        }

        return new JsonResponse([
            'form' => $formDehydrator->dehydrateForm($form, false),
        ]);
    }
}
