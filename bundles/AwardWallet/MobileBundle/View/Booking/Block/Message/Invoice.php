<?php

namespace AwardWallet\MobileBundle\View\Booking\Block\Message;

use AwardWallet\MobileBundle\View\Booking\Block\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

class Invoice extends Message
{
    /**
     * @var string
     */
    public $intro;

    /**
     * @var string
     */
    public $bookerLogoSrc;

    /**
     * @var string
     */
    public $bookerAddress;

    /**
     * @var string
     */
    public $bookerEmail;

    /**
     * @var array
     */
    public $header;

    /**
     * @var array
     */
    public $items;

    /**
     * @var array
     */
    public $miles;

    /**
     * @var string
     */
    public $totalLabel;

    /**
     * @var string
     */
    public $total;

    /**
     * @var bool
     */
    public $isPaid;

    /**
     * @var string
     */
    public $footer;

    /**
     * @var string
     */
    public $proceedButton;

    /**
     * @var string
     */
    public $proceedButtonUrl;

    public function setIntro(string $intro): Invoice
    {
        $this->intro = $intro;

        return $this;
    }

    public function setBookerLogoSrc(string $bookerLogoSrc): Invoice
    {
        $this->bookerLogoSrc = $bookerLogoSrc;

        return $this;
    }

    public function setBookerAddress(string $bookerAddress): Invoice
    {
        $this->bookerAddress = $bookerAddress;

        return $this;
    }

    public function setBookerEmail(?string $bookerEmail): Invoice
    {
        $this->bookerEmail = $bookerEmail;

        return $this;
    }

    public function addHeader(string $name, $value, ?string $type = null): Invoice
    {
        $header = [
            'name' => $name,
            'value' => $value,
        ];

        if (isset($type)) {
            $header['type'] = $type;
        }

        if (!is_array($this->header)) {
            $this->header = [];
        }

        $this->header[] = $header;

        return $this;
    }

    public function addItem(
        string $description,
        string $quantity,
        string $rate,
        string $amount,
        TranslatorInterface $tr,
        ?string $discount = null,
        ?string $total = null
    ): Invoice {
        $item = [
            'description' => $description,
            'quantity' => [
                'name' => $tr->trans('user-pay.column.quantity.title', [], 'messages'),
                'value' => $quantity,
            ],
            'rate' => [
                'name' => $tr->trans('invoice.message.rate', [], 'booking'),
                'value' => $rate,
            ],
            'amount' => [
                'name' => $tr->trans('business.transactions.amount', [], 'messages'),
                'value' => $amount,
            ],
        ];

        if (isset($discount, $total)) {
            $item['amount']['discount'] = $discount;
            $item['amount']['total'] = $total;
        }

        if (!is_array($this->items)) {
            $this->items = [];
        }

        $this->items[] = $item;

        return $this;
    }

    public function addMiles(string $description, string $name, string $value): Invoice
    {
        if (!is_array($this->miles)) {
            $this->miles = [];
        }
        $this->miles[] = [
            'description' => $description,
            'name' => $name,
            'value' => $value,
        ];

        return $this;
    }

    public function setTotalLabel(string $totalLabel): Invoice
    {
        $this->totalLabel = $totalLabel;

        return $this;
    }

    public function setTotal(string $total): Invoice
    {
        $this->total = $total;

        return $this;
    }

    public function setIsPaid(bool $isPaid): Invoice
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    public function setFooter(string $footer): Invoice
    {
        $this->footer = $footer;

        return $this;
    }

    public function setProceedButton(string $proceedButton): Invoice
    {
        $this->proceedButton = $proceedButton;

        return $this;
    }

    public function setProceedButtonUrl(string $proceedButtonUrl): Invoice
    {
        $this->proceedButtonUrl = $proceedButtonUrl;

        return $this;
    }
}
