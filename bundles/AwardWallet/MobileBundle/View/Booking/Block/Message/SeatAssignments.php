<?php

namespace AwardWallet\MobileBundle\View\Booking\Block\Message;

use AwardWallet\MobileBundle\View\Booking\Block\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

class SeatAssignments extends Message
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    public $phoneNumbers;

    public function setMessage(string $message): SeatAssignments
    {
        $this->message = $message;

        return $this;
    }

    public function addPhoneNumber(string $provider, string $phone, TranslatorInterface $tr): self
    {
        if (!is_array($this->phoneNumbers)) {
            $this->phoneNumbers = [];
        }
        $this->phoneNumbers[] = [
            'provider' => [
                'name' => $tr->trans('booking.forms.seat_assignments.provider', [], 'booking'),
                'value' => $provider,
            ],
            'phone' => [
                'name' => $tr->trans('booking.forms.seat_assignments.phone', [], 'booking'),
                'value' => $phone,
            ],
        ];

        return $this;
    }
}
