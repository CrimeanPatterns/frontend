<?php

namespace AwardWallet\Tests\FunctionalSymfony\Security;

use AwardWallet\Common\Memcached\MemcachedMock;
use AwardWallet\MainBundle\Entity\Sitegroup;
use Codeception\Example;

/**
 * @group frontend-functional
 * @group security
 * @group manager
 */
class ManagerWith2FACest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider cases2FA
     */
    public function test2FAAccess(\TestSymfonyGuy $I, Example $example): void
    {
        $userId = $I->createAwUser(
            $username = 'test' . $I->grabRandomString(),
            $I->grabRandomString(10),
            $example['userFields'],
            false
        );

        if ($example['isStaff']) {
            // give ROLE_STAFF group
            $I->haveInDatabase('GroupUserLink', ['UserID' => $userId, 'SiteGroupID' => Sitegroup::STAFF_ID]);
            $I->haveInDatabase('GroupUserLink', ['UserID' => $userId, 'SiteGroupID' => Sitegroup::STAFF_DEVELOPER_ID]);
        }

        $I->mockService(
            'aw.memcached',
            new class() extends MemcachedMock {
                public function getStats($args = null)
                {
                    return ['localhost:11211' => ['pid' => 1]];
                }
            },
        );
        $I->amOnPage("/m/api/login_status?_switch_user=" . $username);

        // emulate request for old TQuery\TBaseList classes
        if (!isset($GLOBALS['_SERVER'])) {
            $GLOBALS['_SERVER'] = [];
        }

        $GLOBALS['_SERVER']['DOCUMENT_URI'] = $example['url'];
        $GLOBALS['_SERVER']['QUERY_STRING'] = '?some=some';

        try {
            $I->amOnPage($example['url']);
        } finally {
            // switch back
            unset($GLOBALS['_SERVER']['DOCUMENT_URI']);
            unset($GLOBALS['_SERVER']['QUERY_STRING']);
        }

        $I->seeResponseCodeIs($example['code']);
    }

    private function cases2FA(): array
    {
        $enabled2FAFields = [
            'GoogleAuthSecret' => 'some',
            'GoogleAuthRecoveryCode' => 'some',
        ];
        $disabled2FAFields = [];
        $testCases = [];

        foreach (
            [
                '/manager/',
                '/manager/impersonate',
                '/manager/list.php?Schema=Provider',
                '/manager/sonata/email-template/list',
                '/manager/email/parser/list',
                '/manager/email/stat',
            ] as $route
        ) {
            foreach ([true, false] as $isStaff) {
                foreach ([true, false] as $is2FAEnabled) {
                    $testCases[] = [
                        'userFields' => $is2FAEnabled ? $enabled2FAFields : $disabled2FAFields,
                        'isStaff' => $isStaff,
                        'url' => $route,
                        'code' => ($isStaff && $is2FAEnabled) ? 200 : 403,
                    ];
                }
            }
        }

        return $testCases;
    }
}
