<?php

namespace AwardWallet\MainBundle\Service\Booking;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\AbRequest;

/**
 * @NoDI()
 */
class MessageFormatter
{
    public static function getMessageReplacedVars(AbRequest $abRequest): array
    {
        $serviceName = strtolower(($bookerInfo = $abRequest->getBooker()->getBookerInfo()) ? $bookerInfo->getServiceName() : '');

        // don't know how to get "awardwallet", there is awardwallet llc everywhere
        if (strpos($serviceName, 'awardwallet') !== false) {
            $serviceName = 'awardwallet';
        }

        $ycbFrameQuery = [
            'noframe' => 'true',
            'skipHeaderFooter' => 'true',
            'FNAME' => $abRequest->getUser()->getFirstname(),
            'LNAME' => $abRequest->getUser()->getLastname(),
            'EMAIL' => $abRequest->getUser()->getEmail(),
            'PHONE' => $abRequest->getContactPhone(),
            'REQUEST_ID' => $abRequest->getAbRequestID(),
            'REQUEST_CODE' => $abRequest->getHash(),
        ];

        return [
            '{{ name }}' => $abRequest->ContactFirstName(),
            '{{ ' . $serviceName . '_schedule }}' => "<iframe src='https://{$serviceName}.youcanbook.me/?" . http_build_query($ycbFrameQuery) . "' style='width:100%;height:900px;border:0px;background-color:transparent;' frameborder='0' allowtransparency='true'></iframe>",
        ];
    }
}
