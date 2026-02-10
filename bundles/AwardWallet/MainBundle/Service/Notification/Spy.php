<?php

namespace AwardWallet\MainBundle\Service\Notification;

use AwardWallet\Common\Memcached\Item;
use AwardWallet\Common\Memcached\Util;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Parameter;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

class Spy
{
    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    private Util $memcachedUtil;

    private EntityRepository $deviceRep;

    private ParameterRepository $parameterRepository;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        Util $memcachedUtil
    ) {
        $this->logger = $logger;
        $this->entityManager = $em;
        $this->memcachedUtil = $memcachedUtil;
        $this->deviceRep = $em->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class);
        $this->parameterRepository = $em->getRepository(\AwardWallet\MainBundle\Entity\Parameter::class);
    }

    /**
     * @param int $contentType
     * @return MobileDevice[]
     */
    public function getPushCopyDevices($contentType, $useCache = false)
    {
        $getter = function () use ($contentType) {
            /** @var Parameter $param */
            $param = $this->parameterRepository->findOneBy(['name' => 'push_copy_' . $contentType]);

            if (!empty($param) && !empty($param->getVal())) {
                $result = explode(",", $param->getVal());
            } else {
                $result = [];
            }
            $result = $this->deviceRep->findBy(['mobileDeviceId' => $result]);

            return new Item($result, 60);
        };

        if ($useCache) {
            return $this->memcachedUtil->getThrough("push_copy_devices_" . $contentType, $getter);
        } else {
            return $getter()->data;
        }
    }

    /**
     * @param int $contentType
     * @param MobileDevice[] $devices
     */
    public function setPushCopyDevices($contentType, array $devices)
    {
        $param = $this->parameterRepository->findOneBy(['name' => 'push_copy_' . $contentType]);

        if (empty($param)) {
            $param = new Parameter();
            $param->setName('push_copy_' . $contentType);
        }
        $param->setVal(implode(",", array_map(function (MobileDevice $device) { return $device->getMobileDeviceId(); }, $devices)));
        $this->logger->warning("set push copies", ["contentType" => $contentType, "devices" => $param->getVal()]);
        $this->entityManager->persist($param);
        $this->entityManager->flush();
    }
}
