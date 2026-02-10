<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\NotificationTemplate;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ManagerSendNotification
{
    public const SEND = 1;
    public const NOT_SEND = 0;
    public const NO_DEVICES = -1;
    public const DESKTOP_DISABLED = -2;

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private Sender $sender;

    private $ntRep;
    private $userRep;
    private $loggerPrefix;
    private $testMode;

    public function __construct(EntityManagerInterface $em, Sender $sender, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->sender = $sender;

        $this->ntRep = $em->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
        $this->userRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        $this->loggerPrefix = 'ManagerSendNotification';
        $this->testMode = false;
    }

    public function setLoggerPrefix($prefix)
    {
        $this->loggerPrefix = $prefix;
    }

    public function setTestMode($mode)
    {
        $this->testMode = $mode;
    }

    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->ntRep = $em->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
        $this->userRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
    }

    public function send(NotificationTemplate $notification)
    {
        $notificationId = $notification->getNotificationTemplateID();
        $queueCnt = $notification->getQueueStat();
        $sentCnt = $notification->getSendStat();
        $notificationMessage = new Content(
            $notification->getTitle(),
            $notification->getMessage(),
            $notification->getType(),
            $notification->getLink(),
            (new Options())
                ->setDeadlineTimestamp($notification->getTTL()->getTimestamp())
                ->setAutoClose($notification->isAutoClose())
                ->setPriority(0)
        );

        $this->em->clear();

        if ($notification->getDeliveryMode() == NotificationTemplate::DELIVERY_MODE_DEFAULT) {
            $deviceTypes = [MobileDevice::TYPES_DESKTOP, MobileDevice::TYPES_MOBILE];
        } elseif ($notification->getDeliveryMode() == NotificationTemplate::DELIVERY_MODE_MOBILE_AND_DESKTOP) {
            $deviceTypes = [MobileDevice::TYPES_ALL];
        } elseif ($notification->getDeliveryMode() == NotificationTemplate::DELIVERY_MODE_DESKTOP) {
            $deviceTypes = [MobileDevice::TYPES_DESKTOP];
        } elseif ($notification->getDeliveryMode() == NotificationTemplate::DELIVERY_MODE_MOBILE) {
            $deviceTypes = [MobileDevice::TYPES_MOBILE];
        } else {
            return true;
        }

        if (!$this->testMode) {
            $this->ntRep->setState($notificationId, NotificationTemplate::STATE_SENDING);
        }

        $startTime = microtime(true);

        $processed = 0;

        foreach ($deviceTypes as $deviceType) {
            foreach ($notification->getUserGroups() as $group) {
                $this->info("calculate group users: " . $group, ['notificationId' => $notificationId, 'device' => $deviceType]);
                $users = $this->ntRep->getUsersFromGroup($group, $notificationId, $deviceType);
                $this->info("group users count: " . count($users), ['notificationId' => $notificationId, 'device' => $deviceType]);

                if (count($users) > 0) {
                    $this->info("start sending to group: " . $group, ['notificationId' => $notificationId, 'device' => $deviceType]);
                    $sent = 0;

                    foreach ($users as $userId) {
                        if (is_numeric($userId)) {
                            $user = $this->userRep->find(intval($userId));

                            if ($user && !$user->isBusiness()) {
                                $this->info("sending to user: " . $user->getEmail(), ['notificationId' => $notificationId, 'device' => $deviceType]);

                                if ($this->sendToUser($user, $deviceType, $notificationMessage)) {
                                    $sent++;
                                    $this->info("sent to user: " . $user->getEmail(), ['notificationId' => $notificationId, 'device' => $deviceType]);
                                } else {
                                    $this->info("not sent to user: " . $user->getEmail(), ['notificationId' => $notificationId, 'device' => $deviceType]);
                                }

                                if (!$this->testMode) {
                                    $this->ntRep->recordLogNotification($user, $notificationId);
                                }
                            }
                        } elseif (filter_var($userId, FILTER_VALIDATE_IP)) {
                            $this->info("sending to user IP: " . $userId, ['notificationId' => $notificationId, 'device' => $deviceType]);

                            if ($this->sendToIP($userId, $deviceType, $notificationMessage)) {
                                $sent++;
                                $this->info("sent to user IP: " . $userId, ['notificationId' => $notificationId, 'device' => $deviceType]);
                            } else {
                                $this->info("not sent to user IP: " . $userId, ['notificationId' => $notificationId, 'device' => $deviceType]);
                            }
                        }
                        $processed++;

                        if (($processed % 50) == 0) {
                            $this->em->clear();
                            $now = microtime(true);
                            $speed = round(100 / ($now - $startTime), 1);
                            $this->warning("processed {$processed} devices, mem: " . round(memory_get_usage(true) / 1024 / 1024, 1) . " Mb, speed: $speed u/s..", ['notificationId' => $notificationId, 'device' => $deviceType]);
                            $startTime = $now;
                        }
                    }

                    if (!$this->testMode) {
                        $queueCnt += count($users);
                        $this->ntRep->setQueueStat($notificationId, $queueCnt);
                        $sentCnt += $sent;
                        $this->ntRep->setSendStat($notificationId, $sentCnt);
                    }
                    $this->warning("finish sending to group: " . $group . ", success: " . $sent . ", error: " . (count($users) - $sent), ['notificationId' => $notificationId, 'device' => $deviceType]);
                }
            }
        }

        if (!$this->testMode) {
            $this->ntRep->setState($notificationId, NotificationTemplate::STATE_DONE);
        }

        return true;
    }

    /**
     * @param Usr|string $user
     * @return int
     */
    protected function sendToDesktop($user, Content $message)
    {
        if ($user instanceof Usr) {
            $desktopDevices = $this->sender->loadDevices([$user], MobileDevice::TYPES_DESKTOP, $message->type);

            if (count($desktopDevices)) {
                if ($this->testMode) {
                    return self::SEND;
                }

                $sended = $this->sender->send($message, $desktopDevices);

                if ($sended) {
                    return self::SEND;
                }

                return self::NOT_SEND;
            }

            return self::NO_DEVICES;
        } elseif (filter_var($user, FILTER_VALIDATE_IP)) {
            $desktopDevices = $this->sender->loadAnonymousDevices([$user], MobileDevice::TYPES_DESKTOP, $message->type);

            if (count($desktopDevices)) {
                if ($this->testMode) {
                    return self::SEND;
                }

                $sended = $this->sender->send($message, $desktopDevices);

                if ($sended) {
                    return self::SEND;
                }

                return self::NOT_SEND;
            }

            return self::NO_DEVICES;
        }

        return self::NOT_SEND;
    }

    /**
     * @return int
     */
    protected function sendToMobile($user, Content $message)
    {
        if ($user instanceof Usr) {
            $mobileDevices = $this->sender->loadDevices([$user], MobileDevice::TYPES_MOBILE, $message->type);

            if (count($mobileDevices)) {
                if ($this->testMode) {
                    return self::SEND;
                }

                $sended = $this->sender->send($message, $mobileDevices);

                if ($sended) {
                    return self::SEND;
                }

                return self::NOT_SEND;
            }

            return self::NO_DEVICES;
        } elseif (filter_var($user, FILTER_VALIDATE_IP)) {
            $mobileDevices = $this->sender->loadAnonymousDevices([$user], MobileDevice::TYPES_MOBILE, $message->type);

            if (count($mobileDevices)) {
                if ($this->testMode) {
                    return self::SEND;
                }

                $sended = $this->sender->send($message, $mobileDevices);

                if ($sended) {
                    return self::SEND;
                }

                return self::NOT_SEND;
            }

            return self::NO_DEVICES;
        }

        return self::NOT_SEND;
    }

    private function info($message, $extra = [])
    {
        $this->logger->info($this->loggerPrefix . ': ' . $message, $extra);
    }

    private function warning($message, $extra = [])
    {
        $this->logger->warning($this->loggerPrefix . ': ' . $message, $extra);
    }

    private function error($message, $extra = [])
    {
        $this->logger->error($this->loggerPrefix . ': ' . $message, $extra);
    }

    /**
     * @param Content $message
     * @return bool
     */
    private function sendToUser(Usr $user, $deviceType, $message)
    {
        if ($deviceType == MobileDevice::TYPES_DESKTOP) {
            $resultDesktop = $this->sendToDesktop($user, $message);

            if ($resultDesktop === self::SEND) {
                return true;
            }

            return false;
        } elseif ($deviceType == MobileDevice::TYPES_MOBILE) {
            $resultDesktop = $this->sendToMobile($user, $message);

            if ($resultDesktop === self::SEND) {
                return true;
            }

            return false;
        } elseif (MobileDevice::TYPES_ALL == $deviceType) {
            $resultDesktop = $this->sendToDesktop($user, $message);
            $resultMobile = $this->sendToMobile($user, $message);

            return in_array(self::SEND, [$resultDesktop, $resultMobile]);
        }

        return false;
    }

    /**
     * @return bool
     */
    private function sendToIP($ip, $device, $message)
    {
        if ($device == MobileDevice::TYPES_DESKTOP) {
            $resultDesktop = $this->sendToDesktop($ip, $message);

            if ($resultDesktop === self::SEND) {
                return true;
            }

            return false;
        }

        return false;
    }
}
