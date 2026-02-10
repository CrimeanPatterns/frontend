<?php

return call_user_func(
    function () {
        return function ($programName, $value, $description, $userId) {
            return [
                'UserID' => $userId,
                'Value' => $value,
                'Description' => $description,
                'ProgramName' => $programName,
                'Kind' => PROVIDER_KIND_AIRLINE,
                'TypeID' => \AwardWallet\MainBundle\Entity\Providercoupon::TYPE_COUPON,
                'CardNumber' => '123456789',
                'CreationDate' => $now = (new \DateTime())->format('Y-m-d H:i:s'),
            ];
        };
    }
);
