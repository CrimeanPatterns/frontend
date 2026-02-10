<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @deprecated use class UserAvatar
 */
class AvatarJpegHelper
{
    private const IMAGE_LOADERS = [
        \IMAGETYPE_GIF => '\\imagecreatefromgif',
        \IMAGETYPE_PNG => '\\imagecreatefrompng',
        \IMAGETYPE_JPEG => '\\imagecreatefromjpeg',
    ];

    private UrlGeneratorInterface $urlGenerator;

    private string $rootDir;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        string $rootDir
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->rootDir = $rootDir;
    }

    public function getUserAvatarUrl(Usr $user, int $urlReferenceType): ?string
    {
        $ver = $user->getPicturever();

        if (!$ver) {
            return null;
        }

        return $this->urlGenerator->generate(
            'awm_avatar_user',
            [
                "userIdDivBy1000" => \sprintf("%06d", $user->getUserid() / 1000),
                "timestamp" => $ver,
                "userId" => $user->getUserid(),
            ],
            $urlReferenceType
        );
    }

    public function getUserAvatarUrlByParts(int $userId, int $timestamp, int $urlReferenceType): ?string
    {
        if (!$timestamp) {
            return null;
        }

        return $this->urlGenerator->generate(
            'awm_avatar_user',
            [
                "userIdDivBy1000" => \sprintf("%06d", $userId / 1000),
                "timestamp" => $timestamp,
                "userId" => $userId,
            ],
            $urlReferenceType
        );
    }

    public function getUserAgentAvatarUrl(Useragent $useragent, int $urlReferenceType): ?string
    {
        $ver = $useragent->getPicturever();

        if (!$ver) {
            return null;
        }

        return $this->urlGenerator->generate(
            'awm_avatar_useragent',
            [
                "userAgentIdDivBy1000" => \sprintf("%06d", $useragent->getUseragentid() / 1000),
                "timestamp" => $ver,
                "userAgentId" => $useragent->getUseragentid(),
            ],
            $urlReferenceType
        );
    }

    public function getUserAgentAvatarUrlByParts(int $useragentId, int $timestamp, int $urlReferenceType): ?string
    {
        if (!$timestamp) {
            return null;
        }

        return $this->urlGenerator->generate(
            'awm_avatar_useragent',
            [
                "userAgentIdDivBy1000" => \sprintf("%06d", $useragentId / 1000),
                "timestamp" => $timestamp,
                "userAgentId" => $useragentId,
            ],
            $urlReferenceType
        );
    }

    public function getImageDataByUser(Usr $user): ?string
    {
        $src = $user->getAvatarSrc();

        if (empty($src)) {
            return null;
        }

        return $this->getImageData($src);
    }

    public function getImageDataByUserAgent(Useragent $useragent): ?string
    {
        $src = $useragent->getAvatarSrc();

        if (empty($src)) {
            return null;
        }

        return $this->getImageData($src);
    }

    protected function getImageData(string $imageRelPath): ?string
    {
        $path = \realpath($this->rootDir . '/../web/' . $imageRelPath);

        if (!$path) {
            return null;
        }

        $imageInfo = @\getimagesize($path);

        if (\count($imageInfo) < 5) {
            return null;
        }

        $imageloader = self::IMAGE_LOADERS[$imageInfo[2]];
        $image = $imageloader($path);
        \ob_start();
        \imagejpeg($image, null, 100);
        $imageData = \ob_get_clean();
        \imagedestroy($image);

        return $imageData;
    }
}
