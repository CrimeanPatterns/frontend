<?php

namespace AwardWallet\MainBundle\Service\Hotels;

use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use AwardWallet\MainBundle\Service\Storage\AbstractS3Storage;
use Aws\S3\S3Client;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;

class Thumbnail extends AbstractS3Storage
{
    public const BUCKET = 'hotels-thumbnails';
    public const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/webp',
        'image/png',
    ];
    private const FILE_FETCH_TIMEOUT_SECONDS = 3;

    private LoggerInterface $logger;
    private KernelInterface $kernel;

    public function __construct(
        LoggerInterface $logger,
        Encryptor $encryptor,
        S3Client $s3Client,
        string $s3BucketPrefix,
        KernelInterface $kernel
    ) {
        $bucket = sprintf('aw-%s-%s', $s3BucketPrefix, self::BUCKET);
        parent::__construct($encryptor, $s3Client, $bucket, $logger);

        $this->logger = $logger;
        $this->kernel = $kernel;
    }

    public function processing(string $key, string $urlOrContent): ?bool
    {
        if ($this->isExists($key)) {
            return true;
        }

        if (false !== stripos($urlOrContent, '<img')) {
            $src = $this->fetchImgSrc($urlOrContent);

            if (null !== $src) {
                return $this->fetchAndWriteFile($key, $src);
            }

            return false;
        }

        if (strlen($urlOrContent) > 1500) {
            $type = $this->getMimeTypeByContent($urlOrContent);

            if (0 === strpos($type, 'image/')) {
                $this->put($key, $urlOrContent);

                return true;
            }

            return false;
        }

        return $this->fetchAndWriteFile($key, $urlOrContent);
    }

    public function fetchAndWriteFile(
        string $key,
        string $fileUrl,
        bool $isReplace = false,
        array $allowMimeTypes = self::IMAGE_MIME_TYPES
    ): ?bool {
        if (!$isReplace && $this->isExists($key)) {
            return null;
        } elseif ($isReplace && $this->isExists($key)) {
            $this->delete($key);
        }

        // maybe for hhonors contained html
        if (false !== stripos($fileUrl, '<img')) {
            $fileUrl = $this->fetchImgSrc($fileUrl);

            if (null === $fileUrl) {
                return false;
            }
        }

        $content = $this->fetchFile($fileUrl);

        if (false !== $content) {
            if (!in_array($this->getMimeTypeByContent($content), $allowMimeTypes, true)) {
                return false;
            }

            $this->put($key, $content);
        }

        return true;
    }

    public function getResponse(string $key, bool $isBrowserWebpCompatible = true): ?Response
    {
        $defaultThumbnailPath = $this->kernel->getRootDir() . '/../web/images/hotels/hotels-default-thumbnail.jpg';

        if (!$this->isExists($key)) {
            $fileStream = file_get_contents($defaultThumbnailPath);
        } else {
            $fileStream = $this->get($key);

            if (null === $fileStream) {
                $fileStream = file_get_contents($defaultThumbnailPath);
            }
        }

        if (!$isBrowserWebpCompatible) {
            $im = imagecreatefromstring((string) $fileStream);
            ob_start();
            imagejpeg($im);
            $content = ob_get_contents();
            ob_end_clean();
            imagedestroy($im);

            $size = strlen($content);
            $mimeType = 'image/jpeg';
        } else {
            if ($fileStream instanceof StreamInterface) {
                $size = $fileStream->getSize();
                $content = $fileStream->getContents();
            } else {
                $content = $fileStream;
                $size = strlen($fileStream);
            }

            $mimeType = $this->getMimeTypeByContent($content);
        }

        $response = new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Length' => $size,
                'Content-Type' => $mimeType,
                'Connection' => 'Keep-Alive',
                'Accept-Ranges' => 'bytes',
                'Pragma' => '',
            ]
        );

        $expireDate = (new \DateTime())->modify('+1 month');
        $response
            ->setExpires($expireDate)
            ->setLastModified(new \DateTime())
            ->setCache(['private' => true, 'max_age' => $expireDate->getTimestamp() - time()])
            ->headers->set(
                'Content-Disposition',
                $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $key)
            );

        return $response;
    }

    private function getMimeTypeByContent(string $content): string
    {
        /*
        $res = fopen('php://memory', 'w+b');
        fwrite($res, $content);
        $contentType = mime_content_type($res);
        fclose($res);

        return $contentType;
        */

        return (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);
    }

    private function fetchImgSrc($content): ?string
    {
        preg_match_all("/<img.*src\s*=\s*[\"']([^\"']+)[\"'][^>]*>/i", $content, $matches);

        if (!empty($matches[1][0])) {
            return htmlspecialchars_decode($matches[1][0]);
        }
    }

    private function fetchFile($url)
    {
        $options = [
            'http' => [
                'method' => 'GET',
                'timeout' => self::FILE_FETCH_TIMEOUT_SECONDS,
                'header' => implode("\r\n", [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/118.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-us,en;q=0.5',
                    'Connection' => 'Keep-Alive',
                    'Expect' => '',
                ]),
            ],
        ];

        try {
            $file = file_get_contents($url, false, stream_context_create($options));

            if (false !== $file) {
                return $file;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Hotels fetchFile: ' . $e->getMessage(), ['url' => $url]);
        }

        return false;
    }
}
