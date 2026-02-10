<?php

namespace AwardWallet\MainBundle\Manager\CardImage;

use AwardWallet\CardImageParser\CardRecognitionResult;
use AwardWallet\CardImageParser\DOMConverter\DOMConverter;
use AwardWallet\MainBundle\Entity\CardImage;

class CardRecognitionResultFactory
{
    /**
     * @var DOMConverter
     */
    protected $domConverter;

    public function __construct(DOMConverter $domConverter)
    {
        $this->domConverter = $domConverter;
    }

    /**
     * @param CardImage[] $cardImagesByKind
     * @return CardRecognitionResult|null
     */
    public function makeCardRecognitionResult(array $cardImagesByKind)
    {
        $cardImageResultConstructorArgs = [];

        foreach ([CardImage::KIND_FRONT, CardImage::KIND_BACK] as $kind) {
            if (
                isset($cardImagesByKind[$kind])
                && ($cardImage = $cardImagesByKind[$kind])
                && $cardImage->hasGoogleVisionResposne()
                && ($googleResponse = $cardImage->getGoogleVisionResponse())
            ) {
                $cardImageResultConstructorArgs[] = $this->domConverter->createImageRecognitionResultFromVisionResult(
                    $googleResponse,
                    $cardImage->getWidth(),
                    $cardImage->getHeight()
                );
            } else {
                $cardImageResultConstructorArgs[] = null;
            }
        }

        if (!array_filter($cardImageResultConstructorArgs)) {
            return null;
        }

        return new CardRecognitionResult(...$cardImageResultConstructorArgs);
    }
}
