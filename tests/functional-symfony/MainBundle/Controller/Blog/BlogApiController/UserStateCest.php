<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Blog\BlogApiController;

use Codeception\Example;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Controller\Blog\BlogApiController
 * @group frontend-functional
 */
class UserStateCest
{
    /**
     * @dataProvider testUserStateActionDataProvider
     */
    public function testUserStateAction(\TestSymfonyGuy $I, Example $example)
    {
        $login = "l" . bin2hex(random_bytes(8));
        $userId = $I->createAwUser($login, null, [
            'AccountLevel' => $example['AccountLevel'],
            'IsBlogPostAds' => $example['IsBlogPostAds'],
        ]);

        if ($example['SwitchUser']) {
            $I->amOnRoute('aw_blog_api_user_info', ['_switch_user' => $login]);
        }

        $data = ["userId" => $userId];

        if ($example['isNative']) {
            $data['isNative'] = '1';
        }

        $I->sendPost("/api/blog/user-state", $data);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            "isPA" => $example['expectingAds'],
        ]);
    }

    private function testUserStateActionDataProvider(): array
    {
        $scenarios = [
            [
                'IsBlogPostAds' => 1,
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'expectingAds' => true,
            ],
            [
                'IsBlogPostAds' => 0,
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'expectingAds' => true,
            ],
            [
                'IsBlogPostAds' => 0,
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'expectingAds' => false,
            ],
            [
                'IsBlogPostAds' => 1,
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'expectingAds' => true,
            ],
        ];

        return
            array_merge(
                array_map(fn ($scenario) => ['isNative' => true, 'SwitchUser' => false] + $scenario, $scenarios),
                array_map(fn ($scenario) => ['isNative' => false, 'SwitchUser' => true] + $scenario, $scenarios)
            )
        ;
    }
}
