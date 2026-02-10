<?php

namespace AwardWallet\MainBundle\Manager\CardImage;

use AwardWallet\CardImageParser\CreditCardDetection\Rectangle;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\CardImage\Exception\ImageException;
use Intervention\Image\AbstractFont;
use Intervention\Image\AbstractShape;
use Intervention\Image\ImageManagerStatic;
use Psr\Log\LoggerInterface;

class ImageCensor
{
    /**
     * @var CardImageManager
     */
    protected $cardImageManager;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var string
     */
    protected $fontFile;
    /**
     * @var bool
     */
    private $dryRun;

    public function __construct(
        string $fontFile,
        CardImageManager $cardImageManager,
        LoggerInterface $logger,
        bool $dryRun = false
    ) {
        $this->cardImageManager = $cardImageManager;
        $this->logger = $logger;
        $this->fontFile = $fontFile;
        $this->dryRun = $dryRun;
    }

    /**
     * @param Rectangle[] $rects
     */
    public function censorImage(CardImage $originalCardImage, array $rects): CardImage
    {
        if (!$rects) {
            return $originalCardImage;
        }

        try {
            $startTimer = microtime(true);
            $originalImageContent = $this->cardImageManager->getImageContent($originalCardImage);

            if (null === $originalImageContent) {
                return $originalCardImage;
            }

            $censoredImageContent = $this->doCensorImage($rects, $originalImageContent, $originalCardImage->getWidth(), $originalCardImage->getHeight());

            if ($originalCardImage->hasContainer()) {
                $user = $originalCardImage->getContainer()->getUserid();
            } else {
                $user = $originalCardImage->getUser();
            }

            if (!$user) {
                $this->logger->critical(sprintf('Credit card image ($s) censoring error: empty user', $originalCardImage->getCardImageId()));

                return $originalCardImage;
            }

            try {
                $censoredCardImage = $this->cardImageManager->saveImage(
                    $user,
                    hash('sha256', hash('sha256', $censoredImageContent) . StringUtils::getPseudoRandomString(64)) . '-censored.jpg',
                    $censoredImageContent
                );
            } catch (ImageException $e) {
                $this->logger->critical(sprintf('Credit card image ($s) censoring error: ' . $e->getMessage(), $originalCardImage->getCardImageId()));

                return $originalCardImage;
            }

            $this->logger->warning('credit card image censorship', [
                'original_content_size' => strlen($originalImageContent),
                'censored_content_size' => strlen($censoredImageContent),
                'time' => round(microtime(true) - $startTimer, 1),
                'cardImageId' => $originalCardImage->getCardImageId(),
            ]);

            return $censoredCardImage;
        } catch (\Throwable $e) {
            $this->logger->critical(sprintf('Credit card image ($s) censoring error: %s', $originalCardImage->getCardImageId(), $e->getMessage()));

            return $originalCardImage;
        }
    }

    /**
     * @param Rectangle[] $rects
     * @return string new image content
     */
    protected function doCensorImage(array $rects, string $imageContent, int $width, int $height): string
    {
        $img = ImageManagerStatic::make($imageContent);
        /** @var Rectangle $maxRect */
        $maxRect = $rects[0];

        foreach ($rects as $rect) {
            if ($rect->getArea() > $maxRect->getArea()) {
                $maxRect = $rect;
            }

            $img->rectangle(
                $rectLeftPx = (int) ($rect->left * $width / 100),
                $rectTopPx = (int) ($rect->top * $height / 100),
                (int) (($rect->left + $rect->width) * $width / 100),
                (int) (($rect->top + $rect->height) * $height / 100),
                function (AbstractShape $shape) {
                    $shape->background('#000000');
                }
            );
        }

        $fontSize = ($maxRect->width * $width / 100 * 0.5 / 8); // 0.5 - text takes ~0.5 of rect width, 8 - length of text

        $img->text(
            'CENSORED',
            (int) (($maxRect->left + ($maxRect->width / 2)) * $width / 100),
            (int) (($maxRect->top + ($maxRect->height / 2)) * $height / 100),
            function (AbstractFont $font) use ($fontSize) {
                $font->file($this->fontFile); // use gd default
                $font->align('center');
                $font->valign('center');
                $font->color('#ffffff');
                $font->size($fontSize);
            }
        );

        return $img->encode('jpg', 85);
    }
}
