<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

class TransferOptions
{
    private TranslatorInterface $translator;
    private DateTimeIntervalFormatter $intervalFormatter;

    public function __construct(TranslatorInterface $translator, DateTimeIntervalFormatter $intervalFormatter)
    {
        $this->translator = $translator;
        $this->intervalFormatter = $intervalFormatter;
    }

    public function get(): array
    {
        $requestDateChoice = [];
        $requestDateChoice[0] = $this->translator->trans('less-1-hour-ago');

        for ($i = 1; $i < 24; $i++) {
            $requestDateChoice[$i] = $this->intervalFormatter->shortFormatViaDateTimes(
                new \DateTime(),
                new \DateTime('-' . $i . ' hour')
            );
        }
        $requestDateChoice[24] = str_replace('23', '24', $requestDateChoice[23]);
        $requestDateChoice[25] = $this->translator->trans('more-than-24-hours-ago');

        return $requestDateChoice;
    }
}
