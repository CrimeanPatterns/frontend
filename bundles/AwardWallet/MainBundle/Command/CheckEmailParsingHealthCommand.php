<?php

namespace AwardWallet\MainBundle\Command;

use Aws\CloudWatch\CloudWatchClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckEmailParsingHealthCommand extends Command
{
    protected static $defaultName = "aw:email:check-health";
    private array $emailApi;
    private LoggerInterface $logger;

    private CloudWatchClient $cloudWatchClient;

    public function __construct(array $emailApi, LoggerInterface $logger, CloudWatchClient $cloudWatchClient)
    {
        parent::__construct();
        $this->emailApi = $emailApi;
        $this->logger = $logger;
        $this->cloudWatchClient = $cloudWatchClient;
    }

    public function configure()
    {
        $this->setDescription("Check email parsing service");
        $this->setDefinition([
            new InputOption('cluster', null, InputOption::VALUE_REQUIRED, 'Cluster name'),
        ]);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (['cluster'] as $p) {
            if (!$input->getOption($p)) {
                throw new \Exception(sprintf('argument %s is required', $p));
            }
        }

        $url = sprintf(
            '%s/json/v2/parseEmail',
            $this->emailApi[$input->getOption('cluster')]['url']
        );

        $request = [
            'email' => $this->getEmail(),
            'returnEmail' => 'none',
            'userData' => bin2hex(random_bytes(5)),
        ];

        $auth = $this->emailApi[$input->getOption('cluster')]['http_auth'];
        $output->writeln('sending parse email request to ' . $input->getOption('cluster'));
        $result = $this->call($url, $request, $auth);

        if ($result['status'] !== 'queued') {
            $this->logger->warning('Unexpected status: ' . $result['status']);

            return 0;
        }

        if (!($id = $result['requestIds'][0])) {
            $this->logger->warning('Missing requestId in response');

            return 0;
        }

        $processed = false;
        $start = time();
        $url = sprintf(
            '%s/json/v2/getResults/%s',
            $this->emailApi[$input->getOption('cluster')]['url'],
            $id
        );

        $output->writeln('checking result from ' . $input->getOption('cluster'));

        while ((time() - $start) < 40 && !$processed) {
            sleep(1);
            $result = $this->call($url, null, $auth);

            if (!empty($result['status']) && $result['status'] !== 'queued') {
                $processed = true;
            }
        }

        $duration = (time() - $start);
        $output->writeln('took ' . $duration . ' seconds');

        if (!$processed) {
            $this->logger->warning('timed out while waiting for result');
        }

        $this->cloudWatchClient->putMetricData([
            'Namespace' => 'AW/Email',
            'MetricData' => [
                [
                    'MetricName' => "ParsingTime",
                    'Timestamp' => time(),
                    'Value' => (time() - $start),
                    'Unit' => 'Seconds',
                ],
            ],
        ]);

        $output->writeln('Checked');

        return 0;
    }

    protected function call($url, $request, $auth): array
    {
        $query = curl_init($url);

        try {
            curl_setopt($query, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($query, CURLOPT_TIMEOUT, 30);
            curl_setopt($query, CURLOPT_HEADER, false);
            curl_setopt($query, CURLOPT_FAILONERROR, false);
            curl_setopt($query, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
            curl_setopt($query, CURLOPT_RETURNTRANSFER, true);

            if ($request) {
                curl_setopt($query, CURLOPT_POST, true);
                curl_setopt($query, CURLOPT_POSTFIELDS, json_encode($request));
            }
            curl_setopt($query, CURLOPT_HTTPHEADER, [sprintf('X-Authentication: %s', $auth)]);
            $result = curl_exec($query);

            if (!is_string($result) || !($arr = @json_decode($result, true))) {
                $this->logger->warning('Email API error: ' . curl_errno($query) . " " . curl_error($query) . " " . substr($result ?? '', 0, 300));

                return ["status" => "curl error"];
            }
        } finally {
            curl_close($query);
        }

        return $arr;
    }

    protected function getEmail()
    {
        return sprintf('From: from@health.check
To: to@health.check
Subject: health check
Date: %s
Content-Type: text/plain

test body', date(DATE_RFC822));
    }
}
