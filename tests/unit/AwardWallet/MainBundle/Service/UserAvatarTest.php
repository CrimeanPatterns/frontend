<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\UserAvatar;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class UserAvatarTest extends BaseUserTest
{
    private const OAUTH_AVATAR_URL = 'oauthAvatarURL';

    /**
     * @var UserAvatar
     */
    private $avatar;

    /**
     * @var Useragent
     */
    private $ua;

    public function _before()
    {
        parent::_before();

        $this->avatar = $this->container->get(UserAvatar::class);
        $this->user->getOAuth()->add(
            $this->getUserOAuth('yahoo', 'xxx', self::OAUTH_AVATAR_URL)
        );
        $this->ua = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)
            ->find($this->aw->createFamilyMember($this->user->getUserid(), 'XXX', 'YYY'));
    }

    public function _after()
    {
        $this->ua = null;
        $this->avatar = null;

        parent::_after();
    }

    public function testGetUserUrl()
    {
        $this->user->setPicturever(123);
        $this->assertStringStartsWith('http', $this->avatar->getUserUrl($this->user));
        $this->assertStringStartsWith('/', $this->avatar->getUserUrl($this->user, false));

        $this->user->setPicturever(null);
        $this->assertEquals(self::OAUTH_AVATAR_URL, $this->avatar->getUserUrl($this->user));

        $oauth = $this->user->getOAuth();
        $firstElem = $oauth->first();
        $oauth->clear();
        $oauth->add(
            $this->getUserOAuth('google', 'yyy')
        );
        $oauth->add($firstElem);
        $this->assertNull($this->avatar->getUserUrl($this->user));

        $oauth->clear();
        $this->assertNull($this->avatar->getUserUrl($this->user));
    }

    public function testGetUserUrlByParts()
    {
        $userId = $this->user->getUserid();
        $this->assertStringStartsWith(
            'http',
            $this->avatar->getUserUrlByParts($userId, 123, self::OAUTH_AVATAR_URL)
        );
        $this->assertStringStartsWith(
            '/',
            $this->avatar->getUserUrlByParts($userId, 123, self::OAUTH_AVATAR_URL, false)
        );
        $this->assertEquals(
            self::OAUTH_AVATAR_URL,
            $this->avatar->getUserUrlByParts($userId, null, self::OAUTH_AVATAR_URL)
        );
        $this->assertNull($this->avatar->getUserUrlByParts($userId, null, null));
    }

    public function testGetUserAgentUrl()
    {
        $this->ua->setPicturever(123);
        $this->assertStringStartsWith('http', $this->avatar->getUserAgentUrl($this->ua));
        $this->assertStringStartsWith('/', $this->avatar->getUserAgentUrl($this->ua, false));

        $this->ua->setPicturever(null);
        $this->assertNull($this->avatar->getUserAgentUrl($this->ua));
    }

    public function testGetUserAgentUrlByParts()
    {
        $uaId = $this->ua->getUseragentid();
        $this->assertStringStartsWith(
            'http',
            $this->avatar->getUserAgentUrlByParts($uaId, 123)
        );
        $this->assertStringStartsWith(
            '/',
            $this->avatar->getUserAgentUrlByParts($uaId, 123, false)
        );
        $this->assertNull($this->avatar->getUserAgentUrlByParts($uaId, null));
    }

    private function getUserOAuth(string $provider, string $providerUserId, ?string $avatar = null): UserOAuth
    {
        $email = 'avatar.' . StringHandler::getPseudoRandomString(10) . '@mail.com';
        $randName = StringHandler::getRandomName();

        return new UserOAuth(
            $this->user,
            $email,
            $randName['FirstName'],
            $randName['LastName'],
            $provider,
            $providerUserId,
            $avatar
        );
    }
}
