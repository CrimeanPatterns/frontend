<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20201202121212 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema): void
    {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get('monolog.logger.payment');

        $this->addSql("DELETE FROM CartItem WHERE CartItemID = 1632143 AND CartID = 1043003 AND ID = 2");

        $logger->info('Removed CartItem Early Supporter discount; recalculated from $1 to $21', [
            'userId' => 48818,
            'cartId' => 1043003,
        ]);
    }

    public function down(Schema $schema): void
    {
    }
}
