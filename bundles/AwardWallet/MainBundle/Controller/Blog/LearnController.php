<?php

namespace AwardWallet\MainBundle\Controller\Blog;

use AwardWallet\MainBundle\Entity\BlogUserPost;
use AwardWallet\MainBundle\Service\Blog\Constants;
use AwardWallet\MainBundle\Service\Blog\Learn;
use AwardWallet\MainBundle\Service\Blog\UserPost;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @Route("/learn")
 * @Security("is_granted('ROLE_STAFF')")
 */
class LearnController extends AbstractController
{
    private Learn $learn;
    private UserPost $userPost;
    private Environment $twigEnv;

    public function __construct(Learn $learn, UserPost $userPost, Environment $twigEnv)
    {
        $this->learn = $learn;
        $this->userPost = $userPost;
        $this->twigEnv = $twigEnv;
    }

    /**
     * @Route("/", name="aw_blog_learn", options={"expose"=true})
     * @Route("/search", name="aw_blog_learn_search", options={"expose"=true})
     * @Template("@AwardWalletMain/Blog/learn.html.twig")
     */
    public function indexAction(Request $request, array $mainList = [])
    {
        $this->twigEnv->addGlobal('webpack', true);

        $canEmptyPosts = in_array($request->attributes->get('_route'), ['aw_blog_learn_read', 'aw_blog_learn_favorite']);
        $groups = [];

        if ((empty($mainList) || empty($mainList['posts'])) && !$canEmptyPosts) {
            $expiringPosts = $this->learn->fetchByExpiring();

            if (!empty($expiringPosts['posts'])) {
                $groups[] = $expiringPosts;
            }

            $subAccountPosts = $this->learn->fetchBySubAccount();

            if (!empty($subAccountPosts['posts'])) {
                $groups[] = $subAccountPosts;
            }

            // $groups[] = $this->learn->fetchByTravels();
            $groups[] = $this->learn->getLatestPosts();
        } else {
            $groups[] = $mainList;
        }

        $data = [
            'menu' => $this->learn->getMenu(),
            'user' => $this->learn->getUserData(),
            'labels' => $this->learn->getLabels(),
            'offers' => $this->learn->getCardOffers(),

            'recommendedOffer' => $this->learn->getRecommendedOffer(),
            'latestNews' => $this->learn->fetchLatestNews(),
            'groups' => $groups,

            'dataset' => [],
            'apiSearch' => [
                'appId' => 'DBGHIZ7OVP',
                'apiKey' => 'a4775da8b76a49dc813f2ee7d29f8d25',
                'indexName' => 'aw_posts_exports',
            ],
        ];

        foreach ($data as $key => $type) {
            if (null !== $type && array_key_exists('posts', $data[$key]) && empty($data[$key]['posts'])) {
                unset($data[$key]);
            }
        }

        if (Request::METHOD_POST === $request->getMethod()) {
            return new JsonResponse($data);
        }

        if (!empty($s = $request->query->get('s'))) {
            $data['search'] = $s;
        }

        return $data;
    }

    /**
     * @Route("/expiring", name="aw_blog_learn_expiring", options={"expose"=true})
     */
    public function expiringPostsAction(Request $request)
    {
        $list = $this->learn->fetchByExpiring(64);

        if (Request::METHOD_POST === $request->getMethod()) {
            return new JsonResponse($list);
        }

        return $this->forward('AwardWallet\MainBundle\Controller\Blog\LearnController::indexAction', [
            'request' => $request,
            'mainList' => $list,
        ]);
    }

    /**
     * @Route("/more-offers", name="aw_blog_learn_more_offers", options={"expose"=true})
     */
    public function moreOffersAction(Request $request)
    {
        $list = $this->learn->fetchBySubAccount(64);

        if (Request::METHOD_POST === $request->getMethod()) {
            return new JsonResponse($list);
        }

        return $this->forward('AwardWallet\MainBundle\Controller\Blog\LearnController::indexAction', [
            'request' => $request,
            'mainList' => $list,
        ]);
    }

