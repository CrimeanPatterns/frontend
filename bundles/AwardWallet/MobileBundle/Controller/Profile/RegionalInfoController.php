<?php

namespace AwardWallet\MobileBundle\Controller\Profile;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Model\Profile\RegionalModel;
use AwardWallet\MainBundle\Form\Type\Mobile\Profile\RegionalType;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegionalInfoController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/regional", name="aw_mobile_regional_info")
     * @JsonDecode
     */
    public function regionalInfoAction(
        Request $request,
        Handler $awFormProfileRegionalHandlerMobile,
        FormDehydrator $formDehydrator,
        TranslatorInterface $translator
    ) {
        $user = $this->getCurrentUser();
        $form = $this->createForm(RegionalType::class, $user, ['method' => 'PUT']);

        if ($awFormProfileRegionalHandlerMobile->handleRequest($form, $request)) {
            /** @var RegionalModel $model */
            $model = $form->getData();

            return new JsonResponse([
                'needUpdate' => $model->isModelChanged(),
                'language' => $this->getCurrentUser()->getLanguage(),
                'success' => true,
            ]);
        }

        return new JsonResponse(array_merge(
            $formDehydrator->dehydrateForm($form),
            ['formTitle' => $translator->trans('personal_info.regional')]
        ));
    }
}
