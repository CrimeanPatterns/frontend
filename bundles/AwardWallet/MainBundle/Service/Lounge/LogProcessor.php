<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Service\LogProcessor as BaseLogProcessor;

class LogProcessor extends BaseLogProcessor
{
    public function __construct()
    {
        parent::__construct(
            'parsing_lounges',
            [],
            [
                Lounge::class => fn (Lounge $lounge): string => sprintf(
                    'lounge id: %s, iata: %s, terminal: %s',
                    $lounge->getId() ? sprintf('#%d', $lounge->getId()) : '<null>',
                    $lounge->getAirportCode(),
                    !empty($lounge->getTerminal()) ? $lounge->getTerminal() : '<null>'
                ),
                LoungeSource::class => fn (LoungeSource $lounge): string => sprintf(
                    'lounge-source id: %s, iata: %s, terminal: %s',
                    sprintf('%s#%s', $lounge->getSourceCode(), $lounge->getSourceId()),
                    $lounge->getAirportCode(),
                    !empty($lounge->getTerminal()) ? $lounge->getTerminal() : '<null>'
                ),
            ],
            ['parser', 'aircode', 'lounge', 'method', 'httpCode', 'url']
        );
    }
}
