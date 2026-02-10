<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Email;
use Doctrine\ORM\Mapping as ORM;

trait AIWarningTrait
{
    use SourceTrait;

    /**
     * @var bool
     * @ORM\Column(name="ShowAIWarning", type="boolean", nullable=false)
     */
    private $showAIWarning = true;

    public function isShowAIWarning(): bool
    {
        return $this->showAIWarning;
    }

    public function setShowAIWarning(bool $showAIWarning): self
    {
        $this->showAIWarning = $showAIWarning;

        return $this;
    }

    /**
     * @return bool true if AI warning should be shown for this itinerary
     */
    public function isShowAIWarningForEmailSource(): bool
    {
        if (!$this->isShowAIWarning()) {
            return false;
        }

        $sources = $this->getSources();

        foreach ($sources as $source) {
            if ($source instanceof Email && $source->isGpt()) {
                return true;
            }
        }

        return false;
    }
}
