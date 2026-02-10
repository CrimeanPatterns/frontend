<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170301093349 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
            
              /* Emails */
              MODIFY COLUMN EmailExpiration TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Отправлять ли письма о протухании балансов' AFTER InviteCouponsCorrection,
              MODIFY COLUMN EmailRewards TINYINT(1) UNSIGNED NOT NULL DEFAULT '3' COMMENT 'Отправлять ли письма об изменении балансов и за какой период (день, неделя, месяц)' AFTER EmailExpiration,
              MODIFY COLUMN EmailNewPlans TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Отправлять ли письма о добавлении новой резервации' AFTER EmailRewards,
              MODIFY COLUMN EmailPlansChanges TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Отправлять ли письма об изменении резерваций' AFTER EmailNewPlans,
              MODIFY COLUMN CheckinReminder TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Оповещение о скором перелете (за 24 часа)' AFTER EmailPlansChanges,
              ADD EmailBookingMessages TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Отправлять ли письма о новых сообщениях в букинге'  AFTER CheckinReminder,
              MODIFY COLUMN EmailBooking TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Отправлять ли букеру письма, связанные с букингом' AFTER EmailBookingMessages,
              MODIFY COLUMN EmailProductUpdates TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Отправлять ли письма, связанные с обновлениями' AFTER EmailBooking,
              MODIFY COLUMN EmailOffers TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Отправлять ли письма офферы' AFTER EmailProductUpdates,
              ADD EmailNewBlogPosts TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Отправлять ли письма о новых сообщениях в блоге'  AFTER EmailOffers,
              MODIFY COLUMN EmailInviteeReg TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Уведомление о регистрации приглашенного пользователя' AFTER EmailNewBlogPosts,
              ADD EmailConnectedAlert TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Отправлять ли письма, адресованные приконекченным юзерам' AFTER EmailInviteeReg,
              MODIFY COLUMN EmailConnected TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Отправлять ли письма, адресованные членам семьи' AFTER EmailConnectedAlert,
              
              /* WP */
              ALTER COLUMN WpNewBlogPosts SET DEFAULT '0',
              ADD WpInviteeReg TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Разрешить webpush-уведомления о регистрации приглашенного пользователя'  AFTER WpNewBlogPosts,
              ADD WpConnectedAlert TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Разрешить webpush-уведомления, предназначенные приконекченным юзерам'  AFTER WpInviteeReg,
              ADD WpNotConnectedAlert TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить webpush-уведомления, предназначенные членам семьи'  AFTER WpConnectedAlert,
              
              /* MP */
              MODIFY COLUMN MpDisableAll TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Отключить все mobile push-уведомления' AFTER wpCheckins,
              ADD MpExpire TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления при протухании балансов'  AFTER MpDisableAll,
              ADD MpRewardsActivity TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Посылать mobile push-уведомления об изменениях баланса программ'  AFTER MpExpire,
              ADD MpNewPlans TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления для новых травел-планов'  AFTER MpRewardsActivity,
              ADD MpPlanChanges TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления при изменении резерваций'  AFTER MpNewPlans,
              ADD MpCheckins TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления о чекинах на перелет'  AFTER MpPlanChanges,
              CHANGE MpBooking MpBookingMessages TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления при новых сообщениях в букинг-запросах'  AFTER MpCheckins,
              ADD MpProductUpdates TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления при обновлении продуктов'  AFTER MpBookingMessages,
              ADD MpOffers TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления для оферов'  AFTER MpProductUpdates,
              ADD MpNewBlogPosts TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Разрешить mobile push-уведомления при новых записях в блоге'  AFTER MpOffers,
              ADD MpInviteeReg TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Разрешить mobile push-уведомления о регистрации приглашенного пользователя'  AFTER MpNewBlogPosts,
              ADD MpConnectedAlert TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Разрешить mobile push-уведомления, предназначенные приконекченным юзерам'  AFTER MpInviteeReg,
              ADD MpNotConnectedAlert TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления, предназначенные членам семьи'  AFTER MpConnectedAlert
        ");
        $this->addSql("UPDATE Usr SET MpExpire = MpLoyaltyProgram, MpRewardsActivity = MpLoyaltyProgram");
        $this->addSql("UPDATE Usr SET MpNewPlans = MpPlans, MpPlanChanges = MpPlans, MpCheckins = MpPlans");
        $this->addSql("
            ALTER TABLE Usr 
              DROP COLUMN MpLoyaltyProgram,
              DROP COLUMN MpPlans
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
              DROP COLUMN EmailConnectedAlert,
              DROP COLUMN EmailNewBlogPosts,
              DROP COLUMN EmailBookingMessages,
              DROP COLUMN WpInviteeReg,
              DROP COLUMN WpConnectedAlert,
              DROP COLUMN WpNotConnectedAlert,
              DROP COLUMN MpExpire,
              DROP COLUMN MpRewardsActivity,
              ADD MpLoyaltyProgram TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления для программ лояльности',
              DROP COLUMN MpNewPlans,
              DROP COLUMN MpPlanChanges,
              DROP COLUMN MpCheckins,
              ADD MpPlans TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления для резерваций',
              CHANGE MpBookingMessages MpBooking TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления при новых сообщениях в букинг-запросах',
              DROP COLUMN MpProductUpdates,
              DROP COLUMN MpOffers,
              DROP COLUMN MpNewBlogPosts,
              DROP COLUMN MpInviteeReg,
              DROP COLUMN MpConnectedAlert,
              DROP COLUMN MpNotConnectedAlert
        ");
    }
}
