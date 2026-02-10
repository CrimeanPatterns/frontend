<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class VisitController
{
    private PageVisitLogger $pageVisitLogger;

    public function __construct(PageVisitLogger $pageVisitLogger)
    {
        $this->pageVisitLogger = $pageVisitLogger;
    }

    /**
     * Adding a new visit from the mobile app.
     *
     * @Route("/visits", name="awm_api_visits_create", methods={"POST"})
     * @JsonDecode()
     */
    public function create(Request $request): JsonResponse
    {
        $screenName = $request->request->get('screenName');

        if (StringHandler::isEmpty($screenName)) {
            throw new BadRequestHttpException();
        }

        $this->pageVisitLogger->logFromMobile($screenName);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Adding a new visit for the blog only.
     *
     * @Route("/visits-blog", name="awm_api_visits_create_blog", methods={"POST"})
     * @JsonDecode()
     */
    public function createFromBlog(Request $request): JsonResponse
    {
        $refCode = $request->request->get('refCode');
        $screenName = $request->request->get('screenName');

        if (StringHandler::isEmpty($refCode) && $screenName !== PageVisitLogger::PAGE_BLOG) {
            throw new BadRequestHttpException();
        }

        $this->pageVisitLogger->logFromMobile($screenName, $refCode);

        return new JsonResponse(['success' => true]);
    }
}
