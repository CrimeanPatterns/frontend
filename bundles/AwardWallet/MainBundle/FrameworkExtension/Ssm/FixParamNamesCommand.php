<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Ssm;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Aws\Ssm\SsmClient;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class FixParamNamesCommand extends Command
{
    public static $defaultName = 'aw:fix-ssm-param-names';
    /**
     * @var SsmClient
     */
    private $ssmClient;

    public function __construct(SsmClient $ssmClient)
    {
        parent::__construct();

        $this->ssmClient = $ssmClient;
    }

    public function configure()
    {
        $this
            ->setDescription('fix ssm param names - removes dots "%env(ssm:google.api.key)%" -> "%env(ssm:google_api_key)%"')
            ->addArgument('yaml-file', InputArgument::REQUIRED, 'yaml file to modify')
            ->addArgument('ssm-path', InputArgument::REQUIRED, 'ssm path to save parameter, like /frontend/prod')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $output->writeln("fixing ssm param names in " . $input->getArgument('yaml-file'));
        $config = Yaml::parseFile($input->getArgument('yaml-file'));
        array_walk_recursive($config, function (&$value) use ($input, $output) {
            if (preg_match('#^%env\(ssm:([^\)]+\.[^\)]+)\)%$#ims', $value, $matches)) {
                $oldParam = $matches[1];

                if (substr($oldParam, 0, 1) !== '/') {
                    $oldParam = $input->getArgument('ssm-path') . '/' . $oldParam;
                }
                $newParam = str_replace('.', '_', $oldParam);

                $output->writeln("correcting $value from $oldParam to $newParam");

                $oldValue = $this->ssmClient->getParameter(["Name" => $oldParam, "WithDecryption" => true])->search('Parameter.Value');

                $this->ssmClient->putParameter([
                    "Name" => $newParam,
                    "Type" => "SecureString",
                    "Value" => $oldValue,
                ]);
                $this->ssmClient->deleteParameter(["Name" => $oldParam]);
                $value = str_replace(".", "_", $value);
            }
        });
        file_put_contents($input->getArgument('yaml-file'), Yaml::dump($config, 8));
        $output->writeln("done");
    }
}
