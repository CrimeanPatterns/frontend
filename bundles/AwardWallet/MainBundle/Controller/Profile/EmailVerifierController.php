<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\Event\UserEmailVerificationChangedEvent;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/")
 */
class EmailVerifierController extends AbstractController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @Route("/email/verify/send", name="aw_email_verify_send", options={"expose" = true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     */
    public function sendAction(Request $request, AntiBruteforceLockerService $securityAntibruteforceEmail)
    {
        $error = $securityAntibruteforceEmail->checkForLockout($this->getUser()->getEmail());

        if (!empty($error)) {
            return new Response($error, 400);
        }

        $this->userManager->sendVerificationMail($this->getUser());

        return new Response("OK");
    }

    /**
     * @Route("/email/verify", name="aw_email_verify")
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Template("@AwardWalletMain/Profile/EmailVerifier/verify.html.twig")
     */
    public function verifyAction(
        Request $request,
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        EventDispatcherInterface $eventDispatcher
    ) {
        $login = $request->get('login');
        $id = $request->get('id');
        $errorsLogin = $validator->validate($login, [
            new Assert\NotBlank(),
            new Assert\Type(['type' => 'string']),
        ]);
        $errorsId = $validator->validate($id, [
            new Assert\NotBlank(),
            new Assert\Type(['type' => 'string']),
        ]);

        if (count($errorsLogin) > 0 || count($errorsId) > 0) {
            return [
                'error' => $translator->trans(
                    /** @Desc("Invalid URL") */
                    "verify_email.invalid_url"
                ),
            ];
        }
        $user = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)
            ->findOneBy(['login' => $login]);

        if (!empty($user) && $user->getEmailVerificationHash() === $id) {
            $prevEmailVerified = $user->getEmailverified();
            $user->setEmailverified(EMAIL_VERIFIED);
            $this->getDoctrine()->getManager()->flush();

            if ($authorizationChecker->isGranted('ROLE_USER')) {
                $this->userManager->refreshToken();
            }

            if ($prevEmailVerified !== EMAIL_VERIFIED) {
                $eventDispatcher->dispatch(new UserEmailVerificationChangedEvent($user));
            }

            return [];
        }

        return [
            'error' => $translator->trans(
                /** @Desc("Error occurred. Most likely you came to this page using an incorrect URL.") */
                "verify_email.error"
            ),
        ];
    }
}
