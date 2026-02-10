<?php

namespace AwardWallet\MainBundle\Globals\Image;

class Image extends Tools
{
    public const BASE_RELATIVE_PATH = '/images/uploaded/';
    private const PATH_PATTERN = '%s/%s/%06d/%s-%d-%s.%s';

    protected $sizes = [
        'original' => 9999,
        'large' => 800,
        'medium' => 250,
        'small' => 64,
    ];

    protected $path;
    protected $version;
    protected $tempFile;
    protected $resource;

    /**
     * Image constructor.
     *
     * @param resource   $resource
     * @param string     $uploadedPath
     */
    public function __construct($resource, $uploadedPath = 'user', ?array $onlySizes = null)
    {
        $uploadedPath = mb_ereg_replace("([^\w\d])", '', $uploadedPath);
        $path = $_SERVER['DOCUMENT_ROOT'] . self::BASE_RELATIVE_PATH . trim($uploadedPath, '\\/') . '/';

        if (!is_dir($path) && !mkdir($path, 0777, true)) {
            throw new \RuntimeException('Can not access directory "' . $uploadedPath . '"');
        }

        $this->path = realpath($path);

        if (0 !== strpos($this->path, $_SERVER['DOCUMENT_ROOT'] . self::BASE_RELATIVE_PATH)) {
            throw new \RuntimeException('Access denied');
        }

        if (!empty($onlySizes)) {
            if ((bool) array_diff_key($onlySizes, $this->sizes)) {
                throw new \InvalidArgumentException('Unsupported size');
            }

            $this->sizes = $onlySizes;
        }

        $this->version = (int) microtime(true);
        $this->tempFile = tempnam(sys_get_temp_dir(), 'image');

        file_put_contents($this->tempFile, $resource);
        $this->resource = self::getImageResource($this->tempFile);
    }

    /**
     * @param int   $viewWidth
     * @param int   $viewHeight
     * @param int   $x
     * @param int   $y
     * @param float $scale
     * @param int   $angle - 90/180/270
     * @return $this
     * @throws \Exception
     */
    public function createImage(int $id, $viewWidth = null, $viewHeight = null, $x = 0, $y = 0, $scale = 1.0, $angle = 0): self
    {
        $type = self::getImageType($this->tempFile);
        $r = $this->resource;

        if ($angle != 0) {
            $r = self::rotate($r, $type, $angle);
        }

        if ($scale != 1) {
            $r = self::scale($r, $type, $scale);
        }

        $viewWidth = !isset($viewWidth) ? self::getWidth($r) : $viewWidth;
        $viewHeight = !isset($viewHeight) ? self::getHeight($r) : $viewHeight;
        $side = min($viewWidth, $viewHeight);
        $x = $x < 0 ? 0 : $x;
        $y = $y < 0 ? 0 : $y;
        $r = self::crop($r, $x, $y, $side, $side);

        foreach ($this->sizes as $dir => $size) {
            if ($dir == 'small') {
                $targetType = IMAGETYPE_GIF;
                $targetExt = 'gif';
            } else {
                $targetType = self::getImageType($this->tempFile);
                $targetExt = $this->getExtension();
            }
            $file = sprintf(self::PATH_PATTERN, $this->path, $dir, $id / 1000, 'file', $id, $this->version, $targetExt);
            $dirName = dirname($file);

            if (!is_dir($dirName) && !mkdir($dirName, 0777, true)) {
                throw new \RuntimeException('Can not access directory "' . $dirName . '"');
            }

            if ($dir == 'original') {
                self::save($this->resource, $file, $targetType);
            } else {
                $_r = self::resize($r, $type, $size, $size);
                self::save($_r, $file, $targetType);
                imagedestroy($_r);
            }
        }
        imagedestroy($r);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function resizeImage(int $id, string $sizeDir, int $maxWidth, int $maxHeight = 0): self
    {
        !empty($maxHeight) ?: $maxHeight = $maxWidth;
        $type = self::getImageType($this->tempFile);
        $r = $this->resource;

        if ('small' === $sizeDir) {
            $targetType = IMAGETYPE_GIF;
            $targetExt = 'gif';
        } else {
            $targetType = self::getImageType($this->tempFile);
            $targetExt = $this->getExtension();
        }

        $file = sprintf(self::PATH_PATTERN, $this->path, $sizeDir, $id / 1000, 'file', $id, $this->version, $targetExt);
        $dirName = dirname($file);

        if (!is_dir($dirName) && !mkdir($dirName, 0777, true)) {
            throw new \RuntimeException('Can not access directory "' . $dirName . '"');
        }

        $widthOrigin = self::getWidth($this->resource);
        $heightOrigin = self::getHeight($this->resource);
        $ratioOrigin = $widthOrigin / $heightOrigin;

        $width = $maxWidth;
        $height = $maxHeight;

        if ($width / $height > $ratioOrigin) {
            $width = $maxWidth * $ratioOrigin;
        } else {
            $height = $maxWidth / $ratioOrigin;
        }

        $_r = self::resize($r, $type, $width, $height);
        self::save($_r, $file, $targetType);

        imagedestroy($_r);
        imagedestroy($r);

        return $this;
    }

    public function destroyImage()
    {
        imagedestroy($this->resource);
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getExtension(): string
    {
        return self::getImageExtension(self::getImageType($this->tempFile));
    }

    public static function getPath(int $id, string $dir, string $size, int $version, string $extension): string
    {
        return \sprintf(self::PATH_PATTERN, \rtrim(self::BASE_RELATIVE_PATH, '/'), $dir . '/' . $size, $id / 1000, 'file', $id, $version, $extension);
    }
}
