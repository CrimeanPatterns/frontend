<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OptionsController extends AbstractController
{
    /**
     * @Route("/user/options/upgrade-popup-skip", name="aw_options_user_upgrade_popup_skip", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function skipUpgradePopup(
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker,
        PlusManager $plusManager
    ): JsonResponse {
        if ($authorizationChecker->isGranted('USER_IMPERSONATED')) {
            throw new ImpersonatedException();
        }
        $request->getSession()->remove(PlusManager::SESSION_KEY_SHOW_UPGRADE_POPUP);

        return new JsonResponse(['success' => $plusManager->incrementUpgradeSkipCount($this->getUser())]);
    }
}
