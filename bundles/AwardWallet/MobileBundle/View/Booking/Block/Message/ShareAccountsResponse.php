<?php

namespace AwardWallet\MobileBundle\View\Booking\Block\Message;

use AwardWallet\MobileBundle\View\Booking\Block\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShareAccountsResponse extends Message
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    public $accounts;

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function addAccount(string $provider, ?string $balance, ?string $owner, TranslatorInterface $tr): self
    {
        if (!is_array($this->accounts)) {
            $this->accounts = [];
        }
        $this->accounts[] = [
            'provider' => [
                'name' => $tr->trans('booking.display.name', [], 'booking'),
                'value' => $provider,
            ],
            'balance' => [
                'name' => $tr->trans('booking.balance', [], 'booking'),
                'value' => $balance,
            ],
            'owner' => [
                'name' => $tr->trans('booking.owner', [], 'booking'),
                'value' => $owner,
            ],
        ];

        return $this;
    }
}
