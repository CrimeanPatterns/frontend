<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @ORM\Entity
 */
class AwBusiness extends CartItem implements TranslationContainerInterface
{
    public const TYPE = 5;

    protected $tariffDescription;

    protected $onlyRecurring = false;

    protected $recurringAmount = 0;

    protected $startRecurringDate;

    public function setPaidUsers($paidUsers)
    {
        $this->setUserdata($paidUsers);

        return $this;
    }

    public function getPaidUsers()
    {
        return $this->getUserdata();
    }

    public function setTariffDescription($tariffDescription)
    {
        $this->tariffDescription = $tariffDescription;

        return $this;
    }

    public function getTariffDescription()
    {
        return $this->tariffDescription;
    }

    /**
     * @return bool
     */
    public function isOnlyRecurring()
    {
        return $this->onlyRecurring;
    }

    /**
     * @param bool $onlyRecurring
     */
    public function setOnlyRecurring($onlyRecurring)
    {
        $this->onlyRecurring = $onlyRecurring;

        return $this;
    }

    /**
     * @return int
     */
    public function getRecurringAmount()
    {
        return $this->recurringAmount;
    }

    /**
     * @param int $recurringAmount
     */
    public function setRecurringAmount($recurringAmount)
    {
        $this->recurringAmount = $recurringAmount;

        return $this;
    }

    public function getStartRecurringDate()
    {
        return $this->startRecurringDate;
    }

    /**
     * @param \DateTime $startRecurringDate
     */
    public function setStartRecurringDate($startRecurringDate)
    {
        $this->startRecurringDate = $startRecurringDate;

        return $this;
    }

    public function translate(TranslateArgs $args)
    {
        $translator = $args->getTranslator();

        if (!$this->isOnlyRecurring()) {
            $name = $translator->trans('cart.item.type.business', [
                '%count%' => $this->getPaidUsers(),
                '%tariffDesc%' => $this->getTariffDescription(),
            ]);
        } else {
            $name = $translator->trans('cart.item.type.business.only-recurring', [
                '%count%' => $this->getPaidUsers(),
                '%tariffDesc%' => $this->getTariffDescription(),
                '%amount%' => $args->getLocalizer()->formatCurrency($this->getRecurringAmount(), 'USD'),
                '%date%' => $args->getLocalizer()->formatDateTime($this->getStartRecurringDate(), 'short', null),
            ]);
        }
        $this->setName($name);
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.business'))->setDesc('Payment for %count% users of AwardWallet Business Service (%tariffDesc%). This is a yearly recurring payment.'),
            (new Message('cart.item.type.business.only-recurring'))->setDesc('Payment for %count% users of AwardWallet Business Service (%tariffDesc%). Recurring payment: %amount% (Starting %date%)'),
        ];
    }
}
