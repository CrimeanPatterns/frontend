<?php

namespace AwardWallet\MainBundle\Globals\Image;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\UserAvatar;

class AvatarCreator
{
    protected $sizes = [
        UserAvatar::SIZE_ORIGINAL => 9999,
        UserAvatar::SIZE_LARGE => 800,
        UserAvatar::SIZE_MEDIUM => 250,
        UserAvatar::SIZE_SMALL => 64,
    ];

    protected $path;
    protected $version;
    protected $tempFile;
    protected $resource;

    public function __construct($resource)
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/images/uploaded/user/';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $this->path = realpath($path);

        $this->version = (int) microtime(true);
        $this->tempFile = tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents($this->tempFile, $resource);
        $this->resource = Tools::getImageResource($this->tempFile);
    }

    /**
     * @param int $viewWidth
     * @param int $viewHeight
     * @param int $x
     * @param int $y
     * @param float $scale
     * @param int $angle - 90/180/270
     */
    public function createAvatarsForUser(Usr $user, $viewWidth = null, $viewHeight = null, $x = 0, $y = 0, $scale = 1, $angle = 0)
    {
        $type = Tools::getImageType($this->tempFile);
        $r = $this->resource;

        if ($angle != 0) {
            $r = Tools::rotate($r, $type, $angle);
        }

        if ($scale != 1) {
            $r = Tools::scale($r, $type, $scale);
        }

        $viewWidth = !isset($viewWidth) ? Tools::getWidth($r) : $viewWidth;
        $viewHeight = !isset($viewHeight) ? Tools::getHeight($r) : $viewHeight;
        $side = min($viewWidth, $viewHeight);
        $x = $x < 0 ? 0 : $x;
        $y = $y < 0 ? 0 : $y;
        $r = Tools::crop($r, $x, $y, $side, $side);

        foreach ($this->sizes as $dir => $size) {
            if ($dir == 'small') {
                $targetType = IMAGETYPE_GIF;
                $targetExt = 'gif';
            } else {
                $targetType = Tools::getImageType($this->tempFile);
                $targetExt = $this->getExtension();
            }
            $file = sprintf("%s/%s/%06d/%s-%d-%s.%s", $this->path, $dir, intval($user->getUserid()) / 1000, 'file', $user->getUserid(), $this->version, $targetExt);
            $dirName = dirname($file);

            if (!is_dir($dirName)) {
                mkdir($dirName, 0755, true);
            }

            if ($dir == 'original') {
                Tools::save($this->resource, $file, $targetType);
            } else {
                $_r = Tools::resize($r, $type, $size, $size);
                Tools::save($_r, $file, $targetType);
                imagedestroy($_r);
            }
        }
        imagedestroy($r);
        imagedestroy($this->resource);
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    public function getExtension()
    {
        return Tools::getImageExtension(Tools::getImageType($this->tempFile));
    }
}
