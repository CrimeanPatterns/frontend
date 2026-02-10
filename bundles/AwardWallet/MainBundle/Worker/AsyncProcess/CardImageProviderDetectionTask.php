<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Globals\StringUtils;

class CardImageProviderDetectionTask extends Task
{
    /**
     * @var int
     */
    public $cardImageId;

    public function __construct(CardImage $cardImage)
    {
        parent::__construct("aw.async.executor.card_image_provider_detection", StringUtils::getRandomCode(20));

        $this->cardImageId = $cardImage->getCardImageId();
    }
}
