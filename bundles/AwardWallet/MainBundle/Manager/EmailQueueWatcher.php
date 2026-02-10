<?php

namespace AwardWallet\MainBundle\Manager;

use Aws\CloudWatch\CloudWatchClient;

class EmailQueueWatcher
{
    public const CACHE_KEY = 'manager_eq_watcher';
    public const CACHE_EXPIRATION = 15 * 60;

    protected $cloudWatchOptions;

    protected $client;

    protected $memcached;

    protected $env;

    public function __construct($region, \Memcached $memcached, $env)
    {
        $this->cloudWatchOptions = [
            'region' => $region,
            'version' => "2010-08-01",
        ];
        $this->memcached = $memcached;
        $this->env = $env;
    }

    public function getStat12hQuaterly($allowCache = true)
    {
        if ($allowCache) {
            $data = $this->memcached->get(self::CACHE_KEY);
        }

        if (empty($data)) {
            $data = $this->calculate();
            $this->memcached->set(self::CACHE_KEY, $data, self::CACHE_EXPIRATION);
        }

        return $data;
    }

    protected function getClient()
    {
        if (!isset($this->client)) {
            $this->client = new CloudWatchClient($this->cloudWatchOptions);
        }

        return $this->client;
    }

    protected function calculate()
    {
        $result = [];

        foreach (['parse_email_prio' => 'Parse', 'send_callback' => 'Callbacks'] as $name => $title) {
            $data = $this->getMetricData($name);
            $data = [
                'Datapoints' => $data['Datapoints'] ?? [],
            ];
            usort($data['Datapoints'], function ($a, $b) {return strtotime($a['Timestamp']) - strtotime($b['Timestamp']); });

            foreach ($data['Datapoints'] as &$datapoint) {
                $datapoint['Timestamp'] = $datapoint['Timestamp']->getTimestamp();
            }
            unset($datapoint);

            $data['Title'] = $title;

            if (count($data['Datapoints']) > 0) {
                end($data['Datapoints']);
                $data['Latest'] = current($data['Datapoints']);
            }
            $result[$title] = $data;
        }

        return $result;
    }

    protected function getMetricData($metric)
    {
        if ($this->env === 'dev') {
            return [];
        }

        return $this->getClient()->getMetricStatistics([
            'Dimensions' => [
            ],
            'EndTime' => time(),
            'MetricName' => $metric,
            'Namespace' => 'AW/Rabbit',
            'Period' => 15 * 60,
            'StartTime' => time() - 12 * 60 * 60,
            'Statistics' => ['Average', 'Maximum'],
        ]);
    }
}
