<?php

namespace AwardWallet\Tests\Unit;

use Codeception\Module\SymfonyTestHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseContainerTest extends BaseTest
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function _before()
    {
        global $symfonyContainer;

        parent::_before();
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        $this->container = $symfony->_getContainer();
        $symfonyContainer = $this->container;
        $this->em = $this->container->get('doctrine.orm.default_entity_manager');
        $this->em->clear();
    }

    public function _after()
    {
        global $symfonyContainer, $Connection;

        /** @var Symfony $symfony */
        if ($this->em) {
            $this->em->clear();
        }

        $symfony = $this->getModule('Symfony');
        $symfony->kernel->shutdown();

        $symfonyContainer = null;
        $this->container = null;
        $this->em = null;
        $this->app = null;

        if ($Connection instanceof \SymfonyMysqlConnection) {
            $Connection->Close();
        }

        $symfony->_initialize();
    }

    /**
     * @return Application
     */
    protected function getApp()
    {
        if (!$this->app) {
            $this->app = new Application($this->container->get('kernel'));
        }

        return $this->app;
    }

    protected function mockServiceWithBuilder(string $id): MockObject
    {
        $mock = $this->getMockBuilder($this->getModule('SymfonyExtras')->getServiceClass($id))->disableOriginalConstructor()->getMock();
        $this->mockService($id, $mock);

        return $mock;
    }

    protected function mockService(string $id, object $mock): void
    {
        /** @var SymfonyTestHelper $symfonyHelper */
        $symfonyHelper = $this->getModule('SymfonyTestHelper');
        $symfonyHelper->mockService($id, $mock);
    }
}
