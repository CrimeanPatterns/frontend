<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\PersistentCollection;

interface CardImageContainerInterface
{
    /**
     * @return PersistentCollection|CardImage[]
     */
    public function getCardImages();

    /**
     * @param PersistentCollection|CardImage[] $cardImages
     * @return $this
     */
    public function setCardImages($cardImages);

    /**
     * @return $this
     */
    public function addCardImage(CardImage $cardImage);

    /**
     * @return $this
     */
    public function removeCardImage(CardImage $cardImage);
}
