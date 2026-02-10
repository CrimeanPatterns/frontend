<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 26.07.16
 * Time: 13:38.
 */

namespace AwardWallet\MainBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessApiDocumentationCommand extends Command
{
    public const TIMEOUT = 30;
    protected static $defaultName = 'aw:process-api-doc';

    protected function configure()
    {
        $this
            ->setDescription('ONLY FOR LOCAL USAGE! Creates static html files for API documentation');
        //            ->addOption('Id', 'a', InputOption::VALUE_REQUIRED, 'Id option')
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //        if (empty($input->getOption('accountId'))) {
        //            $output->writeln("Undefined accountId param\n");
        //            return;
        //        }

        $pythonServicePath = "/www/apidoc/";
        $output->writeln("Starting \"$pythonServicePath\" python service...");
        $pid = shell_exec("cd {$pythonServicePath}; python3 app.py > /dev/null & echo $!");
        $pid = intval($pid);
        sleep(5);
        $output->writeln("Python service PID: $pid");

        $url = "http://0.0.0.0:5000/docs/loyalty-api";
        $output->writeln("Connecting to \"$url\" python service...");
        $query = curl_init($url);

        if (!$query) {
            $output->writeln("Curl init error");

            exit;
        }

        curl_setopt($query, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($query, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($query, CURLOPT_HEADER, false);
        curl_setopt($query, CURLOPT_FAILONERROR, false);
        curl_setopt($query, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($query, CURLOPT_RETURNTRANSFER, true);
        //        if (isset($jsonData)) {
        //            curl_setopt($query, CURLOPT_CUSTOMREQUEST, "POST");
        //            curl_setopt($query, CURLOPT_POSTFIELDS, $jsonData);
        //            $headers[] = 'Content-Length: ' . strlen($jsonData);
        //        }
        $output->writeln('Sending curl;');
        $response = curl_exec($query);
        $code = curl_getinfo($query, CURLINFO_HTTP_CODE);
        $error = curl_error($query);
        $output->writeln('Result curl: ' . $code);
        curl_close($query);

        $output->writeln("Killing python service PID: $pid");
        exec("kill -KILL {$pid}");

        if ($code !== 200) {
            $output->writeln("Something wrong with python service");

            exit;
        }

        $path = __DIR__ . "/../Resources/views/ApiDocumentation/loyalty.html.twig";
        $output->writeln("Saving html to path: $path");
        file_put_contents($path, $response);
        $output->writeln("Generate successfully");

        return 0;
    }
}
