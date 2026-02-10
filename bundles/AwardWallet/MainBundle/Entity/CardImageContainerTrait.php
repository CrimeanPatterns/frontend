<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;

/**
 * Class CardImageTrait.
 *
 * @property CardImage[]|PersistentCollection $cardImages
 */
trait CardImageContainerTrait
{
    /**
     * @return CardImage[]|PersistentCollection
     */
    public function getCardImages()
    {
        return $this->cardImages;
    }

    /**
     * @param CardImage[]|PersistentCollection $cardImages
     * @return $this
     */
    public function setCardImages($cardImages)
    {
        $this->cardImages = $cardImages;

        return $this;
    }

    /**
     * @return $this
     */
    public function addCardImage(CardImage $cardImage)
    {
        unset($this->cardImages[$cardImage->getKind()]);
        $this->cardImages[$cardImage->getKind()] = $cardImage;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeCardImage(CardImage $cardImage)
    {
        if (is_array($this->cardImages)) {
            foreach ($this->cardImages as $key => $iterCardImage) {
                if ($iterCardImage === $cardImage) {
                    unset($this->cardImages[$key]);

                    break;
                }
            }
        } elseif ($this->cardImages instanceof Collection) {
            $this->cardImages->removeElement($cardImage);
        } else {
            throw new \RuntimeException('CardImages are uninitialized');
        }

        return $this;
    }
}
