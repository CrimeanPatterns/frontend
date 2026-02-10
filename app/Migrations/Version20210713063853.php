<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210713063853 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE 
                LoungePage 
            SET SourceID = CONCAT_WS('.', 'fake', LoungePageID) 
            WHERE 
                SourceID IS NULL
                OR LoungePageID IN (
                    SELECT
                        LoungePageID
                    FROM
                        (
                            SELECT LoungePageID
                            FROM LoungePage
                            WHERE
                                (SourceCode, SourceID) IN (
                                    SELECT
                                        SourceCode, SourceID
                                    FROM LoungePage
                                    GROUP BY SourceCode, SourceID
                                    HAVING COUNT(*) > 1
                                )
                        ) t
                );
            
            ALTER TABLE Lounge
                CHANGE IsAvailable IsAvailable TINYINT DEFAULT 1 NOT NULL COMMENT 'Работает или временно не работает. 0 - Нет / 1 - Да',
                CHANGE PriorityPassAccess PriorityPassAccess TINYINT DEFAULT 0 NOT NULL COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                CHANGE AmexPlatinumAccess AmexPlatinumAccess TINYINT DEFAULT 0 NOT NULL COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да',
                CHANGE Status Status TINYINT DEFAULT 1 NOT NULL COMMENT 'Статус. 0 - неактивная запись / 1 - Актуальная запись',
                ADD KEY IsAvailableIndex (IsAvailable),
                ADD KEY StatusIndex (Status);
              
            ALTER TABLE LoungePage
                CHANGE SourceID SourceID VARCHAR(255) NOT NULL COMMENT 'Уникальный идентификатор зала у источника данных',
                CHANGE URL URL VARCHAR(2000) NOT NULL,
                CHANGE IsFullURL IsFullURL TINYINT DEFAULT 1 NOT NULL COMMENT '1 - URL полный (указывает напрямую на страницу зала), 0 - частичный (может указывать на все или группу залов)',
                CHANGE PageBody PageBody MEDIUMTEXT NOT NULL,
                CHANGE IsAvailable IsAvailable TINYINT DEFAULT 1 NOT NULL COMMENT 'Работает или временно не работает. 0 - Нет / 1 - Да',
                CHANGE PriorityPassAccess PriorityPassAccess TINYINT DEFAULT 0 NOT NULL COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                CHANGE AmexPlatinumAccess AmexPlatinumAccess TINYINT DEFAULT 0 NOT NULL COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да',
                CHANGE Status Status TINYINT DEFAULT 1 NOT NULL COMMENT 'Статус. 0 - неактивная запись / 1 - Актуальная запись',
                ADD UNIQUE KEY SourceIndex (SourceCode, SourceID),
                ADD KEY IsAvailableIndex (IsAvailable),
                ADD KEY StatusIndex (Status),
                ADD CONSTRAINT LoungeFK FOREIGN KEY (LoungeID) REFERENCES Lounge (LoungeID) ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                DROP KEY IsAvailableIndex,
                DROP KEY StatusIndex,
                CHANGE IsAvailable IsAvailable TINYINT DEFAULT 1 NULL COMMENT 'Работает или нет. 0 - Нет / 1 - Да',
                CHANGE PriorityPassAccess PriorityPassAccess TINYINT DEFAULT 0 NULL COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                CHANGE AmexPlatinumAccess AmexPlatinumAccess TINYINT DEFAULT 0 NULL COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да',
                CHANGE Status Status TINYINT DEFAULT 1 NULL COMMENT 'Статус. 0 - неактивная запись / 1 - Актуальная запись'
                
            ALTER TABLE LoungePage
                DROP FOREIGN KEY LoungeFK,
                DROP KEY SourceIndex,
                DROP KEY IsAvailableIndex,
                DROP KEY StatusIndex,
                CHANGE SourceID SourceID VARCHAR(255) NULL COMMENT 'Уникальный идентификатор зала у источника данных',
                CHANGE URL URL VARCHAR(2000) NULL,
                CHANGE IsFullURL IsFullURL TINYINT NULL COMMENT '1 - URL полный (указывает напрямую на страницу зала), 0 - частичный (может указывать на все или группу залов)',
                CHANGE PageBody PageBody MEDIUMTEXT NULL,
                CHANGE IsAvailable IsAvailable TINYINT NULL,
                CHANGE PriorityPassAccess PriorityPassAccess TINYINT DEFAULT 0 NULL COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                CHANGE AmexPlatinumAccess AmexPlatinumAccess TINYINT DEFAULT 0 NULL COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да',
                CHANGE Status Status TINYINT DEFAULT 1 NULL COMMENT 'Статус. 0 - неактивная запись / 1 - Актуальная запись'
        ");
    }
}
