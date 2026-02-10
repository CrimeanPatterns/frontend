<?php

namespace AwardWallet\Tests\Unit\Blog;

use AwardWallet\MainBundle\Controller\Blog\LearnController;
use AwardWallet\MainBundle\Service\Blog\Constants;
use AwardWallet\MainBundle\Service\Blog\Learn;
use AwardWallet\MainBundle\Service\Blog\UserPost;
use AwardWallet\Tests\Unit\BaseTest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @group frontend-unit
 */
class LearnTest extends BaseTest
{
    private $router;
    /** @var Learn */
    private $learnMock;
    /** @var UserPost */
    private $favoriteMock;
    /** @var Environment */
    private $twigEnvMock;
    /** @var LearnController */
    private $controller;

    public function _before(): void
    {
        parent::_before();
        $this->router = $this->getModule('Symfony')->_getContainer()->get(RouterInterface::class);

        $this->learnMock = $this->createMock(Learn::class);
        $this->favoriteMock = $this->createMock(UserPost::class);
        $this->twigEnvMock = $this->createMock(Environment::class);

        $this->controller = new LearnController(
            $this->learnMock,
            $this->favoriteMock,
            $this->twigEnvMock
        );
    }

    public function _after()
    {
        $this->learnMock =
        $this->favoriteMock =
        $this->twigEnvMock =
        $this->controller = null;

        parent::_after();
    }

