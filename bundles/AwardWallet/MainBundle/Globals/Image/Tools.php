<?php

namespace AwardWallet\MainBundle\Globals\Image;

class Tools
{
    /**
     * @param string $filename
     * @return int
     */
    public static function getImageType($filename)
    {
        return getimagesize($filename)[2];
    }

    /**
     * @param int $imageType
     * @return string
     */
    public static function getImageExtension($imageType)
    {
        return image_type_to_extension($imageType, false);
    }

    /**
     * @param resource $resource
     * @return int
     */
    public static function getWidth($resource)
    {
        return imagesx($resource);
    }

    /**
     * @param resource $resource
     * @return int
     */
    public static function getHeight($resource)
    {
        return imagesy($resource);
    }

    /**
     * @param string $filename
     * @return resource
     */
    public static function getImageResource($filename)
    {
        switch (self::getImageType($filename)) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filename);

                break;

            case IMAGETYPE_GIF:
                return imagecreatefromgif($filename);

                break;

            case IMAGETYPE_PNG:
                return imagecreatefrompng($filename);

                break;
        }

        throw new \InvalidArgumentException('Invalid filename');
    }

    /**
     * @param resource $resource
     * @param string $filename
     * @param int $targetImageType
     * @param int $compression
     * @param int $permissions
     */
    public static function save($resource, $filename, $targetImageType, $compression = 75, $permissions = null)
    {
        if ($targetImageType == IMAGETYPE_GIF || $targetImageType == IMAGETYPE_PNG) {
            imagealphablending($resource, false);
            imagesavealpha($resource, true);
        }

        if ($targetImageType == IMAGETYPE_JPEG) {
            imagejpeg($resource, $filename, $compression);
        } elseif ($targetImageType == IMAGETYPE_GIF) {
            imagegif($resource, $filename);
        } elseif ($targetImageType == IMAGETYPE_PNG) {
            imagepng($resource, $filename);
        }

        if ($permissions != null) {
            chmod($filename, $permissions);
        }
    }

    /**
     * @param resource $resource
     * @param int $imageType
     * @param float $scale
     * @return resource
     */
    public static function scale($resource, $imageType, $scale)
    {
        $width = round(self::getWidth($resource) * $scale);
        $height = round(self::getHeight($resource) * $scale);

        return self::resize($resource, $imageType, $width, $height);
    }

    /**
     * @param resource $resource
     * @param int $imageType
     * @param int $width
     * @param int $height
     * @return resource
     */
    public static function resize($resource, $imageType, $width, $height)
    {
        $newImage = imagecreatetruecolor($width, $height);

        imagealphablending($newImage, false);
        $color = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $color);
        imagesavealpha($newImage, true);

        if (!imagecopyresampled($newImage, $resource, 0, 0, 0, 0, $width, $height, self::getWidth($resource), self::getHeight($resource))) {
            throw new \Exception("Can't create resize image");
        }

        return $newImage;
    }

    /**
     * @param resource $resource
     * @param int $imageType
     * @param int $degrees - 90, 180, 270
     */
    public static function rotate($resource, $imageType, $degrees = 90)
    {
        if ($imageType == IMAGETYPE_GIF || $imageType == IMAGETYPE_PNG) {
            imagealphablending($resource, false);
            imagesavealpha($resource, true);
        }
        $rotation = imagerotate($resource, $degrees, imagecolorallocatealpha($resource, 0, 0, 0, 127));

        if ($imageType == IMAGETYPE_GIF || $imageType == IMAGETYPE_PNG) {
            imagealphablending($rotation, false);
            imagesavealpha($rotation, true);
        }

        return $rotation;
    }

    /**
     * @param resource $resource
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return resource
     */
    public static function crop($resource, $x = 0, $y = 0, $width = null, $height = null)
    {
        $x = abs($x);
        $y = abs($y);
        $w = self::getWidth($resource);
        $h = self::getHeight($resource);
        $width = (!isset($width)) ? $w : $width;
        $height = (!isset($height)) ? $h : $height;

        if ($width > $w) {
            $width = $w;
        }

        if ($height > $h) {
            $height = $h;
        }

        if ($x > $w) {
            $x = $w - 1;
        }

        if ($y > $h) {
            $y = $h - 1;
        }

        if ($x + $width > $w) {
            $x -= $x + $width - $w;
        }

        if ($y + $height > $h) {
            $y -= $y + $height - $h;
        }

        $new = imagecreatetruecolor($width, $height);
        imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
        imagealphablending($new, false);
        imagesavealpha($new, true);

        imagecopyresampled($new, $resource, 0, 0, $x, $y, $width, $height, $width, $height);

        return $new;
    }
}
