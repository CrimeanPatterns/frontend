<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Ssm;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Aws\Ssm\SsmClient;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class PutToSsmCommand extends Command
{
    public static $defaultName = 'aw:put-to-ssm';
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
            ->setDescription('put param from yml to ssm and replace yml value to "%env(ssm:xxx)"')
            ->addArgument('yaml-file', InputArgument::REQUIRED, 'yaml file to modify')
            ->addArgument('ssm-path', InputArgument::REQUIRED, 'ssm path to save parameter, like /frontend/prod')
            ->addArgument('param-name', InputArgument::REQUIRED, 'parameter name to replace. you could use / to specify levels,like "parameters/prod/database_password"')
            ->addOption('ssm-param-name', null, InputOption::VALUE_REQUIRED, 'ssm param name')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $output->writeln("replacing " . $input->getArgument('param-name') . ' in ' . $input->getArgument('yaml-file'));
        $config = Yaml::parseFile($input->getArgument('yaml-file'));
        $this->updateParam($config, $input->getArgument('param-name'), function (string $name, string $value) use ($input, $output): string {
            $output->writeln("replacing $name, value: $value");

            if (!empty($input->getOption('ssm-param-name'))) {
                $name = $input->getOption('ssm-param-name');
                $output->writeln("using name " . $name);
            }

            if (substr($name, 0, 1) !== '/') {
                $name = $input->getArgument('ssm-path') . "/" . $name;
            }
            $this->ssmClient->putParameter([
                "Name" => $name,
                "Type" => "SecureString",
                "Value" => $value,
            ]);

            if (strpos($name, $input->getArgument('ssm-path')) === 0) {
                $name = substr($name, strlen($input->getArgument('ssm-path')) + 1);
            }

            return "%env(ssm:$name)%";
        });
        file_put_contents($input->getArgument('yaml-file'), Yaml::dump($config, 8));
        $output->writeln("done");
    }

    private function updateParam(&$values, $pathStr, callable $updater): void
    {
        $path = explode("/", $pathStr);
        $oldValue = null;

        while (!empty($path)) {
            $slug = array_shift($path);

            if (!isset($values[$slug])) {
                throw new \Exception("missing key $slug");
            }

            if (empty($path)) {
                $values[$slug] = call_user_func($updater, $slug, $values[$slug]);
            } else {
                $values = &$values[$slug];
            }
        }
    }
}
