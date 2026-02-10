<?php

namespace Codeception\Module;

// here you can define custom functions for TestGuy

use AwardWallet\MainBundle\DependencyInjection\PublicTestAliasesCompilerPass;
use AwardWallet\Tests\Modules\Utils\Prophecy\ObjectProphecyExtended;
use AwardWallet\Tests\Modules\Utils\Prophecy\ProphetExtended;
use Codeception\Module;
use Codeception\TestCase;
use Codeception\Util\Stub;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SymfonyTestHelper extends Module
{
    /**
     * @var MockObject[]
     */
    private $mocks = [];
    /**
     * @var array<string, array-key>
     */
    private $mocksHashIdsMap = [];
    private $aliasesTargets = [];
    private $mocksVerified = false;
    private ?Prophet $prophet = null;

    /**
     * @return array{0: bool, 1: array}
     */
    public function __getMocksStateInternal(): array
    {
        return [
            $this->mocksVerified,
            it($this->prophet ? $this->prophet->getProphecies() : [])
                ->map(fn (ObjectProphecy $objectProphecy) => $objectProphecy->reveal())
                ->chain(
                    it($this->mocks)
                    ->filter(fn (object $mock) => $mock instanceof MockObject || $mock instanceof ObjectProphecy)
                )
                ->reindex(fn (object $mock) => \spl_object_hash($mock))
                ->collectWithKeys()
                ->values()
                ->toArray(),
        ];
    }

    public function _before(TestCase $test)
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        $symfony->client->setServerParameter('HTTP_HOST', $symfony->_getContainer()->getParameter("host"));

        if ($symfony->_getContainer()->getParameter("requires_channel") == 'https') {
            $symfony->client->setServerParameter('HTTPS', 'on');
        }
        $symfony->client->setServerParameter("whiteListedIp", "1");

        if (empty($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        } // for shieldsquare
        $this->mocks = [];
        $this->prophet = null;
        $this->mocksHashIdsMap = [];
        $this->mocksVerified = false;

        global $symfonyContainer;
        $symfonyContainer = $symfony->_getContainer();
        $this->aliasesTargets = $symfonyContainer->getParameter("aliases_targets");
    }

    public function _after(TestCase $test)
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        /** @var Cache $cache */
        $cache = null;

        if ($this->hasModule('Cache')) {
            $cache = $this->getModule('Cache');
        }

        foreach ($this->mocks as $id => $_) {
            if (\is_int($id)) {
                continue;
            }

            $symfony->unpersistService($id);

            // restore permanent services, because unpersist service removed them
            if (in_array($id, Cache::PERSISTENT_SERVICES)) {
                $container = $symfony->_getContainer();
                self::doSetServiceInContainer($id, $cache->cache[$id], $container);
                $symfony->persistPermanentService($id);
            }
        }

        $this->mocks = [];
        $this->mocksHashIdsMap = [];
    }

    public function setHeader($name, $value)
    {
        /** @var Symfony $module */
        $module = $this->getModule('Symfony');
        $module->client->setServerParameter('HTTP_' . $name, $value);
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        /** @var Symfony $symfony2 */
        $symfony2 = $this->getModule('Symfony');

        return $symfony2->_getContainer();
    }

    public function mockService($id, object $mock)
    {
        /** @var Symfony $symfony2 */
        $symfony2 = $this->getModule('Symfony');
        $container = $symfony2->_getContainer();

        if ($container->has(PublicTestAliasesCompilerPass::TEST_SERVICE_PREFIX . $id)) {
            $id = PublicTestAliasesCompilerPass::TEST_SERVICE_PREFIX . $id;
        }

        if (array_key_exists($id, $this->aliasesTargets)) {
            $id = $this->aliasesTargets[$id];
        }

        self::doSetServiceInContainer($id, $mock, $container);
        $symfony2->persistService($id);

        $hash = \spl_object_hash($mock);

        if (isset($this->mocksHashIdsMap[$hash])) {
            [$_, $mocksNumericIdx] = $this->mocksHashIdsMap[$hash];
            unset($this->mocks[$mocksNumericIdx]);
            $this->mocksHashIdsMap[$hash] = $id;
        }

        $this->mocks[$id] = $mock;
    }

    public static function doSetServiceInContainer(string $id, object $mock, ContainerInterface $container): void
    {
        /*
         * Since Symfony 4.1 container layout may look like this: TestContainer(Kernel(Container(services = [...]))).
         * If service is initialized we can safely provide its implementation to container.
         * Otherwise try to extract inner container and replace service with reflection.
         *
         * @link https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
         * @link \Codeception\Module\Symfony::_getContainer
         */
        if ($container instanceof TestContainer) {
            if ($container->initialized($id)) {
                $testContainerRefl = new \ReflectionMethod($container, 'getPublicContainer');
                $testContainerRefl->setAccessible(true);
                $container = $testContainerRefl->invoke($container);
                $testContainerRefl->setAccessible(false);
            } else {
                $container->set($id, $mock);

                return;
            }
        }

        if ($container instanceof \Symfony\Component\DependencyInjection\Container) {
            if ($container->initialized($id)) {
                $reflAliases = new \ReflectionProperty($container, 'aliases');
                $reflAliases->setAccessible(true);
                $aliases = $reflAliases->getValue($container);
                unset($aliases[$id]);
                $reflAliases->setValue($container, $aliases);
                $reflAliases->setAccessible(false);

                $reflServices = new \ReflectionProperty($container, 'services');
                $reflServices->setAccessible(true);
                $services = $reflServices->getValue($container);
                $services[$id] = $mock;
                $reflServices->setValue($container, $services);
                $reflServices->setAccessible(false);

                return;
            } else {
                $container->set($id, $mock);

                return;
            }
        }

        throw new \LogicException('Unknown container layout');
    }

    public function prophesize(string $class): ObjectProphecyExtended
    {
        return $this->getProphet()->prophesize($class);
    }

    /**
     * @param string|object $classOrObject
     * @return ObjectProphecyExtended[]
     */
    public function prophesizeConstructorArguments($classOrObject, array $argsMixinMap = []): array
    {
        return $this->getProphet()->prophesizeConstructorArguments($classOrObject, $argsMixinMap);
    }

    /**
     * @param string|object $classOrObject
     * @return ObjectProphecyExtended[]
     */
    public function prophesizeConstructorArgumentsMuted($classOrObject, array $argsMixinMap = []): array
    {
        return $this->getProphet()->prophesizeConstructorArgumentsMuted($classOrObject, $argsMixinMap);
    }

    public function makeProphesized($classOrObject, array $argsMixinMap = []): object
    {
        return new $classOrObject(
            ...$this->prophesizeConstructorArguments($classOrObject, $argsMixinMap)
        );
    }

    public function makeProphesizedMuted($classOrObject, array $argsMixinMap = []): object
    {
        return new $classOrObject(
            ...$this->prophesizeConstructorArgumentsMuted($classOrObject, $argsMixinMap)
        );
    }

    public function verifyMocks()
    {
        try {
            foreach ($this->mocks as $mock) {
                if ($mock instanceof MockObject) {
                    $mock->__phpunit_verify();
                }
            }

            if ($this->prophet && $this->prophet->getProphecies()) {
                $this->prophet->checkPredictions();
            }
        } finally {
            $this->mocksVerified = true;
        }
    }

    /**
     * @template RealInstanceType of object
     * @param class-string<RealInstanceType>|RealInstanceType|callable(): class-string<RealInstanceType> $class - A class to be mocked
     * @param array $params - properties and methods to set
     * @param bool|\PHPUnit\Framework\TestCase $testCase
     * @return \PHPUnit\Framework\MockObject\MockObject&RealInstanceType - mock
     */
    public function stubMake($class, array $params = [], $testCase = false)
    {
        return $this->addMock(Stub::make($class, $params, $testCase));
    }

    /**
     * @template RealInstanceType of object
     * @param class-string<RealInstanceType>|RealInstanceType|callable(): class-string<RealInstanceType> $class - A class to be mocked
     * @param bool|\PHPUnit\Framework\TestCase $testCase
     * @return \PHPUnit\Framework\MockObject\MockObject&RealInstanceType
     */
    public function stubMakeEmpty($class, array $params = [], $testCase = false)
    {
        return $this->addMock(Stub::makeEmpty($class, $params, $testCase));
    }

    /**
     * @template AddMockInstance
     * @param AddMockInstance&object $mock
     * @return AddMockInstance
     */
    public function addMock(object $mock)
    {
        $hash = \spl_object_hash($mock);

        if (isset($this->mocksHashIdsMap[$hash])) {
            return $this->mocks[$this->mocksHashIdsMap[$hash]];
        }

        $this->mocks[] = $mock;
        $this->mocksHashIdsMap[$hash] = \count($this->mocks) - 1;

        return $mock;
    }

    protected function getProphet(): ProphetExtended
    {
        if (null === $this->prophet) {
            $this->prophet = new ProphetExtended();
        }

        return $this->prophet;
    }
}
