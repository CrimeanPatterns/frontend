<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdateController extends AbstractController
{
    private TranslatorInterface $translator;

    public function __construct(
        LocalizeService $localizeService,
        TranslatorInterface $translator
    ) {
        $localizeService->setRegionalSettings();
        $this->translator = $translator;
    }

    /**
     * @Route("/account/update", name="awm_newapp_update_start", methods={"POST"})
     * @return JsonResponse
     */
    public function startUpdateAction(Request $request)
    {
        return $this->createOutdatedResponse();
    }

    /**
     * @Route("/account/update/{key}",
     *      name="awm_newapp_update_progress",
     *      methods={"POST"},
     *      requirements = {
     *          "key": "[a-z]+"
     *      }
     * )
     * @return JsonResponse
     */
    public function getUpdateProgressAction(Request $request, $key)
    {
        return $this->createOutdatedResponse();
    }

    /**
     * @Route("/account/update/question/{accountId}/{key}",
     *      name="awm_newapp_update_security_cancel",
     *      methods={"DELETE"},
     *      requirements={
     *          "accountId" : "\d+",
     *          "key" : "[a-z]+"
     *      }
     * )
     * @Security("is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @return JsonResponse
     */
    public function securityQuestionCancelAction($accountId, $key)
    {
        return $this->createOutdatedResponse();
    }

    /**
     * @Route("/account/update/question/{accountId}/{key}",
     *      name="awm_newapp_update_security_answer",
     *      methods={"GET", "POST"},
     *      requirements={
     *          "accountId": "\d+",
     *          "key": "[a-z]+"
     *      }
     * )
     * @return JsonResponse
     */
    public function securityQuestionAction(Request $request, $accountId, $key)
    {
        return $this->createOutdatedResponse();
    }

    /**
     * @Route("/account/update/password/{accountId}/{key}",
     *      name="awm_newapp_update_local_password_cancel",
     *      methods={"DELETE"},
     *      requirements={
     *          "accountId" : "\d+",
     *          "key" : "[a-z]+"
     *      }
     * )
     * @Security("is_granted('CSRF')")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function cancelLocalPasswordAction(Request $request, Account $account, $key = null)
    {
        return $this->createOutdatedResponse();
    }

    /**
     * @Route("/account/update/extension/{accountId}/{key}",
     *      name="awm_newapp_update_extension_cancel",
     *      methods={"DELETE"},
     *      requirements={
     *          "accountId" : "\d+",
     *          "key" : "[a-z]+"
     *      }
     * )
     * @Security("is_granted('CSRF')")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function cancelExtensionAction(Request $request, Account $account, $key)
    {
        return $this->createOutdatedResponse();
    }

    protected function createOutdatedResponse()
    {
        $msg = $this->translator->trans('outdated.text', [], 'mobile');
        $response = new JsonResponse(['error' => $msg]);
        $response->headers->set(MobileHeaders::MOBILE_VERSION, $msg);

        return $response;
    }
}
