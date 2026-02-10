#!/usr/bin/env bash

cloc \
  frontend/bundles/AwardWallet/MainBundle/Controller/UserMailboxController.php \
  frontend/bundles/AwardWallet/MainBundle/Controller/MailboxProgressController.php \
  frontend/bundles/AwardWallet/MainBundle/Controller/TimelineController.php \
  frontend/bundles/AwardWallet/MobileBundle/Controller/Timeline/ \
  frontend/bundles/AwardWallet/MobileBundle/Controller/MailboxController.php \
  frontend/bundles/AwardWallet/MainBundle/Scanner/Oauth/ \
  frontend/web/assets/awardwalletnewdesign/js/pages/mailbox/ \
  \
  email/web/AwardWallet/EmailBundle/Scanner/ \
  email/web/AwardWallet/EmailBundle/Controller/MailboxAuthController.php \
  email/web/AwardWallet/EmailBundle/Controller/MailboxController.php \
  email/web/AwardWallet/EmailBundle/Controller/PushController.php \
  email/web/AwardWallet/EmailBundle/Controller/Admin/ScannerController.php \
  email/web/AwardWallet/EmailBundle/Entity/*Mailbox*.php \
  email/web/AwardWallet/EmailBundle/Entity/Partner.php \
  email/web/AwardWallet/EmailBundle/Security/ \
  email/web/AwardWallet/EmailBundle/Command/ImapControllerCommand.php \
  email/web/AwardWallet/EmailBundle/Command/MailboxSchedulerCommand.php \

