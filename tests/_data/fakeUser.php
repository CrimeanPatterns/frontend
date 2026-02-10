<?php

use AwardWallet\MainBundle\Globals\StringHandler;

return call_user_func(
    function () {
        return [
            'Login' => $login = 'test-user-' . substr(bin2hex(openssl_random_pseudo_bytes(7)), 0, 13),
            'Pass' => '$2y$04$8D8o2s3q7bkSRltaEU89fO9S.D/APIQaF2H7HDAvamzkwyPAbfazO', // awdeveloper
            'Password' => 'awdeveloper', // virtual field, removed before fixture insertion
            'FirstName' => 'Ragnar',
            'LastName' => 'Petrovich',
            'Email' => $login . '@fakemail.com',
            'City' => 'Las Vegas',
            'CreationDateTime' => $now = (new \DateTime())->format('Y-m-d H:i:s'),
            'EmailVerified' => EMAIL_VERIFIED,
            'CountryID' => 230, // USA
            'AccountLevel' => ACCOUNT_LEVEL_FREE,
            'RefCode' => \AwardWallet\MainBundle\Globals\StringUtils::getPseudoRandomString(5),
            'Secret' => StringHandler::getRandomCode(32, true),
        ];
    }
);
