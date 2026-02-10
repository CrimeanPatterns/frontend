<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\UserPushDeviceChangedEvent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sinergi\BrowserDetector\Browser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class MobileDeviceManager
{
    public const REQUEST_ATTRIBUTE_DEVICE_ID = 'mobile_device_id';
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var \Doctrine\Persistence\ObjectRepository
     */
    private $rep;

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var GeoLocation
     */
    private $geoLocation;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;
    private LoggerInterface $securityLogger;
    private BinaryLoggerFactory $check;

    private EventDispatcherInterface $eventDispatcher;
    private ?Statement $existsStmt = null;

    public function __construct(
        EntityManagerInterface $em,
        GeoLocation $geoLocation,
        RequestStack $requestStack,
        AwTokenStorageInterface $tokenStorage,
        EncoderFactoryInterface $encoderFactory,
        LoggerInterface $securityLogger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->rep = $em->getRepository(MobileDevice::class);
        $this->connection = $em->getConnection();
        $this->geoLocation = $geoLocation;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->encoderFactory = $encoderFactory;
        $this->securityLogger =
            (new ContextAwareLoggerWrapper($securityLogger))
            ->setMessagePrefix('mobile device manager: ')
            ->pushContext([Context::SERVER_MODULE_KEY => 'mobile_device_manager']);
        $this->check = (new BinaryLoggerFactory($this->securityLogger))->toInfo();
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $deviceType
     * @param string $deviceKey
     * @return MobileDevice
     * @throws \RuntimeException
     */
    public function addDevice(
        $userId,
        $deviceType,
        $deviceKey,
        $lang,
        $appVersion,
        $ip,
        ?SessionInterface $session = null,
        bool $tracked = true,
        ?string $userAgent = null
    ) {
        try {
            $type = MobileDevice::getTypeId($deviceType);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        // check for existence
        $connection = $this->em->getConnection();
        $connection->executeQuery(
            'DELETE FROM MobileDevice WHERE DeviceKey = ? AND DeviceType = ? AND UserID <> ?',
            [$deviceKey, $type, $userId],
            [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        $this->renewDeviceKey(
            $deviceKey,
            $type,
            $userId,
            $lang,
            $appVersion,
            $ip,
            $tracked,
            $userAgent
        );

        $result = $this->rep->findOneBy([
            'userId' => $userId,
            'deviceKey' => $deviceKey,
            'deviceType' => $type,
        ]);

        // leave only last used browser/domain, do not subscribe on different domains and browsers
        if (strpos($appVersion, 'web') === 0 && !empty($result)) {
            $connection->executeUpdate("delete from MobileDevice where UserID = ? and AppVersion = ? and MobileDeviceID <> ?", [$userId, $appVersion, $result->getMobileDeviceId()]);
        }

        if ($result && $userId && $session && $session->isStarted()) {
            $this->updateRememberMeTokenBySession($userId, $result, $session->getId());
        }

        if ($result && $userId) {
            $user = $this->em->getRepository(Usr::class)->find($userId);

            if ($user) {
                $this->eventDispatcher->dispatch(new UserPushDeviceChangedEvent($user));
            }
        }

        return $result;
    }

    //    public function removeDevice($userId, $device, $deviceKey)
    //    {
    //        $user = $this->em->find('AwardWalletMainBundle:Usr', $userId);
    //        $this->em->getConnection()->executeQuery('
    //            DELETE FROM `MobileDevice`
    //            WHERE
    //                `DeviceKey` = ? AND
    //                `DeviceType` = ?
    //            ',
    //            [$deviceKey, $this->getType($device), $userId],
    //            [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]
    //        );
    //    }

    /**
     * @param string|string[] $deviceKey
     * @param string $deviceType
     */
    public function removeDeviceByKey($deviceKey, $deviceType)
    {
        if (!is_array($deviceKey)) {
            $deviceKey = [$deviceKey];
        }

        try {
            $typeId = MobileDevice::getTypeId($deviceType);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        $devices = $this->rep->findBy([
            'deviceKey' => $deviceKey,
            'deviceType' => $typeId,
        ]);
        $users = [];

        foreach ($devices as $device) {
            if ($device->getUser() && !isset($users[$device->getUser()->getId()])) {
                $users[$device->getUser()->getId()] = $device->getUser();
            }

            $this->em->remove($device);
        }

        $this->em->flush();

        foreach ($users as $user) {
            $this->eventDispatcher->dispatch(new UserPushDeviceChangedEvent($user));
        }
    }

    public function removeDeviceById($deviceId, bool $force = true)
    {
        $device = $this->rep->find($deviceId);
        $user = ($device && $device->getUser()) ? $device->getUser() : null;

        if ($force) {
            $this->connection->executeQuery(
                '
                DELETE FROM `MobileDevice`
                WHERE `MobileDeviceID` = ?',
                [$deviceId],
                [\PDO::PARAM_INT]
            );
        } else {
            $this->connection->executeQuery(
                '
                update `MobileDevice`
                set 
                    `Tracked` = 0,
                    `UpdateDate` = NOW()
                WHERE `MobileDeviceID` = ? and `Tracked` = 1',
                [$deviceId],
                [\PDO::PARAM_INT]
            );
        }

        if ($user) {
            $this->eventDispatcher->dispatch(new UserPushDeviceChangedEvent($user));
        }
    }

    public function removeDesktopDevicesByUser(Usr $user)
    {
        $this->connection->executeQuery(
            '
            DELETE FROM MobileDevice
            WHERE DeviceType IN (?) AND UserID = ?',
            [[MobileDevice::TYPE_SAFARI, MobileDevice::TYPE_CHROME, MobileDevice::TYPE_FIREFOX], $user->getId()],
            [Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT]
        );

        $this->eventDispatcher->dispatch(new UserPushDeviceChangedEvent($user));
    }

    /**
     * @param string $key
     * @param int $type
     * @param int $userId
     * @param string $lang
     * @param string $appVersion
     * @throws \Doctrine\DBAL\DBALException
     */
    public function renewDeviceKey(
        $key,
        $type,
        $userId,
        $lang,
        $appVersion,
        $ip,
        bool $tracked = true,
        ?string $userAgent = null
    ) {
        $countryId = $this->geoLocation->getCountryIdByIp($ip);

        $this->connection->executeQuery(
            '
            INSERT INTO `MobileDevice` (
                `DeviceKey`, 
                `DeviceType`, 
                `UserID`, 
                `Lang`, 
                `AppVersion`, 
                `IP`, 
                `CountryID`, 
                `CreationDate`, 
                `UpdateDate`, 
                `Tracked`,
                `UserAgent`
            ) VALUES(?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?) 
            ON DUPLICATE KEY UPDATE 
                `DeviceKey` = VALUES(`DeviceKey`), 
                `AppVersion` = VALUES(`AppVersion`), 
                `IP` = VALUES(`IP`),
                `UpdateDate` = VALUES(`UpdateDate`),
                `Tracked` = VALUES(`Tracked`)
            '
            . (!empty($countryId) ? ', `CountryID` = VALUES(`CountryID`)' : '')
            . (!empty($userId) ? ', `UserID` = VALUES(`UserID`)' : '')
            . (!empty($userAgent) ? ', `UserAgent` = VALUES(`UserAgent`)' : ''),
            [
                $key,
                $type,
                $userId,
                \substr($lang, 0, 8),
                \substr($appVersion, 0, 16),
                $ip,
                $countryId,
                $tracked,
                $userAgent,
            ],
            [
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
            ]
        );
    }

    public function renewDeviceKeyByNotification(Notification $notification, $newDeviceKey)
    {
        $ip = $this->connection->executeQuery('select IP from MobileDevice where MobileDeviceID = ?', [$notification->getDeviceId()], [\PDO::PARAM_INT])->fetchColumn(0);
        $this->removeDeviceById($notification->getDeviceId());
        $this->renewDeviceKey($newDeviceKey, $notification->getDeviceType(), $notification->getUserId(), $notification->getDeviceLang(), $notification->getDeviceAppVersion(), $ip);
        $user = $this->em->getRepository(Usr::class)->find($notification->getUserId());

        if ($user) {
            $this->eventDispatcher->dispatch(new UserPushDeviceChangedEvent($user));
        }
    }

    public function updateDeviceInfo($userId, $device, $lang, $appVersion)
    {
        $this->connection->executeQuery(
            '
            UPDATE `MobileDevice`
            SET
                Lang = ?,
                AppVersion = ?,
                UpdateDate = NOW()
            WHERE
                MobileDeviceID = ? AND
                UserID = ?',
            [\substr($lang, 0, 8),           \substr($appVersion, 0, 16),     $device,         $userId],
            [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]
        );
    }

    public function updateRememberMeTokenBySession(int $userid, MobileDevice $mobileDevice, string $session): void
    {
        $this->securityLogger->info('relinking device to remember-me token by session');

        $rememberMeTokenId = $this->connection->executeQuery("
            select 
               s.RememberMeTokenID 
            from Session s 
            where 
                  s.SessionID = :sessionid AND
                  s.UserID = :userid",
            [
                "sessionid" => $session,
                "userid" => $userid,
            ]
        )->fetchColumn();

        if (
            $this->check->that('remember-me token')->was('found by sessionid')
            ->on(isset($rememberMeTokenId))
        ) {
            $token = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Remembermetoken::class)->find($rememberMeTokenId);

            if ($token) {
                $mobileDevice->setRememberMeToken($token);
                $mobileDevice->setUpdateDate(new \DateTime());
            }

            $this->em->flush($mobileDevice);
        }
    }

    public function forgetUserByRememberMeTokenId(Usr $user, int $rememberTokenId): void
    {
        $this->connection->executeUpdate('
            UPDATE MobileDevice
            SET 
                UserID = null,
                UpdateDate = NOW()
            WHERE 
                  UserID = :userid AND
                  RememberMeTokenID = :tokenid', [
            ':userid' => $user->getId(),
            ':tokenid' => $rememberTokenId,
        ]);

        $this->eventDispatcher->dispatch(new UserPushDeviceChangedEvent($user));
    }

    public function forgetUserBySessionId(Usr $user, string $sessionid): void
    {
        $this->connection->executeUpdate('
            UPDATE MobileDevice md
            JOIN Session s on 
                md.UserID = :userid AND
                md.RememberMeTokenID = s.RememberMeTokenID
            SET 
                md.UserID = null,
                md.UpdateDate = NOW()
            WHERE 
                  s.UserID = :userid AND
                  s.SessionID = :sessionid', [
            ':userid' => $user->getId(),
            ':sessionid' => $sessionid,
        ]);

        $this->eventDispatcher->dispatch(new UserPushDeviceChangedEvent($user));
    }

    public function getCurrentDevice(): ?MobileDevice
    {
        $this->securityLogger->info('searching current device');
        $request = $this->requestStack->getMasterRequest();

        if (!$request) {
            return null;
        }

        $session = $request->getSession();

        if (!$session || !$session->isStarted()) {
            return null;
        }

        $user = $this->tokenStorage->getBusinessUser();

        if (!$user) {
            return null;
        }

        $rememberMeToken = $this->connection->executeQuery('
            select s.RememberMeTokenID 
            from Session s
            where
                s.SessionID = :sessionid and
                s.UserID = :userid
        ', [
            "userid" => $user->getUserid(),
            "sessionid" => $session->getId(),
        ])->fetchColumn();

        if (
            $this->check->that('remember-me token')->wasNot('found by sessionid')
            ->on(!isset($rememberMeToken) || (false === $rememberMeToken))
        ) {
            return null;
        }

        return
            $this->check->that('current device')->was('found by remember-me token')
            ->on(
                $currentDevice = $this->rep->findOneBy(['userId' => $user->getUserid(), 'rememberMeToken' => $rememberMeToken]),
                $currentDevice ? ['mobile_device_id' => $currentDevice->getMobileDeviceId()] : []
            );
    }

    public function generateKeychainForCurrentDevice(): ?string
    {
        $this->securityLogger->info('generating keychain secret for current device');
        $device = $this->getCurrentDevice();

        if (!$device) {
            return null;
        }

        $encoder = $this->encoderFactory->getEncoder($device->getUser());
        $device->setSecret($encoder->encodePassword($secret = StringUtils::getRandomCode(64), ''));
        $this->em->persist($device);
        $this->em->flush();

        return $secret;
    }

    public function getDeviceName(MobileDevice $device): string
    {
        $deviceName = $device->getName();

        if ('ios' == $deviceName) {
            return 'iOS Device';
        } elseif (\in_array($deviceName, ['android', 'pushy-android'], true)) {
            return 'Android Device';
        } elseif ('safari' == $deviceName) {
            return 'Safari';
        } elseif ('chrome' == $deviceName) {
            $detector = new Browser($device->getUseragent());
            $detectedName = $detector->getName();

            return $detectedName !== Browser::UNKNOWN ? $detectedName : 'Chrome';
        } elseif ('firefox' == $deviceName) {
            return 'Mozilla Firefox';
        }

        return '';
    }

    public function deviceExists(int $deviceId): bool
    {
        if (!$this->existsStmt) {
            $this->existsStmt = $this->connection->prepare('
                SELECT 1
                FROM MobileDevice 
                WHERE MobileDeviceID = :deviceId');
        }

        return false !== $this->existsStmt->executeQuery(['deviceId' => $deviceId])->fetchOne();
    }
}
