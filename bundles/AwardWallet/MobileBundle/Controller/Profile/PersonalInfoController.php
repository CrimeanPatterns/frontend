<?php

namespace AwardWallet\MobileBundle\Controller\Profile;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Configuration\Reauthentication;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MobileBundle\Form\Type\Profile\ChangeEmailType;
use AwardWallet\MobileBundle\Form\Type\Profile\ChangePasswordType;
use AwardWallet\MobileBundle\Form\Type\Profile\PersonalType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonalInfoController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/personal", name="aw_mobile_personal_info", methods={"GET", "PUT"})
     * @Security("is_granted('ROLE_USER')")
     * @JsonDecode
     */
    public function personalAction(
        Request $request,
        Handler $awFormProfilePersonalHandlerMobile,
        TranslatorInterface $translator,
        FormDehydrator $formDehydrator
    ) {
        $form = $this->createForm(
            PersonalType::class,
            $this->getCurrentUser(),
            ['method' => 'PUT']
        );

        if ($awFormProfilePersonalHandlerMobile->handleRequest($form, $request)) {
            return new JsonResponse([
                'needUpdate' => true,
                'success' => true,
            ]);
        }

        return new JsonResponse(
            array_merge(
                ['formTitle' => $translator->trans('user.personal.title')],
                $formDehydrator->dehydrateForm($form)
            )
        );
    }

    /**
     * @Route("/changePassword", name="aw_mobile_change_password", methods={"GET", "PUT"})
     * @Security("is_granted('ROLE_USER')")
     * @Reauthentication(methods={"PUT"}, autoReset=false, checkDeviceSupport=true)
     * @JsonDecode
     */
    public function changePasswordAction(
        Request $request,
        ApiVersioningService $apiVersioningService,
        Handler $awFormProfilePasswordHandlerMobile,
        FormDehydrator $formDehydrator,
        TranslatorInterface $translator
    ) {
        $form = $this->createForm(ChangePasswordType::class, $this->getCurrentUser(), [
            'method' => 'PUT',
            'client_ip' => $request->getClientIp(),
            'user_login' => $this->getCurrentUser()->getLogin(),
            'type_old_password' => $apiVersioningService->notSupports(MobileVersions::LOGIN_OAUTH),
        ]);

        if ($awFormProfilePasswordHandlerMobile->handleRequest($form, $request)) {
            return new JsonResponse([
                'needUpdate' => true,
                'success' => true,
            ]);
        }

        return new JsonResponse(
            array_merge(
                $formDehydrator->dehydrateForm($form, false),
                ['formTitle' => $translator->trans('user.change-password.form.title')]
            )
        );
    }

    /**
     * @Route("/forgotPassword", name="aw_mobile_forgot_action", methods={"POST"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function forgotPasswordAction(Request $request, UserManager $userManager, TranslatorInterface $translator)
    {
        $user = $this->getCurrentUser();
        $result = $userManager->sendRestorePasswordEmail($request, $user->getLogin());

        return new JsonResponse([
            'success' => true === $result,
            'message' => true === $result ? $translator->trans('landing.dialog.forgot.success_header') : $result,
        ]);
    }

    /**
     * @Route("/changeEmail", name="aw_mobile_change_email", methods={"GET", "PUT"})
     * @Security("is_granted('ROLE_USER')")
     * @JsonDecode
     * @Reauthentication(methods={"PUT"}, autoReset=false, checkDeviceSupport=true)
     * @return JsonResponse
     */
    public function changeEmailAction(
        Request $request,
        Handler $awFormProfileEmailHandlerMobile,
        FormDehydrator $formDehydrator,
        TranslatorInterface $translator
    ) {
        $form = $this->createForm(
            ChangeEmailType::class,
            $this->getCurrentUser(),
            [
                'user_login' => $this->getCurrentUser()->getLogin(),
                'client_ip' => $request->getClientIp(),
                'method' => 'PUT',
            ]
        );

        if ($awFormProfileEmailHandlerMobile->handleRequest($form, $request)) {
            return new JsonResponse([
                'needUpdate' => true,
                'success' => true,
            ]);
        }

        return new JsonResponse(
            array_merge(
                $formDehydrator->dehydrateForm($form),
                ['formTitle' => $translator->trans('user.change-email.form.title')]
            )
        );
    }

    /**
     * @Route("/sendEmail", name="aw_mobile_send_verification_email", methods={"POST"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @return JsonResponse
     */
    public function sendEmail(UserManager $userManager)
    {
        $userManager->sendVerificationMail($this->getUser());

        return new JsonResponse(['success' => true]);
    }
}
