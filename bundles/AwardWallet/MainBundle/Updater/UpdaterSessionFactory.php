<?php

namespace AwardWallet\MainBundle\Updater;

use Psr\Container\ContainerInterface;

class UpdaterSessionFactory
{
    public const TYPE_MOBILE = 'mobile';
    public const TYPE_DESKTOP = 'desktop';
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createMobileSession(): UpdaterSession
    {
        return $this->container->get('aw.updater_session.mobile');
    }

    public function createDesktopSession(): UpdaterSession
    {
        return $this->container->get('aw.updater_session.desktop');
    }

    public function createSessionByType(string $type): UpdaterSession
    {
        switch ($type) {
            case self::TYPE_MOBILE: return $this->createMobileSession();

            case self::TYPE_DESKTOP: return $this->createDesktopSession();

            default: throw new \InvalidArgumentException("Unknown updater session type '{$type}'");
        }
    }
}
