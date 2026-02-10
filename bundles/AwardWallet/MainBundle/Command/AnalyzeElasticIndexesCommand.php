<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Strings\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AnalyzeElasticIndexesCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $style;
    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;
    /**
     * @var string
     */
    private $elasticSearchHost;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var bool
     */
    private $noCache;

    public function __construct(\HttpDriverInterface $httpDriver, string $elasticSearchHost, \Memcached $memcached)
    {
        parent::__construct();
        $this->httpDriver = $httpDriver;
        $this->elasticSearchHost = $elasticSearchHost;
        $this->memcached = $memcached;
    }

    public function configure()
    {
        $this->addOption('no-cache', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->noCache = $input->getOption('no-cache');
        $this->style = new SymfonyStyle($input, $output);
        $indices = $this->request("/_stats")["indices"];
        $this->style->writeln("loaded " . count($indices) . " indices");
        ksort($indices);
        $indices = $this->exploreIndices($indices);
        $this->style->title("Indices statistics");
        $this->showTable($this->formatIndices($indices));
        $lastIndexes = array_slice($indices, -2);

        foreach ($lastIndexes as $indexName => $index) {
            $this->showindexFields($indexName, $index);
        }
        $this->showTypeConflicts($indices);

        return 0;
    }

    private function showTypeConflicts(array $indices)
    {
        $types = [];

        foreach ($indices as $indexName => $index) {
            foreach ($index['fields'] as $field => $type) {
                if (!isset($types[$field])) {
                    $types[$field] = [
                        $indexName => $type,
                    ];
                } else {
                    if (!in_array($type, $types[$field])) {
                        $types[$field][$indexName] = $type;
                    }
                }
            }
        }

        foreach ($types as $field => $fieldTypes) {
            if (count($fieldTypes) < 2) {
                continue;
            }
            $this->style->title("$field - Type conflict");

            foreach ($fieldTypes as $index => $type) {
                $this->style->writeln("$index: $type");
            }

            foreach ($fieldTypes as $index => $type) {
                $matches = $this->search($index, "_exists_: $field", 1);

                if (!empty($matches)) {
                    $this->style->writeln("sample from $index: $type");
                    $this->style->writeln(json_encode($matches[0], JSON_PRETTY_PRINT));
                }
            }
        }
    }

    private function showIndexFields(string $indexName, array $index)
    {
        $this->style->title('fields of ' . $indexName);
        $rows = [];

        foreach ($index['fields'] as $key => $value) {
            $rows[] = [$key, $value];
        }
        $this->style->table(['field', 'type'], $rows);
    }

    private function exploreIndices(array $indices): array
    {
        $result = [];

        foreach ($indices as $indexName => $index) {
            if (strpos($indexName, 'logstash') === false) {
                continue;
            }

            $result[$indexName] = array_merge($index, [
                'fields' => $this->flatMap($this->request("/{$indexName}/_mapping")[$indexName]['mappings']['properties']),
            ]);
        }

        return $result;
    }

    private function showTable(array $rows)
    {
        $headers = array_keys($rows[0]);
        $this->style->table($headers, $rows);
    }

    private function formatIndices(array $indices)
    {
        $rows = [];

        foreach ($indices as $indexName => $index) {
            $rows[] = [
                'index' => $indexName,
                'docs' => round($index['total']['docs']['count'] / 1000000, 1) . ' M',
                'size' => round($index['total']['store']['size_in_bytes'] / 1024 / 1024 / 1024, 1) . " Gb",
                'memory' => round($index['total']['segments']['memory_in_bytes'] / 1024 / 1024) . " Mb",
                'fields' => count($index['fields']),
            ];
        }

        return $rows;
    }

    private function flatMap(array $fields): array
    {
        $result = [];

        foreach ($fields as $fieldName => $field) {
            if (isset($field['type'])) {
                $result[$fieldName] = $field['type'];
            }

            foreach (['properties', 'fields'] as $branch) {
                if (isset($field[$branch])) {
                    $subFields = $this->flatMap($field[$branch]);

                    foreach ($subFields as $subFieldName => $subFieldType) {
                        $result[$fieldName . '.' . $subFieldName] = $subFieldType;
                    }
                }
            }
        }

        return $result;
    }

    private function search(string $index, string $query, int $limit, ?int $startDate = null, ?int $endDate = null): array
    {
        $postData = '
        {
          "size": ' . $limit . ',
          "sort": [
            {
              "@timestamp": {
                "order": "asc",
                "unmapped_type": "boolean"
              }
            }
          ],
          "query": {
            "bool": {
              "must": [
                {
                  "query_string": {
                    "query": "' . addslashes($query) . '",
                    "analyze_wildcard": true
                  }
                }
                ' . (!empty($startDate) || !empty($endDate) ? ',
                {
                  "range": {
                    "@timestamp": {
                      "gte": ' . ($startDate * 1000) . ',
                      "lte": ' . ($endDate * 1000) . ',
                      "format": "epoch_millis"
                    }
                  }
                }
                ' : '') . '
              ]
            }
          }
        }';

        $result = $this->request("/$index/_search", 'POST', $postData);

        if (!isset($result['hits']['hits'])) {
            throw new \Exception("No hits in ES response: " . json_encode($result));
        }

        return array_map(function (array $hit) { return $hit['_source']; }, $result['hits']['hits']);
    }

    private function request($url, string $method = 'GET', ?string $postData = null)
    {
        $cacheKey = 'es2_' . sha1($url);

        if (!empty($postData)) {
            $cacheKey .= '_' . sha1($postData);
        }

        if ($this->noCache) {
            $response = false;
        } else {
            $response = $this->memcached->get($cacheKey);
        }

        if ($response !== false) {
            return $response;
        }

        $this->style->writeln("$method $url");
        $response = $this->httpDriver->request(new \HttpDriverRequest("http://{$this->elasticSearchHost}:9200{$url}", $method, $postData, [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]));

        if ($response->httpCode < 200 || $response->httpCode >= 300) {
            throw new \Exception("Elastic request failed: {$response->httpCode} " . Strings::cutInMiddle($response->body, 1024));
        }

        $result = json_decode($response->body, true);

        $this->memcached->set($cacheKey, $result, 86400);

        return $result;
    }
}
