<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\Mapper;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\Tests\Modules\DbBuilder\Account;
use AwardWallet\Tests\Modules\DbBuilder\ProviderCoupon;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Flow\JSONPath\JSONPath;
use Herrera\Version\Parser;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractMapperTest extends BaseContainerTest
{
    protected ?AccountListManager $accountListManager;

    protected ?PropertyAccessor $accessor;

    protected ?ApiVersioningService $apiVersioningService;

    protected ?OptionsFactory $optionsFactory;

    public function _before()
    {
        parent::_before();

        $this->accountListManager = $this->container->get(AccountListManager::class);
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->apiVersioningService = $this->container->get(ApiVersioningService::class);
        $this->optionsFactory = $this->container->get(OptionsFactory::class);
    }

    public function _after()
    {
        $this->accountListManager = null;
        $this->accessor = null;
        $this->apiVersioningService = null;
        $this->optionsFactory = null;

        parent::_after();
    }

    protected function assertArrayHaveKeys(array $array, array $keys, ?string $msg = null)
    {
        foreach ($keys as $key => $value) {
            if (strpos($key, '[') === 0) {
                $actual = $this->accessor->getValue($array, $key);
            } else {
                $actual = (new JSONPath($array))->find($key)->first() ?? null;
            }

            $message = ($msg ? "$msg, " : '') . $key;

            if ($this->isRegex($value)) {
                $this->assertMatchesRegularExpression($value, $actual, $message);
            } else {
                $this->assertSame($value, $actual, $message);
            }
        }
    }

    protected function assertArrayNotHaveKeys(array $array, array $keys, ?string $msg = null)
    {
        foreach ($keys as $key) {
            $this->assertNull($this->accessor->getValue($array, $key), ($msg ? "$msg, " : '') . $key);
        }
    }

    /**
     * @param string $version like "4.20.0", "android|4.21.1", "ios|4.20.0"
     */
    protected function setMobileVersion(string $version)
    {
        $parts = explode('|', $version);

        if (count($parts) === 1) {
            $platform = 'ios';
        } else {
            [$platform, $version] = $parts;
        }

        $this->apiVersioningService->setVersion(Parser::toVersion($version));
        $this->apiVersioningService->setVersionsProvider(new MobileVersions($platform));
    }

    protected function getCoupon(int $couponId, Options $options, ?int $userId = null): array
    {
        if (!is_null($userId)) {
            $user = $this->em->getRepository(Usr::class)->find($userId);
            $this->container->get(UserManager::class)->loadToken($user, false);
            $options->set(Options::OPTION_USER, $user);
        }

        return $this->accountListManager->getCoupon($options, $couponId);
    }

    protected function getAccount(int $accountId, Options $options, ?int $userId = null): array
    {
        if (!is_null($userId)) {
            $user = $this->em->getRepository(Usr::class)->find($userId);
            $this->container->get(UserManager::class)->loadToken($user, false);
            $options->set(Options::OPTION_USER, $user);
        }

        return $this->accountListManager->getAccount($options, $accountId);
    }

    /**
     * @param Account|ProviderCoupon $account
     */
    protected function testMappers(array $mappers, $account)
    {
        if ($account instanceof Account) {
            $this->dbBuilder->makeAccount($account);
        } else {
            $this->dbBuilder->makeProviderCoupon($account);
        }

        $userId = $account->getUser()->getId();

        foreach ($mappers as $set) {
            $options = $this->optionsFactory
                ->createDefaultOptions()
                ->set(Options::OPTION_FORMATTER, $this->container->get($set['mapper']));

            if (isset($set['version'])) {
                $this->setMobileVersion($set['version']);
            }

            if ($account instanceof Account) {
                $fields = $this->getAccount($account->getId(), $options, $userId);
            } else {
                $fields = $this->getCoupon($account->getId(), $options, $userId);
            }

            $this->assertNotNull($fields);
            $msg = sprintf('mapper: %s, version: %s', $set['mapper'], $set['version'] ?? 'none');

            if (count($set['haveKeys']) > 0) {
                $this->assertArrayHaveKeys($fields, $set['haveKeys'], $msg);
            }

            if (count($set['notHaveKeys']) > 0) {
                $this->assertArrayNotHaveKeys($fields, $set['notHaveKeys'], $msg);
            }
        }
    }

    protected static function mapperSet(array $haveKeys = [], array $notHaveKeys = [], ?string $mapperClass = Mapper::class): array
    {
        return [
            'mapper' => $mapperClass,
            'haveKeys' => $haveKeys,
            'notHaveKeys' => $notHaveKeys,
        ];
    }

    protected static function mobileMapperSet(string $version, array $haveKeys = [], array $notHaveKeys = [], ?string $mapperClass = MobileMapper::class): array
    {
        return [
            'mapper' => $mapperClass,
            'version' => $version,
            'haveKeys' => $haveKeys,
            'notHaveKeys' => $notHaveKeys,
        ];
    }

    private function isRegex(string $val): bool
    {
        return strpos($val, '/') === 0 && strpos($val, '/', -1) !== false;
    }
}
