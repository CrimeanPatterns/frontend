<?php

namespace AwardWallet\MobileBundle\Controller\Profile;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Form\Model\Profile\SecurityQuestionModel;
use AwardWallet\MainBundle\Form\Type\Mobile\Profile\SecurityQuestionType;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\User\SecurityQuestionHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityQuestionController extends AbstractController
{
    use ControllerTrait;

    public function __construct(LocalizeService $localizeService)
    {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/user-question", name="aw_mobile_profile_question", methods={"GET", "PUT"})
     * @Security("is_granted('ROLE_USER')")
     * @JsonDecode()
     * @return JsonResponse
     */
    public function indexAction(
        Request $request,
        SecurityQuestionHelper $helper,
        FormDehydrator $formDehydrator,
        TranslatorInterface $translator
    ) {
        $form = $this->createForm(SecurityQuestionType::class, $helper->findModel(), ['method' => 'PUT']);
        $request->request->replace([$form->getName() => $request->request->all()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SecurityQuestionModel $model */
            $model = $form->getData();
            $result = $helper->process($model);

            return new JsonResponse([
                'success' => true,
                'language' => $this->getCurrentUser()->getLanguage(),
                'needUpdate' => $result === SecurityQuestionHelper::STATUS_UPDATED,
            ]);
        }

        return new JsonResponse(array_merge(
            $formDehydrator->dehydrateForm($form, false),
            ['formTitle' => $translator->trans('personal_info.security_question')]
        ));
    }
}
