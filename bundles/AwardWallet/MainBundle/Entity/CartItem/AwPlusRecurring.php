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
class AwPlusRecurring extends CartItem implements TranslationContainerInterface
{
    public const TYPE = 14;

    protected $price = 0;

    protected $recurringAmount;

    protected $period = 6;

    public function setRecurringAmount($amount)
    {
        $this->setUserdata($amount);

        return $this;
    }

    public function getRecurringAmount()
    {
        return $this->getUserdata();
    }

    public function translate(TranslateArgs $args)
    {
        $this->setName(
            $args->getTranslator()->trans('cart.item.type.awplus-recurring', [
                '%amount%' => $args->getLocalizer()->formatCurrency($this->getRecurringAmount(), 'USD', false),
                '%period%' => $this->getPeriod(),
            ])
        );
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.awplus-recurring'))->setDesc('Set up recurring payment of %amount% every %period% months'),
        ];
    }

    public function getPeriod()
    {
        return $this->period;
    }

    public function setPeriod($period)
    {
        $this->period = $period;
    }
}
