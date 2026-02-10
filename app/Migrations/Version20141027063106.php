<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141027063106 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('AbMessageColor')->getColumn('BookerID')->setComment('ID букера');
        $schema->getTable('AbMessageColor')->getColumn('Color')->setComment('Цвет сообщения');
        $schema->getTable('AbMessageColor')->getColumn('Description')->setComment('Текст статуса');

        $schema->getTable('AbRequestStatus')->getColumn('BookerID')->setComment('ID букера');
        $schema->getTable('AbRequestStatus')->getColumn('Status')->setComment('Текст статуса');
        $schema->getTable('AbRequestStatus')->getColumn('SortIndex')->setComment('Индекс сортировки статусов в выпадухах фильтра в списке запросов букера и на странице подробностей запроса');
        $schema->getTable('AbRequestStatus')->getColumn('TextColor')->setComment('Цвет текста пункта в выпадухе (HEX)');
        $schema->getTable('AbRequestStatus')->getColumn('BgColor')->setComment('Цвет фона пункта в выпадухе (HEX)');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('AbMessageColor')->getColumn('BookerID')->setComment('');
        $schema->getTable('AbMessageColor')->getColumn('Color')->setComment('');
        $schema->getTable('AbMessageColor')->getColumn('Description')->setComment('');

        $schema->getTable('AbRequestStatus')->getColumn('BookerID')->setComment('');
        $schema->getTable('AbRequestStatus')->getColumn('Status')->setComment('');
        $schema->getTable('AbRequestStatus')->getColumn('SortIndex')->setComment('');
        $schema->getTable('AbRequestStatus')->getColumn('TextColor')->setComment('');
        $schema->getTable('AbRequestStatus')->getColumn('BgColor')->setComment('');
    }
}
