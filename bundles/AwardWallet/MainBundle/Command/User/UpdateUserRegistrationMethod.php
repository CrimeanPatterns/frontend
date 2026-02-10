<?php

namespace AwardWallet\MainBundle\Command\User;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUserRegistrationMethod extends Command
{
    protected static $defaultName = 'aw:user:update-registration-method';

    private LoggerInterface $logger;
    private Connection $connection;
    private Connection $unbufConnection;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        Connection $unbufConnection
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->connection = $connection;
        $this->unbufConnection = $unbufConnection;
    }

    public function configure(): void
    {
        $this->setDescription('Fill Usr.RegistrationMethod, Usr.RegistrationPlatform');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stmt = $this->unbufConnection->executeQuery('
            SELECT
                    u.UserID, u.CreationDateTime,
                    uoa.CreateDate AS oauth_CreateDate, uoa.Provider,
                    md.CreationDate AS device_CreateDate, md.DeviceType
            FROM Usr u
            LEFT JOIN UserOAuth uoa ON (uoa.UserID = u.UserID AND uoa.CreateDate = (SELECT MIN(CreateDate) FROM UserOAuth uoa_once WHERE uoa_once.UserID = uoa.UserID))
            LEFT JOIN MobileDevice md ON (md.UserID = u.UserID AND md.CreationDate = (SELECT MIN(CreationDate) FROM MobileDevice md_once WHERE md_once.UserID = md.UserID))
            WHERE
                     u.RegistrationMethod IS NULL
                OR   u.RegistrationPlatform IS NULL
        ');
        $stmt->execute();

        $updated = 0;

        while ($user = $stmt->fetchAssociative()) {
            // $this->testJoinFirstRow($user); // local only

            $method = Usr::REGISTRATION_METHOD_FORM;
            $platform = Usr::REGISTRATION_PLATFORM_DESKTOP_BROWSER;

            $userId = (int) $user['UserID'];
            $userCreateDateTime = strtotime($user['CreationDateTime']);
            $oauthCreateDate = empty($user['oauth_CreateDate']) ? null : strtotime($user['oauth_CreateDate']);
            $deviceCreateDate = empty($user['device_CreateDate']) ? null : strtotime($user['device_CreateDate']);
            $provider = empty($user['provider']) ? null : $user['provider'];
            $deviceType = empty($user['DeviceType']) ? null : (int) $user['DeviceType'];

            if (!empty($provider) && !array_key_exists($provider, Usr::REGISTRATION_METHODS)) {
                throw new \RuntimeException('Unknown OAuth provider');
            }

            if ($this->isNearby($userCreateDateTime, $oauthCreateDate)) {
                $method = Usr::REGISTRATION_METHODS[$provider];
            }

            if ($this->isNearby($userCreateDateTime, $deviceCreateDate)
                && in_array($deviceType, MobileDevice::TYPES_MOBILE, true)) {
                $platform = Usr::REGISTRATION_PLATFORM_MOBILE_APP;
            }

            $this->connection->update(
                'Usr',
                ['RegistrationMethod' => $method, 'RegistrationPlatform' => $platform],
                ['UserID' => $userId]
            );

            ++$updated;
        }

        $output->writeln('done. updated ' . $updated . ' users');

        return 0;
    }

    /**
     * True if there are less than 5 minutes between dates.
     */
    private function isNearby($dateFirst, $dateSecond): bool
    {
        if (empty($dateFirst) || empty($dateSecond)) {
            return false;
        }

        $diffInSeconds = $dateSecond - $dateFirst;

        if ($diffInSeconds < (3 * 60)) {
            return true;
        }

        return false;
    }

    private function testJoinFirstRow($user)
    {
        if (!empty($user['oauth_CreateDate'])) {
            // echo 'oa - checked' . PHP_EOL;
            $first = $this->connection->fetchAssociative('SELECT CreateDate, Provider FROM UserOAuth WHERE UserID = ' . $user['UserID'] . ' ORDER BY CreateDate ASC LIMIT 1');

            if ($first['CreateDate'] !== $user['oauth_CreateDate'] || $first['Provider'] !== $user['Provider']) {
                throw new \Exception('OAuth - missing get first row');
            }
        }

        if (!empty($user['device_CreateDate'])) {
            // echo 'md - checked' . PHP_EOL;
            $first = $this->connection->fetchAssociative('SELECT CreationDate FROM MobileDevice WHERE UserID = ' . $user['UserID'] . ' ORDER BY CreationDate ASC LIMIT 1');

            if ($first['CreationDate'] !== $user['device_CreateDate']) {
                throw new \Exception('MobileDevice - missing get first row');
            }
        }
    }
}
