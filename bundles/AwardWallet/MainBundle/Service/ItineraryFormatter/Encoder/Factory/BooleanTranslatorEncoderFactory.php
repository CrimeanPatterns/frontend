<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory;

use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanTranslatorEncoderFactory
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function make(Trans $trueTrans, Trans $falseTrans): EncoderInterface
    {
        return new CallableEncoder(function ($value) use ($trueTrans, $falseTrans) {
            $trans = $value ? $trueTrans : $falseTrans;

            return $this->translator->trans(
                $trans->getId(),
                $trans->getParameters(),
                $trans->getDomain(),
                $trans->getDomain()
            );
        });
    }
}
