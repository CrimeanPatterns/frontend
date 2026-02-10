<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\Entity\Usr;

class UserAvatar extends \Twig_Extension
{
    public const SIZE_ORIGINAL = 'original';
    public const SIZE_LARGE = 'large';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_SMALL = 'small';

    private string $protoAndHost;

    public function __construct(string $protoAndHost)
    {
        $this->protoAndHost = $protoAndHost;
    }

    public function getUserUrl(Usr $user, bool $asURL = true, string $size = self::SIZE_SMALL): ?string
    {
        $path = $user->getAvatarLink($size);

        if (!is_null($path)) {
            if ($asURL) {
                return $this->protoAndHost . $path;
            }

            return $path;
        }

        if ($user->getOAuth()->count() > 0) {
            /** @var UserOAuth $lastOAuth */
            $lastOAuth = $user->getOAuth()->first();

            if (!empty($avatarURL = $lastOAuth->getAvatarURL())) {
                return $avatarURL;
            }
        }

        return null;
    }

    public function getUserUrlByParts(
        int $userId,
        ?int $pictureVer,
        ?string $oauthAvatarUrl = null,
        bool $asURL = true,
        string $size = self::SIZE_SMALL
    ): ?string {
        $path = Usr::generateAvatarLink($userId, $pictureVer, 'jpg', $size);

        if (!is_null($path)) {
            if ($asURL) {
                return $this->protoAndHost . $path;
            }

            return $path;
        }

        if (!is_null($oauthAvatarUrl)) {
            return $oauthAvatarUrl;
        }

        return null;
    }

    public function getUserAgentUrl(Useragent $useragent, bool $asURL = true): ?string
    {
        $path = $useragent->getAvatarSrc();

        if (!is_null($path)) {
            if ($asURL) {
                return $this->protoAndHost . $path;
            }

            return $path;
        }

        return null;
    }

    public function getUserAgentUrlByParts(
        int $useragentId,
        ?int $pictureVer,
        bool $asURL = true
    ): ?string {
        $path = Useragent::generateAvatarSrc($useragentId, $pictureVer, 'jpg');

        if (!is_null($path)) {
            if ($asURL) {
                return $this->protoAndHost . $path;
            }

            return $path;
        }

        return null;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('userAvatar', [$this, 'getUserUrl']),
            new \Twig_SimpleFunction('userAgentAvatar', [$this, 'getUserAgentUrl']),
        ];
    }
}
