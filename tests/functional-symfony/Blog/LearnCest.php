<?php

namespace AwardWallet\Tests\FunctionalSymfony\Blog;

use AwardWallet\MainBundle\Entity\BlogUserPost;
use AwardWallet\MainBundle\Service\Blog\Constants;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class LearnCest
{
    private ?RouterInterface $router;
    private ?int $userId;
    private string $username;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');

        $this->userId = $I->createAwUser(null, null, [], true);
        $this->username = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->userId]);

        $I->amOnPage($this->router->generate('aw_blog_learn', ['_switch_user' => $this->username]));
    }

    public function testIndexPage(\TestSymfonyGuy $I)
    {
        $I->seeResponseCodeIs(200);

        $I->see('Latest Posts', 'h3');
        $I->see('Top Offers for You', 'span');
    }

    public function testLatestPostsPage(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_blog_learn_latestposts'));
        $I->seeResponseCodeIs(200);

        $I->see('Latest Posts', 'h3');
    }

    public function testFavoritePosts(\TestSymfonyGuy $I)
    {
        $postId = 1;
        $I->amOnPage($this->router->generate('aw_blog_learn'));

        $I->sendPUT($this->router->generate('aw_blog_learn_favorite') . '?id=' . $postId);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['success' => true]);

        $I->seeInDatabase('BlogUserPost', [
            'Type' => BlogUserPost::TYPE_FAVORITE,
            'UserID' => $this->userId,
            'PostID' => $postId,
        ]);

        $I->amOnPage($this->router->generate('aw_blog_learn_favorite'));
        $I->seeResponseCodeIs(200);

        $I->sendDELETE(
            $this->router->generate('aw_blog_learn_favorite')
            . '?id=' . $postId
        );
        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInDatabase('BlogUserPost', [
            'Type' => BlogUserPost::TYPE_FAVORITE,
            'UserID' => $this->userId,
            'PostID' => $postId,
        ]);
    }

    public function testResponses(\TestSymfonyGuy $I)
    {
        $endpoints = [
            'aw_blog_learn',
            'aw_blog_learn_expiring',
            'aw_blog_learn_latestposts',
            'aw_blog_learn_favorite',
        ];

        foreach ($endpoints as $endpoint) {
            $I->sendPOST($this->router->generate($endpoint));
            $I->seeResponseCodeIsSuccessful();
            $I->seeResponseIsJson();
        }

        $categorySlug = Constants::CATEGORY_SLUG[Constants::CATEGORY_NEWS_AND_PROMOTIONS_ID];
        $I->sendPOST($this->router->generate('aw_blog_learn_category', [
            'category' => $categorySlug,
        ]));
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
    }
}
