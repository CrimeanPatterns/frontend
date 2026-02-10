<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Service\Blog\BlogPostMock;

/**
 * @group frontend-functional
 * @group curldriver
 */
class HomeControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        $blogpostMock = $I->stubMake(BlogPostMock::class, [
            'fetchLastPost' => function ($count = 1) {
                return json_decode(file_get_contents(__DIR__ . '/../../../_data/Blog/getHomepagePost.json'));
            },
        ]);
        $I->mockService(BlogPostMock::class, $blogpostMock);
    }

    /**
     * @group locks
     */
    public function indexBlogpost(\TestSymfonyGuy $I)
    {
        $blogposts = json_decode(file_get_contents(__DIR__ . '/../../../_data/Blog/getHomepagePost.json'));
        $I->assertNotEmpty($blogposts[0]->title);

        $I->amOnSubdomain('business');
        $I->amOnPage('/');
        $grabTitle = $I->grabTextFrom('.blog .blog__post .blog__post-title a');
        $I->assertEquals($grabTitle, $blogposts[0]->title);
    }

    public function redirectLoggedInOnCoupon(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10)
        );

        $I->amOnPage('/register?code=SomeInvalidCoupon&_switch_user=' . $username);
        $I->amOnRoute('aw_users_usecoupon');
        $I->dontSee("Invalid coupon code");

        $I->amOnPage('/register?Code=SomeInvalidCoupon');
        $I->amOnRoute('aw_users_usecoupon');
        $I->dontSee("Invalid coupon code");
    }
}
