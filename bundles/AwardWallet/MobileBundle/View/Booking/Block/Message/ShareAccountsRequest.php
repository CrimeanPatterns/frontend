<?php

namespace AwardWallet\MobileBundle\View\Booking\Block\Message;

class ShareAccountsRequest extends ShareAccountsResponse
{
    /**
     * @var string
     */
    public $shareButton;

    /**
     * @var string
     */
    public $shareButtonUrl;

    public function setShareButton(string $shareButton): self
    {
        $this->shareButton = $shareButton;

        return $this;
    }

    public function setShareButtonUrl(string $shareButtonUrl): self
    {
        $this->shareButtonUrl = $shareButtonUrl;

        return $this;
    }
}
