<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140521114451 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->em = $container->get('doctrine.orm.entity_manager');
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('AbBookerInfo');
        $table->addColumn('ServeEconomyClass', 'boolean', ['default' => false, 'notnull' => true, 'comment' => 'Обслуживают запросы на эконом класс']);
        $table->addColumn('ServeInternational', 'boolean', ['default' => true, 'notnull' => true, 'comment' => 'Обслуживают запросы на международные авиарейсы']);
        $table->addColumn('ServeDomestic', 'boolean', ['default' => true, 'notnull' => true, 'comment' => 'Обслуживают запросы на внутренние авиарейсы']);
        $table->addColumn('ServeReservationAir', 'boolean', ['default' => true, 'notnull' => true, 'comment' => 'Обслуживают запросы на перелёты']);
        $table->addColumn('ServeReservationHotel', 'boolean', ['default' => false, 'notnull' => true, 'comment' => 'Обслуживают запросы на бронирование отелей']);
        $table->addColumn('ServeReservationCar', 'boolean', ['default' => false, 'notnull' => true, 'comment' => 'Обслуживают запросы на аренду авто']);
        $table->addColumn('ServeReservationCruises', 'boolean', ['default' => false, 'notnull' => true, 'comment' => 'Обслуживают запросы на круизы']);
        $table->addColumn('ServePaymentCash', 'boolean', ['default' => false, 'notnull' => true, 'comment' => 'Принимают оплату в наличных']);
        $table->addColumn('ServePaymentMiles', 'boolean', ['default' => true, 'notnull' => true, 'comment' => 'Принимают оплату в милях']);
        $table->addColumn('RequirePriorSearches', 'boolean', ['default' => true, 'notnull' => true, 'comment' => 'Требуется ли указание поля Prior Searches']);
        $table->addColumn('RequireCustomer', 'boolean', ['default' => true, 'notnull' => true, 'comment' => 'Требуется ли взаимодействие с заказчиком через интерфейсы и почту']);
        $this->addSql('ALTER TABLE AbBookerInfo ADD CurrencyID int unsigned not null');
        $this->addSql('update AbBookerInfo set CurrencyID = 3');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('AbBookerInfo');
        $table->dropColumn('ServeEconomyClass');
        $table->dropColumn('ServeInternational');
        $table->dropColumn('ServeDomestic');
        $table->dropColumn('ServeReservationAir');
        $table->dropColumn('ServeReservationHotel');
        $table->dropColumn('ServeReservationCar');
        $table->dropColumn('ServeReservationCruises');
        $table->dropColumn('ServePaymentCash');
        $table->dropColumn('ServePaymentMiles');
        $table->dropColumn('RequirePriorSearches');
        $table->dropColumn('RequireCustomer');
        $table->dropColumn('CurrencyID');
    }
}
