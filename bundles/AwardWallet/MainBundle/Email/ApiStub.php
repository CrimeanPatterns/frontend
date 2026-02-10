<?php

namespace AwardWallet\MainBundle\Email;

use Symfony\Component\HttpFoundation\Request;

class ApiStub extends Api
{
    public function __construct()
    {
    }

    public function call($name, $isPost, $data = [], $params = [], $dataEncoded = false, $region = null, $headers = [])
    {
        return [];
    }

    public function proxyCall($url, Request $request, &$responseHeaders, $requestHeaders = [], $region = null)
    {
        return [];
    }
}
