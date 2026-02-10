<?php

namespace AwardWallet\MobileBundle\Controller\Profile;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Configuration\Reauthentication;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticatorHandler;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationException;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/2factor")
 */
class TwoFactorAuthenticationController extends AbstractController
{
    private const REAUTH_ACTION = 'mobile_two_factor_setup_action';

    private AwTokenStorageInterface $awTokenStorage;
    private TwoFactorAuthenticationService $twoFactorAuthentication;
    private MobileReauthenticatorHandler $mobileReauthenticatorHandler;
    private AuthorizationCheckerInterface $authChecker;

    public function __construct(
        AwTokenStorageInterface $awTokenStorage,
        TwoFactorAuthenticationService $twoFactorAuthentication,
        MobileReauthenticatorHandler $mobileReauthenticatorHandler,
        AuthorizationCheckerInterface $authChecker
    ) {
        $this->awTokenStorage = $awTokenStorage;
        $this->twoFactorAuthentication = $twoFactorAuthentication;
        $this->mobileReauthenticatorHandler = $mobileReauthenticatorHandler;
        $this->authChecker = $authChecker;
    }

    /**
     * @Route("/init",
     *     name="awm_profile_2factor_init",
     *     methods={"GET", "POST"}
     * )
     * @IsGranted("CSRF")
     * @JsonDecode
     */
    public function initAction(Request $request): Response
    {
        $user = $this->awTokenStorage->getUser();

        if (!$user->twoFactorAllowed()) {
            return $this->json(['success' => true]);
        }

        if ($user->enabled2Factor()) {
            return $this->json(['success' => true]);
        }

        $twoFactorSecret = $this->twoFactorAuthentication->generateSecret();

        if (
            $request->isMethod('POST')
            && StringUtils::isNotEmpty($code = $request->get('code'))
            && StringUtils::isNotEmpty($secret = $request->get('secret'))
        ) {
            if ($this->authChecker->isGranted('USER_IMPERSONATED')) {
                throw new ImpersonatedException();
            }

            $reauthReponse = $this->mobileReauthenticatorHandler->handle(
                self::REAUTH_ACTION,
                $request,
                [],
                false
            );

            if ($reauthReponse) {
                return $reauthReponse;
            }

            $session = $request->getSession();

            try {
                $recoveryCode = $this->twoFactorAuthentication->storeCheckpoint(
                    $user,
                    $secret,
                    $code
                );
            } catch (TwoFactorAuthenticationException $e) {
                return $this->json(['error' => $e->getMessage()]);
            }

            $session->set("2fact.confirm", [
                'recovery' => $recoveryCode,
                'secret' => $secret,
            ]);

            try {
                $this->twoFactorAuthentication->saveTwoFactorCredentials($user, $secret);
            } catch (TwoFactorAuthenticationException $e) {
                return $this->json(['error' => $e->getMessage()]);
            } finally {
                $this->mobileReauthenticatorHandler->reset(self::REAUTH_ACTION);
            }

            $session->remove('2fact.confirm');

            return $this->json([
                'success' => true,
                'recovery' => $recoveryCode,
            ]);
        }

        return $this->json([
            'secret' => $twoFactorSecret,
            'secret_formatted' => self::formatSecret($twoFactorSecret),
        ]);
    }

    /**
     * @Route("/confirm", name="awm_profile_2factor_confirm", methods={"POST"})
     * @IsGranted("CSRF")
     * @IsGranted("NOT_USER_IMPERSONATED")
     */
    public function confirmAction(Request $request)
    {
        throw new BadRequestHttpException();
    }

    /**
     * @Route("", name="awm_profile_2factor_cancel", methods={"DELETE"})
     * @IsGranted("CSRF")
     * @IsGranted("NOT_USER_IMPERSONATED")
     * @Reauthentication
     */
    public function cancelAction()
    {
        $user = $this->awTokenStorage->getUser();

        if (!$user->enabled2Factor()) {
            return $this->json(['success' => true]);
        }

        $this->twoFactorAuthentication->cancelTwoFactor($user);

        return $this->json(['success' => true]);
    }

    protected static function formatSecret(string $secret): string
    {
        if (strlen($secret) % 4 !== 0) {
            return $secret;
        }

        return it(\str_split($secret, 4))->joinToString(" ");
    }
}
