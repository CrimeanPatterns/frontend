<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParserCreateCommand extends Command
{
    protected static $defaultName = 'lp:create';

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function configure()
    {
        $this->setDescription("create LPs parser template");
        $this->addArgument("code", InputArgument::REQUIRED, "program code");
        $this->addOption('template', null, InputOption::VALUE_REQUIRED, 'template', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var $provider \AwardWallet\MainBundle\Entity\Provider
         */
        $code = strtolower($input->getArgument("code"));
        $path = "engine/" . $code;

        if (is_dir($path) && file_exists($path . DIRECTORY_SEPARATOR . 'functions.php')) {
            throw new \Exception("This program already exists!");
        } else {
            if (!is_dir($path)) {
                mkdir($path);
                $output->writeln("<comment>LP:</comment><info>directory created successfully!</info>");
            } else {
                $output->writeln("<comment>LP:</comment><info>directory already exist...</info>");
            }

            $provider = $this->entityManager->getRepository(Provider::class)->findOneByCode($code);
            $template = file_get_contents("data/templates/lpTemplates/" . $input->getOption('template') . ".php");

            if (isset($provider)) {
                $output->writeln("<comment>LP:</comment><info>provider found in database, ID:<comment>{$provider->getProviderid()}</comment>. Preset...</info>");
                $template = str_replace("loginURL", $provider->getLoginurl(), $template);
                $template = str_replace("siteURL", $provider->getSite(), $template);
            } else {
                $output->writeln("<comment>LP:</comment><info>provider not found in database. Preset default...</info>");
            }

            $template = str_replace("ProviderName", ucfirst($code), $template);
            //            $template = str_replace("providerCode", $code, $template);

            if (file_put_contents($path . DIRECTORY_SEPARATOR . 'functions.php', $template)) {
                $output->writeln("<comment>LPs:</comment><info>parser template added successfully!</info>");
            }
        }

        return 0;
    }
}
