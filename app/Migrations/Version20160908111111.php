<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160908111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $usr = $schema->getTable('Usr');
        $usr->addColumn('WpDisableAll', 'boolean', ['default' => false, 'comment' => 'Отключить все webpush-уведомления']);
        $usr->addColumn('WpNewPlans', 'boolean', ['default' => true, 'comment' => 'Разрешить webpush-уведомления для новых травел-планов']);
        $usr->addColumn('WpPlanChanges', 'boolean', ['default' => true, 'comment' => 'Разрешить webpush-уведомления при изменении резерваций']);
        $usr->addColumn('WpProductUpdates', 'boolean', ['default' => true, 'comment' => 'Разрешить webpush-уведомления при обновлении продуктов']);
        $usr->addColumn('WpOffers', 'boolean', ['default' => true, 'comment' => 'Разрешить webpush-уведомления для оферов']);
        $usr->addColumn('WpExpire', 'boolean', ['default' => true, 'comment' => 'Разрешить webpush-уведомления при протухании балансов']);
        $usr->addColumn('WpBookingMessages', 'boolean', ['default' => true, 'comment' => 'Разрешить webpush-уведомления при новых сообщениях в букинг-запросах']);
        $usr->addColumn('WpNewBlogPosts', 'boolean', ['default' => true, 'comment' => 'Разрешить webpush-уведомления при новых записях в блоге']);
    }

    public function down(Schema $schema): void
    {
        $usr = $schema->getTable('Usr');
        $usr->dropColumn('WpDisableAll');
        $usr->dropColumn('WpNewPlans');
        $usr->dropColumn('WpPlanChanges');
        $usr->dropColumn('WpProductUpdates');
        $usr->dropColumn('WpOffers');
        $usr->dropColumn('WpExpire');
        $usr->dropColumn('WpBookingMessages');
        $usr->dropColumn('WpNewBlogPosts');
    }
}
