<?php

namespace AwardWallet\MainBundle\Service\RA\Hotel;

class ApiResolver
{
    private Api $api;

    private TestApi $debugApi;

    private Config $config;

    public function __construct(Api $api, TestApi $testApi, Config $config)
    {
        $this->api = $api;
        $this->debugApi = $testApi;
        $this->config = $config;
    }

    public function getApi(): Api
    {
        return $this->config->useDebugApi() ? $this->debugApi : $this->api;
    }
}
