<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Doctrine;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use JMS\Serializer\SerializerInterface;
use Webit\DoctrineJmsJson\DBAL\JmsJsonType;

class DoctrineJsonTypeConfigurator
{
    private static $initialized = false;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function configure(ConnectionFactory $connectionFactory)
    {
        if (!self::$initialized) {
            JmsJsonType::initialize($this->serializer, $this->serializer, new SerializerTypeResolver());
            self::$initialized = true;
        }
    }
}
