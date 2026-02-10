<?php

return function ($name, $code) {
    return [
        'Name' => $name,
        'DisplayName' => $name,
        'ShortName' => $name,
        'Code' => $code,
        'Kind' => PROVIDER_KIND_AIRLINE,
        'State' => PROVIDER_ENABLED,
        'LoginCaption' => 'Login',
        'LoginRequired' => 1,
        'PasswordCaption' => 'Password',
        'PasswordRequired' => 1,
        'LoginURL' => "http://some.$code.provider/login",
    ];
};
