<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExtensionCreateCommand extends Command
{
    protected static $defaultName = 'ext:create';

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function configure()
    {
        $this->setDescription("create LPs extension template");
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

        if (is_dir($path) && file_exists($path . DIRECTORY_SEPARATOR . 'extension.js')) {
            throw new \Exception("This extension already exists!");
        } else {
            if (!is_dir($path)) {
                mkdir($path);
                $output->writeln("<comment>LP:</comment><info>directory created successfully!</info>");
            } else {
                $output->writeln("<comment>LP:</comment><info>directory already exist...</info>");
            }

            $provider = $this->entityManager->getRepository(Provider::class)->findOneByCode($code);
            $template = file_get_contents("data/templates/lpTemplates/" . $input->getOption('template') . ".js");

            if (isset($provider)) {
                $output->writeln("<comment>LP:</comment><info>provider found in database, ID:<comment>{$provider->getProviderid()}</comment>. Preset...</info>");
                $loginURL = $provider->getLoginurl();

                $template = str_replace("loginURL", $loginURL, $template);
                $template = str_replace("siteURL", $provider->getSite(), $template);

                $urlParts = parse_url($loginURL);
            } else {
                $output->writeln("<comment>LP:</comment><info>provider not found in database. Preset default...</info>");
            }

            $template = str_replace("schemeURL", $urlParts['scheme'] ?? 'https', $template);
            $template = str_replace("hostURL", $urlParts['host'] ?? 'www.site.com', $template);

            if (file_put_contents($path . DIRECTORY_SEPARATOR . 'extension.js', $template)) {
                $output->writeln("<comment>LP:</comment><info>extension template added successfully!</info>");
            }
        }

        return 0;
    }
}
