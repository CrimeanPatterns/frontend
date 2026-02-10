<?php

namespace AwardWallet\MainBundle\Tests;

trait GeneralHelper
{
    /**
     * @var \Doctrine\Persistence\ManagerRegistry
     */
    protected static $doctrine;
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected static $connection;
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected static $container;

    protected static function setupBeforeClassGeneralHelper(\Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        self::$doctrine = $container->get('doctrine');
        self::$connection = self::$doctrine->getConnection();
        self::$container = $container;
    }
}
