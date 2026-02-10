<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\Parameter;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Review;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\CustomHeadersListener;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Blog\BlogPostInterface;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\ProviderRating;
use AwardWallet\MainBundle\Service\TransferTimes;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ProviderRatingController extends AbstractController
{
    private ProviderRating $ratingLoyaltyProgram;
    private AuthorizationCheckerInterface $authorizationChecker;
    private RouterInterface $router;
    private Environment $twig;

    public function __construct(
        ProviderRating $ratingLoyaltyProgram,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        Environment $twig
    ) {
        $this->ratingLoyaltyProgram = $ratingLoyaltyProgram;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->twig = $twig;
    }

    /**
     * @Route(
     *     "/r/{loyaltyProgramName}",
     *     name="aw_loyalty_program_rating",
     *     defaults={"_canonical" = "aw_loyalty_program_rating", "_alternate" = "aw_loyalty_program_rating_locale"},
     *     options={"expose"=true}
     * )
     * @Route("/{_locale}/r/{loyaltyProgramName}", name="aw_loyalty_program_rating_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_loyalty_program_rating", "_canonical_clean"="_locale"})
     * @return Response|RedirectResponse
     */
    public function ratingAction(
        Request $request,
        $_locale = 'en',
        $loyaltyProgramName = null,
        Counter $counter,
        EntityManagerInterface $entityManager,
        BlogPostInterface $blogPost,
        TransferTimes $transferTimes,
        MileValueService $mileValueService,
        TranslatorInterface $translator
    ) {
        if (!$this->authorizationChecker->isGranted('NOT_SITE_BUSINESS_AREA')) {
            throw new NotFoundHttpException();
        }

        if (empty($loyaltyProgramName)) {
            return $this->redirect($this->router->generate('aw_supported'));
        }

        $result = $this->ratingLoyaltyProgram->getReviewData($loyaltyProgramName);

        if (empty($result['provider'])) {
            return $this->redirect($this->router->generate('aw_supported'));
        }
        $result['fieldNames'] = Review::SCORES_FIELDS;

        $mileValue = $mileValueService->getProviderItem($result['provider']['providerid']);

        if (!empty($mileValue)) {
            $mileValue = [
                '_mileValue' => [
                    'title' => $translator->trans('point-mile-values'),
                    'flights' => PROVIDER_KIND_AIRLINE === $result['provider']['kind'] || 'transfers' === $mileValue['group'],
                    'data' => [$mileValue],
                ],
            ];
        }

        if (!empty($result['provider']['blogPostId'])) {
            $listBlogPost = $blogPost->fetchPostById($result['provider']['blogPostId'], true);

            if (!empty($listBlogPost)) {
                $queryArgs = ['cid' => 'lp-review'];
                $awid = $request->query->get('awid');

                if (!empty($awid)) {
                    $queryArgs['awid'] = $awid;
                }

                foreach ($listBlogPost as &$item) {
                    $item['postURL'] = StringHandler::replaceVarInLink($item['postURL'], $queryArgs, true);
                }
            }
        }

        $response = new Response($this->twig->render('@AwardWalletMain/RatingLoyaltyProgram/rating.html.twig', [
            'isAuth' => $this->authorizationChecker->isGranted('ROLE_USER'),
            'initialData' => $result,
            'counter' => [
                'programs' => $entityManager->getRepository(Provider::class)->getLPCount($_SERVER['DOCUMENT_ROOT']),
                'users' => (int) ($counter->getUsersCount() / 1000),
                'points' => (int) substr((string) $entityManager->getRepository(Parameter::class)->getMilesCount(), 0, 3),
            ],
            'blogposts' => $listBlogPost ?? [],
            'blogtags' => empty($result['provider']['blogTagsId']) ? '' : explode(',', str_replace(' ', '', trim($result['provider']['blogTagsId'], ', ')))[0],
            'transferTimesTo' => $transferTimes->getData(BalanceWatch::POINTS_SOURCE_TRANSFER, $result['provider']['providerid'])['data'],
            'transferTimesFrom' => $transferTimes->getData(BalanceWatch::POINTS_SOURCE_TRANSFER, null, $result['provider']['providerid'])['data'],
            'mileValue' => $mileValue ?? [],
        ]));
        $response->headers->set('X-Robots-Tag', CustomHeadersListener::XROBOTSTAG_NOINDEX);

        return $response;
    }

    /**
     * View reviews with id = $reviewId.
     *
     * @Route("/r/review/{reviewId}", name="aw_loyalty_program_rating_review", options={"expose"=true})
     * @Template("@AwardWalletMain/RatingLoyaltyProgram/review.html.twig")
     * @return array|RedirectResponse|RedirectResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function reviewAction(Request $request, ?int $reviewId = null)
    {
        if (empty($reviewId)
            || empty($result = $this->ratingLoyaltyProgram->getReview($reviewId))
        ) {
            return $this->redirect($this->router->generate('aw_supported'));
        }

        $result['fieldNames'] = array_values(Review::SCORES_FIELDS);
        $result['fieldNames'] = array_map('strtolower', $result['fieldNames']);

        return [
            'initialData' => $result,
        ];
    }

    /**
     * @Route("/rating/review/update", methods={"POST"}, name="aw_rating_review_update", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function addReviewAction(Request $request): JsonResponse
    {
        $result = $this->ratingLoyaltyProgram->addReview($request->request->get('review'));

        if (array_key_exists('ProviderID', $result)) {
            $result['reviewsList'] = $this->ratingLoyaltyProgram->getReviewData((int) $result['ProviderID'])['reviewsList'];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/rating/useful/vote", methods={"POST"}, name="aw_rating_useful_vote", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     * @return JsonResponse|\Exception
     */
    public function voteUsefulAction(Request $request, AwTokenStorageInterface $tokenStorage): JsonResponse
    {
        $this->ratingLoyaltyProgram->voteUseful($request->request->get('reviewId'), $tokenStorage->getBusinessUser()->getId());

        return new JsonResponse(
            [
                'reviewsList' => $this->ratingLoyaltyProgram->getReviewData((int) $request->request->get('providerId'))['reviewsList'],
            ]
        );
    }

    /**
     * @Route("/rating/", name="aw_rating_compatibility")
     * @Route("/rating/reviews.php", name="aw_rating_compatibility_reviews")
     * @Route("/rating/view.php", name="aw_rating_compatibility_view")
     * @Route("/rating/rate.php", name="aw_rating_compatibility_rate")
     * @Route("/rating/legend.php", name="aw_rating_compatibility_legend")
     */
    public function oldUriСompatibility(Request $request, ProviderRepository $providerRepository): RedirectResponse
    {
        $providerId = (int) $request->get('ProviderID', 0);

        if (!empty($providerId) && ($provider = $providerRepository->find($providerId))) {
            return new RedirectResponse($this->router->generate('aw_loyalty_program_rating', ['loyaltyProgramName' => ProviderRating::urlName($provider->getDisplayname())]));
        }

        return new RedirectResponse($this->router->generate('aw_supported'));
    }
}
