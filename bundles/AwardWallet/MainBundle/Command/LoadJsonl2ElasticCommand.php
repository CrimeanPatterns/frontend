<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadJsonl2ElasticCommand extends Command
{
    public static $defaultName = 'aw:load-jsonl-2-elastic';
    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;

    public function __construct(\HttpDriverInterface $httpDriver)
    {
        parent::__construct();
        $this->httpDriver = $httpDriver;
    }

    public function configure()
    {
        $this
            ->addOption('input-file', null, InputOption::VALUE_REQUIRED)
            ->addOption('es-address', null, InputOption::VALUE_REQUIRED)
            ->addOption('index', null, InputOption::VALUE_REQUIRED)
            ->addOption('date-field', null, InputOption::VALUE_REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("loading jsonl from " . $input->getOption('input-file'));

        $inputFile = fopen($input->getOption("input-file"), "rb");

        if ($inputFile === false) {
            throw new \Exception("failed to open input file: " . $input->getOption("input-file"));
        }

        $dateField = $input->getOption('date-field');

        if (!empty($dateField)) {
            $this->addMapping($input->getOption('es-address'), $input->getOption('index'), $dateField, 'date');
        }

        $progress = new ProgressLogger(new Logger("main", [new StreamHandler('php://stdout')]), 100, 30);
        $count = 0;
        $packet = [];

        try {
            while (!empty($line = fgets($inputFile))) {
                $progress->showProgress("uploading to elasticsearch", $count);
                $count++;

                $line = trim($line);

                if (empty($line)) {
                    continue;
                }

                if (!empty($dateField)) {
                    $json = json_decode($line, true);

                    if (isset($json[$dateField])) {
                        $json[$dateField] = strtotime($json[$dateField]) * 1000;
                        $line = json_encode($json);
                    }
                }

                $packet[] = $line;

                if (count($packet) >= 1000) {
                    $this->sendPacket($packet, $input->getOption('es-address'), $input->getOption('index'));
                    $packet = [];
                }
            }

            if (!empty($packet)) {
                $this->sendPacket($packet, $input->getOption('es-address'), $input->getOption('index'));
            }
        } finally {
            fclose($inputFile);
        }

        $output->writeln("done");

        return 0;
    }

    private function addMapping(string $esAddress, string $index, string $field, string $type)
    {
        $mappings = [
            'mappings' => [
                'properties' => [
                    $field => [
                        'type' => $type,
                    ],
                ],
            ],
        ];
        $request = new \HttpDriverRequest("http://{$esAddress}/{$index}", "PUT", json_encode($mappings), ["Content-Type" => "application/json"]);
        $response = $this->httpDriver->request($request);

        if ($response->httpCode !== 200) {
            throw new \Exception("es mapping request failed: {$response->httpCode} {$response->body}");
        }
    }

    private function sendPacket(array $packet, string $esAddress, string $index)
    {
        $postBody = implode("\n", array_map(function (string $json) use ($index) {
            return '{"index": {"_index": "' . $index . '"}}' . "\n" . $json;
        }, $packet)) . "\n";
        $request = new \HttpDriverRequest("http://{$esAddress}/_bulk", "POST", $postBody, ["Content-Type" => "application/json"]);
        $response = $this->httpDriver->request($request);

        if ($response->httpCode !== 200) {
            throw new \Exception("es request failed: {$response->httpCode} {$response->body}");
        }
        $responseJson = json_decode($response->body, true);

        if (!empty($responseJson["errors"])) {
            throw new \Exception("es request failed: {$response->httpCode} {$response->body}");
        }
    }
}
