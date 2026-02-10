<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Mobilefeedback;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/feedback")
 */
class FeedbackController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/add", name="awm_newapp_feedback_add", methods={"POST"})
     * @JsonDecode()
     */
    public function add(Request $request, EntityManagerInterface $em)
    {
        try {
            $feedback = (new Mobilefeedback())
                ->setUser($this->getCurrentUser())
                ->setAction($request->get('action'))
                ->setAppversion($request->headers->get(MobileHeaders::MOBILE_VERSION))
                ->setDate(new \DateTime());
        } catch (\InvalidArgumentException $e) {
            return $this->errorJsonResponse('Undefined action');
        }

        $em->persist($feedback);
        $em->flush();

        return $this->successJsonResponse();
    }
}
