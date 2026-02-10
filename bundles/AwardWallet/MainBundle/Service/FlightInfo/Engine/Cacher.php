<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

use AwardWallet\MainBundle\Entity\FlightInfoLog;
use AwardWallet\MainBundle\Entity\Repositories\FlightInfoLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class Cacher implements CacherInterface
{
    protected EntityManagerInterface $em;

    protected FlightInfoLogRepository $cacheRep;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->cacheRep = $em->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoLog::class);
    }

    /**
     * @return CacheStorageInterface|false
     */
    public function get(HttpRequest $request)
    {
        $service = $request->getService();

        if ($service) {
            $key = md5($request->getDescription());
            $items = $this->cacheRep->findBy(['Service' => $service, 'RequestHash' => $key, 'State' => FlightInfoLog::STATE_OK]);

            foreach ($items as $cache) {
                if (!$cache->isExpired()) {
                    return $cache;
                }
            }
        }

        return false;
    }

    /**
     * @return CacheStorageInterface[]|false
     */
    public function getAll(HttpRequest $request)
    {
        $service = $request->getService();

        if ($service) {
            $ret = [];
            $key = md5($request->getDescription());
            $items = $this->cacheRep->findBy(['Service' => $service, 'RequestHash' => $key, 'State' => FlightInfoLog::STATE_OK]);

            foreach ($items as $cache) {
                if (!$cache->isExpired()) {
                    $ret[] = $cache;
                }
            }

            return !empty($ret) ? $ret : false;
        }

        return false;
    }

    /**
     * @return CacheStorageInterface|false
     */
    public function cache(HttpRequest $request, HttpResponse $response)
    {
        $service = $request->getService();

        if ($service) {
            $key = $request->getDescription();
            $cache = new FlightInfoLog();
            $cache->setService($service);
            $cache->setRequest($key);
            $cache->setState(FlightInfoLog::STATE_NEW);
            $cache->setHttpResponse($response);
            $this->em->persist($cache);
            $this->em->flush($cache);

            return $cache;
        }

        return false;
    }

    /**
     * @param CacheStorageInterface $cache
     * @param int $state
     * @return CacheStorageInterface
     */
    public function setState($cache, $state)
    {
        $cache->setState($state);
        $this->em->flush($cache);

        return $cache;
    }

    /**
     * @param CacheStorageInterface $cache
     * @param \DateTime|string|null $date
     * @return CacheStorageInterface
     */
    public function setExpire($cache, $date)
    {
        if ($date instanceof \DateTime) {
            $cache->setExpireDate($date);
        } elseif (!empty($date) && is_string($date)) {
            $interval = $date;
            $date = clone $cache->getCreateDate();
            $date->add(\DateInterval::createFromDateString($interval));
            $cache->setExpireDate($date);
        } else {
            $cache->setExpireDate($date);
        }
        $this->em->flush($cache);

        return $cache;
    }
}
