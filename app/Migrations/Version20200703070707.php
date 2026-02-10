<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200703070707 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE QsTransaction SET Earnings = '56.25', Approvals = '1' WHERE QsTransactionID = 777004;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 271009287;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272016256;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272020514;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272023754;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272013351;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272016131;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272000022;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272019780;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272001891;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '260', Approvals = 1 WHERE Click_ID = 272098792;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272118539;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272057399;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272187537;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 271114249;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '56.25', Approvals = 1 WHERE Click_ID = 270864829;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '70', Approvals = 1 WHERE Click_ID = 272812653;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '56.25', Approvals = 1 WHERE Click_ID = 272854087;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 272758157;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 272834963;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272065552;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272077033;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272186468;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272234831;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272237045;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272283856;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '113.75', Approvals = 1 WHERE Click_ID = 272270101;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 272960002;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '113.75', Approvals = 1 WHERE Click_ID = 273083808;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 273094670;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '113.75', Approvals = 1 WHERE Click_ID = 273215876;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273196915;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273249345;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273206919;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273250921;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273201408;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273205977;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273199228;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273199649;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273190157;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273143176;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 273192658;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273263934;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 273123852;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 273190397;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273457732;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273354910;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273359241;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273396455;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273378732;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273214269;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273205020;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273296694;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273539470;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272008544;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272015821;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272148121;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '162.50', Approvals = 1 WHERE Click_ID = 272242511;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 273636791;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273671093;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '41.25', Approvals = 1 WHERE Click_ID = 272127350;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '41.25', Approvals = 1 WHERE Click_ID = 273331646;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 273812384;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 273845199;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273529037;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 273894283;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 274035565;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273936257;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273914931;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273951088;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 273476037;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 274110890;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '56.25', Approvals = 1 WHERE Click_ID = 274033728;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '71', Approvals = 1 WHERE Click_ID = 274269867;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 274403262;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '56.25', Approvals = 1 WHERE Click_ID = 273425301;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 274288943;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274437829;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 274669123;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 274672362;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 274565242;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 274625763;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 274707341;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '41.25', Approvals = 1 WHERE Click_ID = 274772116;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '56.25', Approvals = 1 WHERE Click_ID = 274772177;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274785933;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '112.50', Approvals = 1 WHERE Click_ID = 274833858;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274835547;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274837586;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274838860;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274839070;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274839086;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274842157;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274845854;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274847296;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '206.25', Approvals = 1 WHERE Click_ID = 274853209;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274854319;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274857631;");
        $this->addSql("UPDATE QsTransaction SET Earnings = '33.75', Approvals = 1 WHERE Click_ID = 274861209;");
    }

    public function down(Schema $schema): void
    {
    }
}
