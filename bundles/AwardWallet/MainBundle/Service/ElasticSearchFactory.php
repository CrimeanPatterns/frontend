<?php

namespace AwardWallet\MainBundle\Service;

use Elasticsearch\ClientBuilder;

class ElasticSearchFactory
{
    private string $elasticSearchHost;

    public function __construct(string $elasticSearchHost)
    {
        $this->elasticSearchHost = $elasticSearchHost;
    }

    public function create()
    {
        return ClientBuilder::create()
            ->setHosts([$this->elasticSearchHost])
            ->build()
        ;
    }
}
