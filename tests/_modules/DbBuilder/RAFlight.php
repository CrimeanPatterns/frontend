<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Globals\StringHandler;

class RAFlight extends AbstractDbEntity
{
    public function __construct(
        string $providerCode,
        array $fields = []
    ) {
        parent::__construct(array_merge(
            [
                'RequestID' => StringHandler::getRandomCode(32),
                'SearchDate' => date('Y-m-d H:i:s', strtotime('-10 day')),
                'Airlines' => 'AA',
                'StandardSegmentCOS' => 'business',
                'FareClasses' => 'T',
                'AwardType' => 'Saver',
                'FlightType' => 2,
            ],
            $fields,
            [
                'Provider' => $providerCode,
            ]
        ));
    }
}
