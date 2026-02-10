<?php

return [
    'ContactName' => 'TestContactName',
    'ContactEmail' => 'test@gmail.com',
    'ContactPhone' => '123-456-789',
    'Status' => 0,
    'CreateDate' => date('Y-m-d H:i:s'),
    'LastUpdateDate' => date('Y-m-d H:i:s'),
    'CabinFirst' => 1,
    'CabinBusiness' => 1,
    'CabinEconomy' => 0,
    'CabinPremiumEconomy' => 0,
    'BookerUserID' => CommonUser::$booker_id,
    'BusinessTravel' => 0,
    'UserID' => CommonUser::$admin_id,
];
