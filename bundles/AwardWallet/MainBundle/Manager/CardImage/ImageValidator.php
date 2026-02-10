<?php

namespace AwardWallet\MainBundle\Manager\CardImage;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Manager\CardImage\Exception\ImageException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageValidator
{
    private const ERRORS_MAP = [
        UPLOAD_ERR_INI_SIZE => 'The file exceeds your upload limit 1.',
        UPLOAD_ERR_FORM_SIZE => 'The file exceeds the upload limit 2.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_CANT_WRITE => 'The file could not be written.',
        UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing destination.',
        UPLOAD_ERR_EXTENSION => 'File upload was stopped by a server.',
    ];
    /**
     * @var int
     */
    private $maxBytes;
    /**
     * @var int
     */
    private $maxWidth;
    /**
     * @var int
     */
    private $maxHeight;
    /**
     * @var string
     */
    private $mime;
    /**
     * @var int
     */
    private $size;
    /**
     * @var int
     */
    private $width;
    /**
     * @var int
     */
    private $height;

    public function __construct(int $maxBytes, int $maxWidth, int $maxHeight)
    {
        $this->maxBytes = $maxBytes;
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function validateContent(string $filename, string $content): void
    {
        if (!preg_match('/^[a-zA-Z0-9-]+\.[a-z]{1,5}$/', $filename)) {
            $this->ex('Invalid image name.');
        }

        $this->size = strlen($content);

        if ($this->size > $this->maxBytes) {
            $this->ex('Invalid image size 2: ' . $this->size);
        }

        if (
            !\is_array($imageData = @getimagesizefromstring($content, $_))
            || (\count($imageData) < 5)
        ) {
            $this->ex('Invalid image format.');
        }

        if (($this->width = $imageData[0]) >= $this->maxWidth) {
            $this->ex('Invalid image width.');
        }

        if (($this->height = $imageData[1]) >= $this->maxHeight) {
            $this->ex('Invalid image height.');
        }

        if (StringHandler::isEmpty($this->mime = $imageData['mime'])) {
            $this->ex('Invalid image format info.');
        }
    }

    public function validateUpload(UploadedFile $file): void
    {
        if ($errorCode = $file->getError()) {
            if (isset(self::ERRORS_MAP[$errorCode])) {
                $this->ex(self::ERRORS_MAP[$errorCode]);
            }

            $this->ex('The file was not uploaded due to an unknown error.');
        }

        $filesize = \filesize($file->getPathname());

        if ($filesize > $this->maxBytes) {
            $this->ex('Invalid image size 1: ' . $filesize);
        }
    }

    private function ex(string $error)
    {
        throw new ImageException($error);
    }
}
