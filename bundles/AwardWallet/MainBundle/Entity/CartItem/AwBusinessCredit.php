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
class AwBusinessCredit extends CartItem implements TranslationContainerInterface
{
    public const TYPE = 15;

    protected $price = 0;

    public function getMonthlyEstimate()
    {
        return $this->userdata;
    }

    public function setMonthlyEstimate($monthlyEstimate)
    {
        $this->userdata = $monthlyEstimate;
    }

    public function translate(TranslateArgs $args)
    {
        $translator = $args->getTranslator();

        $name = $translator->trans('aw.credit');

        $this->setName($name);
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('aw.credit'))->setDesc('AwardWallet Credit'),
        ];
    }
}
