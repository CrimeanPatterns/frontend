<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\UserPlusChangedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EmailPlusOnlyOptionsListener
{
    private const BACKUP_FIELDS = [
        'EmailExpiration',
        'EmailRewards',
        'EmailNewPlans',
        'EmailPlansChanges',
        'CheckinReminder',
        'EmailProductUpdates',
        'EmailOffers',
        'EmailInviteeReg',
        'EmailFamilyMemberAlert',
    ];

    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function onPlusChanged(UserPlusChangedEvent $event): void
    {
        /** @var Usr $user */
        $user = $this->entityManager->find(Usr::class, $event->getUserId());

        if ($user->isFree()) {
            $user->setFieldsBeforeDowngrade($this->getBackupFieldsValues($user));
            $this->logger->info("resetting user email preferences to free mode", ['UserID' => $event->getUserId()]);
            $user->setEmailexpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);
            $user->setEmailrewards(REWARDS_NOTIFICATION_DAY);
            $user->setEmailnewplans(true);
            $user->setEmailplanschanges(true);
            $user->setCheckinreminder(true);
            $user->setEmailproductupdates(true);
            $user->setEmailoffers(true);
            $user->setEmailInviteeReg(true);
            $user->setEmailFamilyMemberAlert(true);
            $this->entityManager->flush();
        } else {
            $this->restoreBackupFields($user);
            $this->entityManager->flush();
        }
    }

    private function getBackupFieldsValues(Usr $user)
    {
        $result = [];

        foreach (self::BACKUP_FIELDS as $field) {
            $method = $this->getMethodForField('get', $field);
            $result[$field] = $user->$method();
        }

        $this->logger->info("set FieldsBeforeDowngrade: " . json_encode($result), ["UserID" => $user->getId()]);

        return $result;
    }

    private function getMethodForField(string $prefix, string $field)
    {
        $method = $prefix . ucfirst(strtolower($field));

        if (!method_exists(Usr::class, $method)) {
            $method = $prefix . $field;
        }

        if (!method_exists(Usr::class, $method) && $prefix === "get") {
            $method = "is" . $field;
        }

        return $method;
    }

    private function restoreBackupFields(Usr $user)
    {
        if ($user->getFieldsBeforeDowngrade() === null) {
            $this->logger->info("no FieldsBeforeDowngrade to restore", ["UserID" => $user->getId()]);

            return;
        }

        $this->logger->info("there is FieldsBeforeDowngrade to restore: " . json_encode($user->getFieldsBeforeDowngrade()), ["UserID" => $user->getId()]);

        foreach ($user->getFieldsBeforeDowngrade() as $field => $value) {
            $method = $this->getMethodForField('set', $field);

            if (method_exists($user, $method)) {
                $user->$method($value);
            } else {
                $this->logger->warning("no setter for field: $field", ["UserID" => $user->getId()]);
            }
        }
    }
}
