<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Timeline\Manager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/awardBooking/timeline")
 */
class TimelineController extends AbstractController
{
    private EntityManagerInterface $em;

    private RouterInterface $router;

    private TranslatorInterface $translator;

    private Manager $timelineManager;

    private AuthorizationCheckerInterface $checker;

    public function __construct(
        EntityManagerInterface $em,
        RouterInterface $router,
        TranslatorInterface $translator,
        Manager $timelineManager,
        AuthorizationCheckerInterface $checker
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->translator = $translator;
        $this->timelineManager = $timelineManager;
        $this->checker = $checker;
    }

    /**
     * @Route("/add-plan/{id}", methods={"POST"}, name="aw_booking_timeline_addplan", options={"expose"=true}, requirements={"id" = "\d+"})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @Security("is_granted('CSRF') and is_granted('SITE_BUSINESS_AREA') and is_granted('VIEW', abRequest) and is_granted('BOOKER', abRequest)")
     */
    public function addPlanAction(AbRequest $abRequest, Request $request, AwTokenStorage $tokenStorage)
    {
        $url = $request->request->get('url');

        if (!is_string($url) || empty($url)) {
            return new JsonResponse(['error' => 'Url is required']);
        }

        $tripRequest = Request::create($url);
        $context = $this->router->getContext();
        $urlMatcher = new UrlMatcher(
            $this->router->getRouteCollection(),
            new RequestContext(
                $context->getBaseUrl(),
                'GET',
                $context->getHost(),
                $context->getScheme()
            )
        );

        try {
            $params = $urlMatcher->matchRequest($tripRequest);

            if (isset($params['_route'])) {
                if (in_array($params['_route'], ['aw_travelplan_shared', 'aw_travelplan_shared_locale'])) {
                    $parts = $this->timelineManager->decodeShareCode($params['shareCode']);

                    if (count($parts) === 3 && $parts[0] === 'Travelplan' && is_numeric($parts[1]) && is_string($parts[2])) {
                        /** @var Plan $plan */
                        $plan = $this->em->getRepository(Plan::class)->find($parts[1]);

                        if ($plan && $this->checker->isGranted('EDIT', $plan)) {
                            $abRequest->setTravelPlan($plan);
                            $this->em->flush();

                            return new JsonResponse([
                                'success' => true,
                                'iframe' => $this->router->generate('aw_travelplan_shared', [
                                    'shareCode' => $plan->getEncodedShareCode(),
                                ]),
                            ]);
                        } else {
                            if ($business = $tokenStorage->getBusinessUser()) {
                                $ua = $this->em->getRepository(Useragent::class)->findOneBy(['clientid' => $abRequest->getUser(), 'agentid' => $business]);

                                if ($ua) {
                                    return $this->getAccessDeniedResponse($abRequest->getUser()->getFullName(), $ua);
                                }
                            }
                        }
                    }
                } elseif (in_array($params['_route'], ['aw_timeline_shared', 'aw_timeline_shared_locale'])) {
                    return $this->getInvalidURLTypeResponse();
                }
            }
        } catch (NoConfigurationException|ResourceNotFoundException|MethodNotAllowedException $e) {
        }

        return $this->getInvalidTravelPlanURLResponse();
    }

    /**
     * @Route("/remove-plan/{id}", methods={"POST"}, name="aw_booking_timeline_removeplan", options={"expose"=true}, requirements={"id" = "\d+"})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @Security("is_granted('CSRF') and is_granted('SITE_BUSINESS_AREA') and is_granted('VIEW', abRequest) and is_granted('BOOKER', abRequest)")
     */
    public function removePlanAction(AbRequest $abRequest, Request $request)
    {
        $abRequest->setTravelPlan(null);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    private function getInvalidTravelPlanURLResponse(): JsonResponse
    {
        return new JsonResponse([
            'error' => $this->translator->trans(
                /** @Desc("Invalid trip URL, please copy the sharing URL of the trip you created one more time.") */ 'invalid-travel-plan-url', [], 'validators'
            ),
        ]);
    }

    private function getInvalidURLTypeResponse(): JsonResponse
    {
        return new JsonResponse([
            'error' => $this->translator->trans(
                /** @Desc("You are providing a sharing link to a specific trip segment, instead, you need to create a trip and share a link to that trip.") */ 'invalid-type-travel-plan-url', [], 'validators'
            ),
        ]);
    }

    private function getAccessDeniedResponse(string $userName, Useragent $useragent): JsonResponse
    {
        return new JsonResponse([
            'error' => $this->translator->trans(
                /** @Desc("The trip that you are trying to add belongs to another user, you should make sure that the trip you are adding belongs to the %link_on%timeline of %user_name%%link_off%") */ 'access-denied-travel-plan', [
                                    '%link_on%' => '<a target="_blank" href="' . $this->router->generate('aw_timeline_html5', ['agentId' => $useragent->getId()]) . '">',
                                    '%link_off%' => '</a>',
                                    '%user_name%' => $userName,
                                ], 'validators'
            ),
        ]);
    }
}
