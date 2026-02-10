<?php

namespace Codeception\Module;

use Codeception\TestInterface;
use Doctrine\DBAL\Connection;

class Cache extends \Codeception\Module
{
    public const PERSISTENT_SERVICES = [\Memcached::class, 'public.security.access.role_hierarchy_voter'];
    public $cache = [];

    public function _initialize()
    {
        parent::_initialize();
        /** @var Symfony $symfony2 */
        $symfony2 = $this->getModule('Symfony');

        foreach (self::PERSISTENT_SERVICES as $service) {
            $this->cache[$service] = $symfony2->grabService($service);
            $symfony2->persistPermanentService($service);
        }
    }

    /**
     * **HOOK** executed before test.
     */
    public function _before(TestInterface $test)
    {
        /** @var \Memcached $cache */
        $cache = $this->cache[\Memcached::class];
        $cache->clear();
    }

    public function _after(TestInterface $test)
    {
        /** @var Symfony $symfony2 */
        $symfony2 = $this->getModule('Symfony');
        $container = $symfony2->_getContainer();

        if ($container->has('doctrine.dbal.default_connection')) {
            /** @var Connection $defaultConnection */
            $defaultConnection = $container->get('doctrine.dbal.default_connection');

            // rollback all transaction, otherwise we will catch lock wait in CustomDb::removeInserted
            while ($defaultConnection->isTransactionActive()) {
                $defaultConnection->rollBack();
            }
        }
    }
}