    public function testIndexActionWithGetRequest(): void
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);

        $this->twigEnvMock->expects($this->once())
            ->method('addGlobal')
            ->with('webpack', true);

        $this->learnMock->expects($this->once())
            ->method('fetchByExpiring')
            ->willReturn(['title' => 'Expiring Posts', 'posts' => []]);

        $this->learnMock->expects($this->once())
            ->method('fetchBySubAccount')
            ->willReturn(['title' => 'Sub Account Posts', 'posts' => []]);

        $this->learnMock->expects($this->once())
            ->method('getLatestPosts')
            ->willReturn(['title' => 'Latest Posts', 'posts' => []]);

        $this->learnMock->expects($this->once())
            ->method('fetchLatestNews')
            ->willReturn(['title' => 'Latest News', 'posts' => []]);

        $result = $this->controller->indexAction($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('menu', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('offers', $result);
        $this->assertArrayHasKey('groups', $result);
    }

    public function testIndexActionWithPostRequest(): void
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $this->twigEnvMock->expects($this->once())
            ->method('addGlobal')
            ->with('webpack', true);

        $this->learnMock->expects($this->once())
            ->method('fetchByExpiring')
            ->willReturn(['title' => 'Expiring Posts', 'posts' => []]);

        $this->learnMock->expects($this->once())
            ->method('fetchBySubAccount')
            ->willReturn(['title' => 'Learn how to earn more miles through credit card offers', 'posts' => []]);

        $this->learnMock->expects($this->once())
            ->method('getLatestPosts')
            ->willReturn(['title' => 'Latest Posts', 'posts' => []]);

        $this->learnMock->expects($this->once())
            ->method('fetchLatestNews')
            ->willReturn(['title' => 'Latest News', 'posts' => []]);

        $result = $this->controller->indexAction($request);

        $this->assertInstanceOf(JsonResponse::class, $result);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('menu', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('offers', $data);
        $this->assertArrayHasKey('groups', $data);
    }

    public function testExpiringPostsActionWithPostRequest(): void
    {
        $expiringPosts = [
            'title' => 'Expiring Posts',
            'posts' => [
                ['id' => 1, 'title' => 'Post 1'],
                ['id' => 2, 'title' => 'Post 2'],
            ],
        ];

        $this->learnMock->expects($this->once())
            ->method('fetchByExpiring')
            ->with(64)
            ->willReturn($expiringPosts);

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $result = $this->controller->expiringPostsAction($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals($expiringPosts, json_decode($result->getContent(), true));
    }

    public function testCategoryActionWithInvalidCategory(): void
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $result = $this->controller->categoryAction($request,
            $this->router,
            'invalid-category'
        );

        $this->assertIsArray($result);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code']);
        $this->assertEquals('unknown category', $result['error']);
    }

    public function testCategoryActionWithValidCategory(): void
    {
        $validCategory = Constants::CATEGORY_SLUG[Constants::CATEGORY_NEWS_AND_PROMOTIONS_ID];

        $categoryPosts = [
            'title' => Constants::CATEGORY_NAMES[Constants::CATEGORY_NEWS_AND_PROMOTIONS_ID],
            'posts' => [],
        ];

        $this->learnMock->expects($this->once())
            ->method('getCategoryPost')
            ->with($validCategory, 1)
            ->willReturn($categoryPosts);

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $result = $this->controller->categoryAction(
            $request,
            $this->router,
            $validCategory
        );

        $this->assertIsArray(json_decode($result->getContent(), true));
    }

    public function testCategoryActionWithPostRequest(): void
    {
        $validCategory = Constants::CATEGORY_SLUG[Constants::CATEGORY_NEWS_AND_PROMOTIONS_ID];

        $categoryPosts = [
            'title' => 'News',
            'posts' => [
                ['id' => 1, 'title' => 'News Post 1'],
                ['id' => 2, 'title' => 'News Post 2'],
            ],
        ];

        $this->learnMock->expects($this->once())
            ->method('getCategoryPost')
            ->with($validCategory, 1)
            ->willReturn($categoryPosts);

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $result = $this->controller->categoryAction($request, $this->router, $validCategory);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals($categoryPosts, json_decode($result->getContent(), true));
    }

    public function testProviderActionWithPostRequest(): void
    {
        $providerCode = 'AA';

        $providerPosts = [
            'title' => 'American Airlines',
            'posts' => [
                ['id' => 1, 'title' => 'Provider Post 1'],
                ['id' => 2, 'title' => 'Provider Post 2'],
            ],
        ];

        $this->learnMock->expects($this->once())
            ->method('getPostByProvider')
            ->with($providerCode)
            ->willReturn($providerPosts);

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $result = $this->controller->providerAction($request, $providerCode);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals($providerPosts, json_decode($result->getContent(), true));
    }

    public function testLatestPostsActionWithPostRequest(): void
    {
        $latestPosts = [
            'title' => 'Latest Posts',
            'posts' => [
                ['id' => 1, 'title' => 'Latest Post 1'],
                ['id' => 2, 'title' => 'Latest Post 2'],
            ],
        ];

        $this->learnMock->expects($this->once())
            ->method('getLatestPosts')
            ->with(1)
            ->willReturn($latestPosts);

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $result = $this->controller->latestPostsAction($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals($latestPosts, json_decode($result->getContent(), true));
    }

    public function testFavoriteActionWithPutRequest(): void
    {
        $postId = 1;

        $this->favoriteMock->expects($this->once())
            ->method('set')
            ->with($postId)
            ->willReturn(true);

        $request = new Request();
        $request->setMethod(Request::METHOD_PUT);
        $request->query->set('id', $postId);

        $result = $this->controller->favoriteAction($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $expectedData = ['success' => true];
        $this->assertEquals($expectedData, json_decode($result->getContent(), true));
    }

    public function testFavoriteActionWithDeleteRequest(): void
    {
        $postId = 1;

        $this->favoriteMock->expects($this->once())
            ->method('delete')
            ->with($postId)
            ->willReturn(true);

        $request = new Request();
        $request->setMethod(Request::METHOD_DELETE);
        $request->query->set('id', $postId);

        $result = $this->controller->favoriteAction($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $expectedData = ['success' => true];
        $this->assertEquals($expectedData, json_decode($result->getContent(), true));
    }

    public function testFavoriteActionWithPostRequest(): void
    {
        $favoritePosts = [
            'title' => 'Favorite Posts',
            'posts' => [
                ['id' => 1, 'title' => 'Favorite Post 1'],
                ['id' => 2, 'title' => 'Favorite Post 2'],
            ],
        ];

        $this->learnMock->expects($this->once())
            ->method('getUserPosts')
            ->willReturn($favoritePosts);

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $result = $this->controller->favoriteAction($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals($favoritePosts, json_decode($result->getContent(), true));
    }
}