    /**
     * @Route("/category/{category}", name="aw_blog_learn_category", options={"expose"=true})
     * @Route("/category/{category}/page-{page}", name="aw_blog_learn_category_page", requirements={"page"="\d+"}, options={"expose"=true})
     */
    public function categoryAction(Request $request, RouterInterface $router, string $category = '', int $page = 1)
    {
        if (false === in_array($category, Constants::CATEGORY_SLUG)) {
            if (Request::METHOD_POST !== $request->getMethod()) {
                return new RedirectResponse($router->generate('aw_blog_learn'));
            }

            return [
                'code' => Response::HTTP_BAD_REQUEST,
                'error' => 'unknown category',
            ];
        }

        $list = $this->learn->getCategoryPost($category, $page);

        if (Request::METHOD_POST === $request->getMethod()) {
            return new JsonResponse($list);
        }

        return $this->forward('AwardWallet\MainBundle\Controller\Blog\LearnController::indexAction', [
            'request' => $request,
            'mainList' => $list,
        ]);
    }

    /**
     * @Route("/provider/{providerCode}", name="aw_blog_learn_provider", options={"expose"=true})
     */
    public function providerAction(Request $request, string $providerCode)
    {
        $list = $this->learn->getPostByProvider($providerCode);

        if (Request::METHOD_POST === $request->getMethod()) {
            return new JsonResponse($list);
        }

        return $this->forward('AwardWallet\MainBundle\Controller\Blog\LearnController::indexAction', [
            'request' => $request,
            'mainList' => $list,
        ]);
    }

    /**
     * @Route("/latest-posts", name="aw_blog_learn_latestposts", options={"expose"=true})
     * @Route("/latest-posts/page-{page}", name="aw_blog_learn_latestposts_page", requirements={"page"="\d+"}, options={"expose"=true})
     */
    public function latestPostsAction(Request $request, int $page = 1)
    {
        $list = $this->learn->getLatestPosts($page);

        if (Request::METHOD_POST === $request->getMethod()) {
            return new JsonResponse($list);
        }

        return $this->forward('AwardWallet\MainBundle\Controller\Blog\LearnController::indexAction', [
            'request' => $request,
            'mainList' => $list,
        ]);
    }

    /**
     * @Route("/favorites", name="aw_blog_learn_favorite", options={"expose"=true})
     */
    public function favoriteAction(Request $request)
    {
        $response = ['success' => false];

        switch ($request->getMethod()) {
            case Request::METHOD_PUT:
                $response['success'] = $this->userPost->set($request->query->getInt('id'), BlogUserPost::TYPE_FAVORITE);

                break;

            case Request::METHOD_DELETE:
                $response['success'] = $this->userPost->delete(
                    $request->query->getInt('id'),
                    BlogUserPost::TYPE_FAVORITE
                );

                break;

            case Request::METHOD_POST:
                $response = $this->learn->getUserPosts(BlogUserPost::TYPE_FAVORITE);

                break;

            case Request::METHOD_GET:
                return $this->forward('AwardWallet\MainBundle\Controller\Blog\LearnController::indexAction', [
                    'request' => $request,
                    'mainList' => $this->learn->getUserPosts(BlogUserPost::TYPE_FAVORITE),
                    '_route' => $request->attributes->get('_route'),
                ]);
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/learned", name="aw_blog_learn_read", options={"expose"=true})
     */
    public function readAction(Request $request)
    {
        $response = ['success' => false];

        switch ($request->getMethod()) {
            case Request::METHOD_PUT:
                $response['success'] = $this->userPost->set(
                    $request->query->getInt('id'),
                    BlogUserPost::TYPE_MARK_READ
                );

                break;

            case Request::METHOD_DELETE:
                $response['success'] = $this->userPost->delete(
                    $request->query->getInt('id'),
                    BlogUserPost::TYPE_MARK_READ
                );

                break;

            case Request::METHOD_POST:
                $response = $this->learn->getUserPosts(BlogUserPost::TYPE_MARK_READ);

                break;

            case Request::METHOD_GET:
                return $this->forward('AwardWallet\MainBundle\Controller\Blog\LearnController::indexAction', [
                    'request' => $request,
                    'mainList' => $this->learn->getUserPosts(BlogUserPost::TYPE_MARK_READ),
                    '_route' => $request->attributes->get('_route'),
                ]);
        }

        return new JsonResponse($response);
    }
}
