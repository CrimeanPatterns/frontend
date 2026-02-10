<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Command\GenerateCardImageUUIDCommand;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171026123758 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $this->executeCommand();
    }

    public function down(Schema $schema): void
    {
    }

    protected function executeCommand()
    {
        $app = new Application($this->container->get('kernel'));
        $app->add($command = new GenerateCardImageUUIDCommand('card-image:generate-uuid'));
        $command->setContainer($this->container);

        $writer = function (string $message) {
            $this->write($message);
        };

        $command->run(
            new ArrayInput([]),
            new class($writer) extends Output {
                /**
                 * @var callable
                 */
                protected $writer;

                public function __construct(callable $writer, $verbosity = self::VERBOSITY_NORMAL, $decorated = false, $formatter = null)
                {
                    parent::__construct($verbosity, $decorated, $formatter);

                    $this->writer = $writer;
                }

                protected function doWrite($message, $newline)
                {
                    ($this->writer)($message);
                }
            }
        );
    }
}
