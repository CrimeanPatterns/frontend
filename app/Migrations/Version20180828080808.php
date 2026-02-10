<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180828080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `BlogComment` (
              `BlogCommentID` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `PostTitle` varchar(255) NOT NULL,
              `PostLink` varchar(255) NOT NULL,
              `PostUpdate` datetime NOT NULL,
              `CommentCount` mediumint(8) NOT NULL DEFAULT '0',
              `CommentAuthor` varchar(128) NOT NULL,
              `CommentDate` datetime NOT NULL,
              `CommentEmail` varchar(128) NOT NULL,
              `CommentLink` varchar(255) NOT NULL,
              `CommentContent` text NOT NULL,
              `Subscribers` text NOT NULL,
              PRIMARY KEY (`BlogCommentID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `BlogComment`");
    }
}
