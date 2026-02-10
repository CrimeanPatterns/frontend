<?php

namespace AwardWallet\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class AuthKeyChecker
{
    public const SKIP_AUTHKEY_CHECK = 'Skip AuthKey check';
    public const TEST_IP_ADDRESS_COOKIE_NAME = 'TestIpAddress';
    public const KEY_NAME = 'AuthKey';

    /**
     * @var string
     */
    protected $localPasswordsKey;
    /**
     * @var string
     */
    protected $localPasswordsKeyOld;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        LoggerInterface $logger,
        string $localPasswordsKey,
        string $localPasswordsKeyOld
    ) {
        $this->localPasswordsKey = $localPasswordsKey;
        $this->localPasswordsKeyOld = $localPasswordsKeyOld;
        $this->logger = $logger;
    }

    public function keyExists(Credentials $credentials): bool
    {
        $user = $credentials->getUser();

        if ($this->isUserInSkippingGroup($user)) {
            $this->logger->warning('User in skipping group "' . self::SKIP_AUTHKEY_CHECK . '"', $this->getLogContext($credentials));

            return false;
        }

        if (!empty($credentials->getRequest()->cookies->get(self::TEST_IP_ADDRESS_COOKIE_NAME))) {
            $this->logger->warning('Request has "' . self::TEST_IP_ADDRESS_COOKIE_NAME . '" cookie', $this->getLogContext($credentials));

            return false;
        }

        $authKey = $this->getAuthKey($credentials->getRequest());

        if (
            !empty($authKey['u' . $user->getUserid()])
            && ((time() - (int) $authKey['u' . $user->getUserid()]) < (DateTimeUtils::SECONDS_PER_DAY * 365 * 10))
        ) {
            $this->logger->info('AuthKey cookie exists and less than a 10 years old', $this->getLogContext($credentials));

            return true;
        }

        $this->logger->info("AuthKey cookie doesn't exist or more than a 10 years old", $this->getLogContext($credentials));

        return false;
    }

    protected function getAuthKey(Request $request)
    {
        if ($request->attributes->has(self::KEY_NAME)) {
            return $request->attributes->get(self::KEY_NAME);
        }

        $authKey = $request->cookies->get(self::KEY_NAME);

        if (!empty($authKey)) {
            $decoded = @json_decode(@AESDecode(@base64_decode($authKey), $this->localPasswordsKey), true);

            if (empty($decoded)) {
                $decoded = @json_decode(@AESDecode(@base64_decode($authKey), "%local_passwords_key%"), true);
            }

            if (empty($decoded)) {
                $decoded = @json_decode(@AESDecode(@base64_decode($authKey), $this->localPasswordsKeyOld), true);
            }
            $authKey = $decoded;
        }

        // convert v1 format to v2
        if (!empty($authKey['UserID'])) {
            $authKey = [
                "u" . $authKey['UserID'] => $authKey['Time'],
                'Noise' => $authKey['Noise'],
            ];
        }

        if (empty($authKey)) {
            $authKey = [];
        }
        $request->attributes->set(self::KEY_NAME, $authKey);

        return $authKey;
    }

    protected function getLogContext(Credentials $credentials): array
    {
        return EntryPointUtils::getLogContext($credentials, ['auth_step' => 'auth_key']);
    }

    protected function isUserInSkippingGroup(Usr $user): bool
    {
        return $user
            ->getGroups()
            ->exists(function ($_, Sitegroup $sitegroup) {
                return $sitegroup->getGroupname() == self::SKIP_AUTHKEY_CHECK;
            });
    }
}
