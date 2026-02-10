<?php

namespace AwardWallet\MainBundle\Service\FlightInfo;

use AwardWallet\MainBundle\Entity\FlightInfo as FlightInfoEntity;
use AwardWallet\MainBundle\Entity\FlightInfoConfig;
use AwardWallet\MainBundle\Entity\FlightInfoLog;
use AwardWallet\MainBundle\Entity\Repositories\AircraftRepository;
use AwardWallet\MainBundle\Entity\Repositories\FlightInfoLogRepository;
use AwardWallet\MainBundle\Entity\Repositories\FlightInfoRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\Paginator\QueryHelper;
use AwardWallet\MainBundle\Service\AirlineNameResolver;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\ErrorException;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\Exception;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\NotExistsException;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\NotFoundException;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\RequestException;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\ResponseException;
use AwardWallet\MainBundle\Service\FlightInfo\Request\FlightInfoRequestInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Request\MultiRequestInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Request\RequestInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Request\SubscribeRequestInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Response\FlightInfoResponseInterface;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FlightInfo
{
    public const NOT_FOUND_ERRORS_LIMIT = 3; // empty response
    public const INTERNAL_ERRORS_LIMIT = 5; // broken worker
    public const API_ERRORS_LIMIT = 2; // broken api

    public const CONFIG_CACHE_KEY = 'flight_info_config';

    /**
     * @var array
     */
    private $services;

    /**
     * @var ConfigRule[]
     */
    private $config;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var AirlineNameResolver
     */
    private $resolver;

    /**
     * @var FlightInfoRepository
     */
    private $flightInfoRep;

    /**
     * @var ProviderRepository
     */
    private $providerRep;

    /**
     * @var FlightInfoLogRepository
     */
    private $logRep;

    /**
     * @var FlightInfoEntity[]
     */
    private $cache = [];

    /**
     * @var Router
     */
    private $router;

    /**
     * @var \Memcached
     */
    private $memcache;
    /**
     * @var ItineraryTracker
     */
    private $tracker;
    /**
     * @var AircraftRepository
     */
    private $aircraftRepository;
    private DoctrineRetryHelper $doctrineRetryHelper;

    public function __construct($services, EntityManager $em, LoggerInterface $logger, ProducerInterface $producer, AirlineNameResolver $resolver, Router $router, \Memcached $memcache, ItineraryTracker $tracker, AircraftRepository $aircraftRepository, DoctrineRetryHelper $doctrineRetryHelper)
    {
        $this->services = $services;
        $this->em = $em;
        $this->logger = $logger;
        $this->producer = $producer;
        $this->resolver = $resolver;
        $this->router = $router;
        $this->memcache = $memcache;

        $this->flightInfoRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\FlightInfo::class);
        $this->providerRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $this->logRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoLog::class);
        //        $this->configRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoConfig::class);

        $this->loadConfig();
        $this->tracker = $tracker;
        $this->aircraftRepository = $aircraftRepository;
        $this->doctrineRetryHelper = $doctrineRetryHelper;
    }

    /**
     * search flight info by flight data.
     *
     * @param string $airline
     * @param string $flightNumber
     * @param string $depCode
     * @param string $arrCode
     * @return FlightInfoEntity
     */
    public function getFlightInfo($airline, $flightNumber, \DateTime $flightDate, $depCode, $arrCode)
    {
        $airline = strtoupper($airline);
        $flightNumber = preg_replace('/[^\d]/', '', $flightNumber);
        $flightNumber = ltrim($flightNumber, '0');
        $flightDate = new \DateTime($flightDate->format('Y-m-d'));
        $key = implode('|', [$airline, $flightNumber, $flightDate->format('Y-m-d'), $depCode, $arrCode]);

        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->flightInfoRep->findOrCreate($airline, $flightNumber, $flightDate, $depCode, $arrCode);
        }

        return $this->cache[$key];
    }

    /**
     * control memory leaks.
     */
    public function clearCache()
    {
        $this->cache = [];
    }

    /**
     * EM listener.
     */
    public function onClear()
    {
        $this->clearCache();
    }

    /**
     * @param array $tsData
     * @param array|null $tripData
     * @param int|null $providerId
     * @return FlightInfoEntity|null
     * @deprecated
     */
    public function bindToTSData($tsData, $tripData = null, $providerId = null)
    {
        // broken segment
        if (empty($tsData['FlightNumber'])) {
            return null;
        }

        if (empty($tsData['DepCode'])) {
            return null;
        }

        if (empty($tsData['ArrCode'])) {
            return null;
        }

        if (empty($tsData['DepDate'])) {
            return null;
        }

        if ($tsData['FlightNumber'] == 'n/a') {
            return null;
        }

        // deleted segment
        if (isset($tsData['Hidden']) && $tsData['Hidden']) {
            return null;
        }

        if (!empty($tripData) && is_array($tripData)) {
            // deleted segment
            if (isset($tripData['Hidden']) && $tripData['Hidden']) {
                return null;
            }

            // cancelled segment
            if (isset($tripData['Cancelled']) && $tripData['Cancelled']) {
                return null;
            }

            // non-flight segment
            if (isset($tripData['TripCategory']) && $tripData['TripCategory'] != TRIP_CATEGORY_AIR) {
                return null;
            }
        }

        // broken segment
        try {
            if (preg_match('/^\d+$/', $tsData['DepDate'])) { // timestamp
                $depDate = new \DateTime('@' . $tsData['DepDate']);
            } else { // mysql datetime
                $depDate = new \DateTime($tsData['DepDate']);
            }
        } catch (\Exception $e) {
            return null;
        }

        [$airlineCode, $flightNumber] = $this->extractFlightCode($tsData['FlightNumber']);

        if ($airlineCode) { // normalize to known iata
            $airlineCode = $this->resolver->resolveToIATACode($airlineCode);
        }

        if (empty($airlineCode)) {
            if (isset($tsData['AirlineName'])) {
                $airlineCode = $this->resolver->resolveToIATACode($tsData['AirlineName']);
            }

            if (empty($airlineCode) && !empty($providerId)) {
                $provider = $this->providerRep->find($providerId);

                if (!empty($provider) && $provider->getKind() == PROVIDER_KIND_AIRLINE) {
                    $airlineCode = $provider->getIATACode();
                }
            }

            if (empty($airlineCode) && !empty($tripData) && is_array($tripData)) {
                if (isset($tripData['ProviderID'])) {
                    $provider = $this->providerRep->find($tripData['ProviderID']);

                    if (!empty($provider) && $provider->getKind() == PROVIDER_KIND_AIRLINE) {
                        $airlineCode = $provider->getIATACode();
                    }
                }

                if (empty($airlineCode) && isset($tripData['AirlineName'])) {
                    $airlineCode = $this->resolver->resolveToIATACode($tripData['AirlineName']);
                }
            }
        }

        $flightInfo = $this->getFlightInfo($airlineCode, $flightNumber, $depDate, $tsData['DepCode'], $tsData['ArrCode']);

        if (empty($airlineCode)) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_AIRLINE);
        } elseif (empty($flightNumber) || strlen($flightNumber) > 4) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_NUMBER);
        }

        return $flightInfo;
    }

    public function applyToTripsegmentAndCopies(Tripsegment $originalSegment)
    {
        $flightInfo = $this->bindToTripsegment($originalSegment);

        if ($flightInfo) {
            if (
                $originalSegment->getFlightinfoid() === null
                || $originalSegment->getFlightinfoid()->getFlightInfoID() !== $flightInfo->getFlightInfoID()
            ) {
                $originalSegment->setFlightinfoid($flightInfo);
                $this->em->flush($originalSegment);
                $this->schedule($flightInfo);

                if ($flightInfo->isLoaded()) {
                    $oldProperties = [];

                    foreach ($flightInfo->getSegments() as $segment) {
                        $key = 'T.' . $segment->getTripid()->getId();

                        if (!array_key_exists($key, $oldProperties)) {
                            $oldProperties[$key] = ['changes' => $this->tracker->getProperties($key), 'userId' => $segment->getTripid()->getUser()->getUserid()];
                        }
                    }
                    $this->updateTripSegments($flightInfo);

                    foreach ($oldProperties as $key => $data) {
                        $this->tracker->recordChanges($data['changes'], $key, $data['userId'], true);
                    }
                }
            }
        } else {
            if ($originalSegment->getFlightinfoid() !== null) {
                $originalSegment->setFlightinfoid(null);
                $this->em->flush($originalSegment);
            }
        }
    }

    public function applyToTripsegment(Tripsegment $segment)
    {
        $flightInfo = $this->bindToTripsegment($segment);

        if ($flightInfo) {
            $segment->setFlightinfoid($flightInfo);

            if (!$flightInfo->isLoaded() && $flightInfo->isRequestable()) {
                $this->schedule($flightInfo);
            }

            if ($flightInfo->isLoaded() && $flightInfo->getState() === FlightInfoEntity::STATE_CHECKED) {
                $data = $flightInfo->convertToTripsegment();
                $this->applyDataToSegment($data, $segment);
            }
        } else {
            $segment->setFlightinfoid(null);
        }
    }

    /**
     * @return FlightInfoEntity|null
     */
    public function bindToTripsegment(Tripsegment $tripSegment)
    {
        $trip = $tripSegment->getTripid();

        // broken segment
        if (empty($tripSegment->getFlightNumber())) {
            return null;
        }

        if (empty($tripSegment->getDepcode())) {
            return null;
        }

        if (empty($tripSegment->getArrcode())) {
            return null;
        }

        if (empty($tripSegment->getDepdate())) {
            return null;
        }

        if ($tripSegment->getFlightNumber() == 'n/a') {
            return null;
        }

        // deleted segment
        if ($tripSegment->getHidden()) {
            return null;
        }

        if ($trip->getHidden()) {
            return null;
        }

        // cancelled segment
        if ($trip->getCancelled()) {
            return null;
        }

        // non-flight segment
        if ($trip->getCategory() != TRIP_CATEGORY_AIR) {
            return null;
        }

        [$airlineCode, $flightNumber] = $this->extractFlightCode($tripSegment->getFlightNumber());

        if ($airlineCode) { // normalize to known iata
            $airlineCode = $this->resolver->resolveToIATACode($airlineCode);
        }

        if (empty($airlineCode)) {
            $airlineCode = $this->resolver->resolveToIATACode($tripSegment->getAirlineName());

            if (empty($airlineCode)) {
                if (!empty($trip->getProvider())) {
                    $provider = $trip->getProvider();

                    if (!empty($provider) && $provider->getKind() == PROVIDER_KIND_AIRLINE) {
                        $airlineCode = $provider->getIATACode();
                    }
                }

                if (empty($airlineCode)) {
                    $airlineCode = $this->resolver->resolveToIATACode($trip->getAirlineName());
                }
            }
        }

        $flightInfo = $this->getFlightInfo($airlineCode, $flightNumber, $tripSegment->getDepdate(), $tripSegment->getDepcode(), $tripSegment->getArrcode());

        if (empty($airlineCode)) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_AIRLINE);
        } elseif (empty($flightNumber) || strlen($flightNumber) > 4) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_NUMBER);
        }

        return $flightInfo;
    }

    /**
     * @return bool
     */
    public function schedule(FlightInfoEntity $flightInfo)
    {
        if (!$flightInfo->isRequestable()) {
            return false;
        }

        if (empty($this->config)) {
            return false;
        }

        // update schedule in FlightInfo
        $scheduled = 0;

        foreach (array_keys($this->config) as $ruleName) {
            $configRule = $this->config[$ruleName];

            if (!$configRule->isSchedule()) {
                continue;
            }

            if ($flightInfo->scheduleTask($ruleName)) {
                $scheduled++;
            }
        }

        $scheduledTasks = $flightInfo->getScheduledTasks();
        /** @var ConfigRule[] $rulesToPublish */
        $rulesToPublish = [];

        // calculate tasks to publishing, depends of current FI state
        foreach ($scheduledTasks as $ruleName) {
            if (!array_key_exists($ruleName, $this->config)) {
                continue;
            }
            $configRule = $this->config[$ruleName];

            if ($configRule->isDebug()) {
                $rulesToPublish[] = $configRule;
            } elseif ($configRule->isCheckRule() && $flightInfo->canCheck()) {
                $rulesToPublish[] = $configRule;
            } elseif ($configRule->isSubscribeRule() && $flightInfo->canSubscribe()) {
                $rulesToPublish[] = $configRule;
            } elseif ($configRule->isUpdateRule() && $flightInfo->canUpdate()) {
                $rulesToPublish[] = $configRule;
            }
        }

        //  publish non-published tasks
        $messages = [];

        foreach ($rulesToPublish as $configRule) {
            $nextUpdateDate = $configRule->getScheduleTime($flightInfo->getFlightDate(), $flightInfo->getDepartureUTCDate(), $flightInfo->getArrivalUTCDate());

            if ($nextUpdateDate) {
                $nextUpdate = $nextUpdateDate->getTimestamp() - time();

                if ($nextUpdate < 0) {
                    $nextUpdate = 0;
                }
                $task = [
                    'id' => $flightInfo->getFlightInfoID(),
                    'rule' => $configRule->getName(),
                    'create' => (new \DateTime())->format('c'),
                    'schedule' => $nextUpdateDate->format('c'),
                ];
                $messages[] = ['task' => $task, 'headers' => ['application_headers' => ['x-delay' => ['I', $nextUpdate * 1000 + 1]]]];
                $flightInfo->publishTask($configRule->getName(), $nextUpdateDate);
            }
        }

        if (count($messages) > 0 || $scheduled) {
            $this->doctrineRetryHelper->execute(function () {
                $this->em->flush();
            });
        }

        foreach ($messages as $message) {
            $this->producer->publish(@serialize($message['task']), '', $message['headers']);
        }

        if (count($messages)) {
            return true;
        }

        return false;
    }

    /**
     * @return FlightInfoEntity[]|bool
     * @throws DBALException
     */
    public function update(FlightInfoEntity $flightInfo, $ruleName)
    {
        $publishedTasks = $flightInfo->getPublishedTasks();

        if (!in_array($ruleName, $publishedTasks)) {
            return false;
        } // skip doubled rabbitmq queue items

        if (!$flightInfo->isFilled()) {
            $flightInfo->finalizeTask($ruleName, 'broken request');
            $this->em->flush();

            return false;
        }

        if (!array_key_exists($ruleName, $this->config)) {
            $flightInfo->finalizeTask($ruleName, 'deleted task');
            $this->em->flush();

            return false;
        }

        $configRule = $this->config[$ruleName];

        if (!$configRule->isEnable()) {
            $flightInfo->finalizeTask($ruleName, 'disabled task');
            $this->em->flush();

            return false;
        }

        if (!array_key_exists($configRule->getService(), $this->services)) {
            $flightInfo->finalizeTask($ruleName, 'unknown service');
            $this->em->flush();

            return false;
        }

        $factory = $this->services[$configRule->getService()];
        /** @var FlightInfoRequestInterface|SubscribeRequestInterface $request */
        $request = $factory['service']->create($factory['request']);

        if (!($request instanceof FlightInfoRequestInterface)) {
            $flightInfo->finalizeTask($ruleName, 'broken service');
            $this->em->flush();

            return false;
        }

        if ($configRule->isSubscribeRule() && !($request instanceof SubscribeRequestInterface)) {
            $flightInfo->finalizeTask($ruleName, 'broken subscribe service');
            $this->em->flush();

            return false;
        }

        if (!$configRule->isDebug()) { // debug rules skip all checks
            if ($configRule->isCheckRule() && !$flightInfo->canCheck()) {
                $flightInfo->finalizeTask($ruleName, 'cant check');
                $this->em->flush();

                return false;
            }

            if ($configRule->isSubscribeRule() && !$flightInfo->canSubscribe()) {
                $flightInfo->finalizeTask($ruleName, 'cant subscribe');
                $this->em->flush();

                return false;
            }

            if ($configRule->isUpdateRule() && !$flightInfo->canUpdate()) {
                $flightInfo->finalizeTask($ruleName, 'cant update');
                $this->em->flush();

                return false;
            }

            $segments = $flightInfo->getSegments();

            if (count($segments) == 0) {
                $flightInfo->finalizeTask($ruleName, 'no segments');
                $this->em->flush();

                return false;
            }

            $awPlus = FlightInfoConfig::AWPLUS_REGULAR;

            foreach ($segments as $segment) {
                if ($segment->getUser()->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS) {
                    $awPlus = FlightInfoConfig::AWPLUS_PLUS;

                    break;
                }
            }

            if ($configRule->getAwPlus() != FlightInfoConfig::AWPLUS_ALL && $configRule->getAwPlus() != $awPlus) {
                $flightInfo->finalizeTask($ruleName, 'skipped by awplus');
                $this->em->flush();

                return false;
            }

            $region = FlightInfoConfig::REGION_INTERNATIONAL;
            /** @var Tripsegment $segment */
            $segment = $segments->first();

            if (!empty($segment->getDepgeotagid()) && !empty($segment->getArrgeotagid())
                && !empty($segment->getDepgeotagid()->getCountryCode()) && $segment->getDepgeotagid()->getCountryCode() == $segment->getArrgeotagid()->getCountryCode()
            ) {
                $region = FlightInfoConfig::REGION_DOMESTIC;
            }

            if ($configRule->getRegion() != FlightInfoConfig::REGION_ALL && $configRule->getRegion() != $region) {
                $flightInfo->finalizeTask($ruleName, 'skipped by region');
                $this->em->flush();

                return false;
            }
        }

        if ($configRule->isCheckRule()) {
            $requestIncFunc = function () use ($flightInfo) { $flightInfo->incChecks(); };
        } elseif ($configRule->isSubscribeRule()) {
            $requestIncFunc = function () use ($flightInfo) { $flightInfo->incSubscribes(); };
        } else {
            $requestIncFunc = function () use ($flightInfo) { $flightInfo->incUpdates(); };
        }

        $ret = [];

        $request
            ->carrier($flightInfo->getAirline())
            ->flight($flightInfo->getFlightNumber())
            ->date($flightInfo->getFlightDate())
            ->departure($flightInfo->getDepCode())
            ->arrival($flightInfo->getArrCode());

        if ($configRule->isSubscribeRule()) {
            $request->subscribe($this->router->generate($factory['subscribe'], ['id' => $flightInfo->getFlightInfoID()], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        $response = null;

        try {
            /** @var FlightInfoResponseInterface $response */
            $response = $request->fetch();
        } catch (RequestException $e) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_OTHER);
            $flightInfo->finalizeTask($ruleName, 'request error');
        } catch (NotFoundException $e) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_NOT_FOUND);
            $flightInfo->finalizeTask($ruleName, 'not found');
            $requestIncFunc();
            $flightInfo->incErrors();
        } catch (NotExistsException $e) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_NOT_EXISTS);
            $flightInfo->finalizeTask($ruleName, 'not exists');
            $requestIncFunc();
            $flightInfo->incErrors();
        } catch (ErrorException $e) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_OTHER);
            $flightInfo->setErrorMessage($e->getError());
            $this->logger->error("FlightInfo error: " . $e->getError());
            $flightInfo->finalizeTask($ruleName, $e->getError());
            $requestIncFunc();
            $flightInfo->incErrors();
        } catch (ResponseException $e) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_OTHER);
            $flightInfo->finalizeTask($ruleName, 'response error');
            $requestIncFunc();
            $flightInfo->incErrors();
        } catch (Exception $e) {
            $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
            $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_OTHER);
            $flightInfo->finalizeTask($ruleName, 'general error');
        }

        if (!empty($response)) {
            $requestIncFunc();
            $service = $request->getHttpRequest()->getService();

            foreach ($response->getFlightIndex() as $i => $status) {
                // todo some carriers have empty iata code
                if (!$status->isIATA() || !$status->isLocal()) {
                    continue;
                }
                $updatedFlightInfo = $this->getFlightInfo(
                    $status->getCarrierIATACode(),
                    $status->getFlightNumber(),
                    $status->getDepartureLocalDate(),
                    $status->getDepartureIATACode(),
                    $status->getArrivalIATACode()
                );
                $updatedFlightInfo->setPropertiesFromService($service, $status->getInfo(), $configRule->getIgnoreFields());
                $ret[] = $updatedFlightInfo;

                $updatedFlightInfo->scheduleTask($ruleName);
                $updatedFlightInfo->publishTask($ruleName);
                $updatedFlightInfo->finalizeTask($ruleName, 'ok');

                if ($configRule->isCheckRule() && $flightInfo->canCheck()) {
                    $updatedFlightInfo->setState(FlightInfoEntity::STATE_CHECKED);
                } elseif ($configRule->isUpdateRule() && $flightInfo->getState() != FlightInfoEntity::STATE_CHECKED) {
                    $updatedFlightInfo->setState(FlightInfoEntity::STATE_CHECKED);
                }

                if ($updatedFlightInfo->getArrivalUTCDate() && $updatedFlightInfo->getArrivalUTCDate()->getTimestamp() <= time()) {
                    $updatedFlightInfo->setState(FlightInfoEntity::STATE_DONE);
                    $updatedFlightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ARRIVE);
                } elseif ($updatedFlightInfo->getDepartureUTCDate() && $updatedFlightInfo->getDepartureUTCDate()->getTimestamp() <= time()) {
                    $updatedFlightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_DEPART);
                } elseif ($updatedFlightInfo->getDepartureUTCDate() && $updatedFlightInfo->getDepartureUTCDate()->getTimestamp() > time()) {
                    $updatedFlightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_SCHEDULE);
                }
            }

            // requested FI still not checked
            if ($configRule->isCheckRule() && $flightInfo->canCheck()) {
                $flightInfo->setState(FlightInfoEntity::STATE_ERROR);
                $flightInfo->setFlightState(FlightInfoEntity::FLIGHTSTATE_ERROR_NOT_EXISTS);
                $flightInfo->finalizeTask($ruleName, 'not exists');
            }
        }

        $this->doctrineRetryHelper->execute(function () {
            $this->em->flush();
        });

        return $ret ? array_values($ret) : false;
    }

    /**
     * update existing flight info.
     */
    public function updateTripSegments(FlightInfoEntity $flightInfo)
    {
        if (!$flightInfo->isLoaded()) {
            return;
        }

        $segmentData = $flightInfo->convertToTripsegment();

        if (!empty($segmentData)) {
            $segments = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class)->findBy(['flightinfoid' => $flightInfo]);

            foreach ($segments as $segment) {
                $changed = false;
                $trip = $segment->getTripid();
                $segment->setTripAlertsUpdateDate(new \DateTime());

                if ($segment->getHidden() || $trip->getHidden() || $trip->getCancelled()) {
                    $changed = true; // disable further updates
                    $segment->setFlightinfoid(null);
                } else {
                    $changed = $changed || $this->applyDataToSegment($segmentData, $segment);
                }

                if ($changed) {
                    //                    $segment->setChangeDate(new \DateTime());
                    $this->em->flush($segment);
                }
            }
        }
    }

    public function bindAll()
    {
        $per_iteration = 100;

        $this->logger->warning('FlightInfo BindAll: start');

        $q = $this->em->createQueryBuilder();
        $q->select('ts')->from(Tripsegment::class, 'ts')
            ->join('ts.tripid', 't')
            ->where('ts.flightinfoid is null')
            ->andWhere('ts.depdate >= :depdate_start')
            ->andWhere('ts.depdate < :depdate_end')
            ->andWhere('ts.depcode <> :empty_string')
            ->andWhere('ts.arrcode <> :empty_string')
            ->andWhere('ts.flightNumber <> :empty_string')
            ->andWhere('ts.flightNumber <> :na_string')
            ->andWhere('t.category = :category')
            ->andWhere('t.hidden <> 1')
            ->andWhere('ts.hidden <> 1')
            ->andWhere('t.cancelled <> 1')
            ->andWhere('ts.tripsegmentid > :last_segment')
            ->orderBy('ts.tripsegmentid');
        $q->setParameter('depdate_start', (new \DateTime('-2 days'))->format('Y-m-d'));
        $q->setParameter('depdate_end', (new \DateTime('+3 days'))->format('Y-m-d'));
        $q->setParameter('empty_string', '');
        $q->setParameter('na_string', 'n/a');
        $q->setParameter('category', TRIP_CATEGORY_AIR);
        $q->setMaxResults($per_iteration);
        $query = $q->getQuery();

        $updated = 0;
        $lastSegmentId = 0;

        do {
            $this->em->clear();
            $limitQuery = QueryHelper::cloneQuery($query);
            $limitQuery->setParameter('last_segment', $lastSegmentId);
            $result = $limitQuery->getResult();

            /** @var Tripsegment $segment */
            foreach ($result as $segment) {
                $updated++;
                $flightInfo = $this->bindToTripsegment($segment);
                $segment->setFlightinfoid($flightInfo);
                $lastSegmentId = $segment->getTripsegmentid();
            }
            $this->em->flush();
            $this->logger->warning('FlightInfo BindAll: process ' . $updated);
        } while (count($result) == $per_iteration);

        $this->logger->warning('FlightInfo BindAll: done');

        return $updated;
    }

    public function scheduleAll()
    {
        $per_iteration = 100;

        $this->logger->warning('FlightInfo ScheduleAll: start');

        $q = $this->em->createQueryBuilder();
        $q->select('i')->from(\AwardWallet\MainBundle\Entity\FlightInfo::class, 'i')
            ->where('i.FlightDate > :start_update')
            ->andWhere('i.FlightDate <= :end_update')
            ->andWhere($q->expr()->in('i.State', $this->getAllowedStates()));
        $q->setParameter('start_update', (new \DateTime('-2 days'))->format('Y-m-d'));
        $q->setParameter('end_update', (new \DateTime('+2 days'))->format('Y-m-d'));
        $q->setMaxResults($per_iteration);
        $query = $q->getQuery();

        $scheduled = $iteration = 0;

        do {
            $this->em->clear();
            $limitQuery = QueryHelper::cloneQuery($query);
            $limitQuery->setFirstResult($iteration * $per_iteration);
            $result = $limitQuery->getResult();

            /** @var FlightInfoEntity $flightInfo */
            foreach ($result as $flightInfo) {
                if ($this->schedule($flightInfo)) {
                    $scheduled++;
                }
            }
            $iteration++;
            $this->logger->warning('FlightInfo ScheduleAll: process ' . $scheduled);
        } while (count($result) == $per_iteration);

        $this->logger->warning('FlightInfo ScheduleAll: done');

        return $scheduled;
    }

    /**
     * @param string $number
     * @return string[]
     */
    public function extractFlightCode($number)
    {
        $number = trim($number);
        $number = strtoupper($number);

        if (preg_match('/[^A-Z0-9\s-]/', $number)) {
            return ['', ''];
        }

        if (strlen($number) >= 3) {
            $letter1 = $number[0];
            $letter2 = $number[1];

            if (strlen($number) > 3) {
                $letter3 = $number[2];
            } else {
                $letter3 = '';
            }

            if ((preg_match('/[A-Z]/', $letter1) && preg_match('/[0-9]/', $letter2)) || (preg_match('/[0-9]/', $letter1) && preg_match('/[A-Z]/', $letter2))) {
                $airlineCode = $letter1 . $letter2;
                $flightNumber = substr($number, 2);
            } elseif (preg_match('/[A-Z]/', $letter1) && preg_match('/[A-Z]/', $letter2)) {
                if ($letter3) {
                    if (preg_match('/[A-Z]/', $letter3)) {
                        $airlineCode = $letter1 . $letter2 . $letter3;
                        $flightNumber = substr($number, 3);
                    } else {
                        $airlineCode = $letter1 . $letter2;
                        $flightNumber = substr($number, 2);
                    }
                } else {
                    $airlineCode = $letter1 . $letter2;
                    $flightNumber = substr($number, 2);
                }
            } else {
                $airlineCode = '';
                $flightNumber = $number;
            }
        } else {
            $airlineCode = '';
            $flightNumber = $number;
        }
        $flightNumber = trim($flightNumber);
        $flightNumber = ltrim($flightNumber, '-');
        $flightNumber = trim($flightNumber);

        if (preg_match('/[^0-9]/', $flightNumber)) {
            return ['', ''];
        }
        $flightNumber = intval($flightNumber);

        if (empty($flightNumber)) {
            return ['', ''];
        }
        $flightNumber = '' . $flightNumber;

        return [$airlineCode, $flightNumber];
    }

    /**
     * @return ConfigRule[]
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return FlightInfoLog[]
     */
    public function getLogs(FlightInfoEntity $flightInfo)
    {
        $ret = [];

        foreach ($this->services as $key => $factory) {
            /** @var FlightInfoRequestInterface|SubscribeRequestInterface|MultiRequestInterface $request */
            $request = $factory['service']->create($factory['request']);

            if ($request instanceof FlightInfoRequestInterface) {
                $request->carrier($flightInfo->getAirline())
                    ->flight($flightInfo->getFlightNumber())
                    ->date($flightInfo->getFlightDate())
                    ->departure($flightInfo->getDepCode())
                    ->arrival($flightInfo->getArrCode());

                if ($request instanceof SubscribeRequestInterface) {
                    $request->subscribe($this->router->generate($factory['subscribe'], ['id' => $flightInfo->getFlightInfoID()], UrlGeneratorInterface::ABSOLUTE_URL));
                }

                if ($request instanceof MultiRequestInterface) {
                    $httpRequests = $request->getHttpRequestCollection();
                } else {
                    $httpRequests = [$request->getHttpRequest()];
                }

                foreach ($httpRequests as $httpRequest) {
                    $service = $httpRequest->getService();

                    if ($service) {
                        $key = $httpRequest->getDescription();
                        $logs = $this->logRep->findBy(['Service' => $service, 'Request' => $key]);

                        foreach ($logs as $log) {
                            $ret[] = $log;
                        }
                        $logs = $this->logRep->findBy(['Service' => $service, 'Request' => $flightInfo->getFlightInfoID()]);

                        foreach ($logs as $log) {
                            $ret[] = $log;
                        }
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function getServices()
    {
        $ret = [];

        foreach ($this->services as $key => $factory) {
            /** @var RequestInterface $request */
            $request = $factory['service']->create($factory['request']);

            if ($request instanceof FlightInfoRequestInterface) {
                $ret[] = $key;
            }
        }

        return $ret;
    }

    private function applyDataToSegment(array $segmentData, Tripsegment $segment): bool
    {
        $changed = false;

        if ($segmentData['DepDate']->getTimestamp() !== $segment->getDepartureDate()->getTimestamp()) {
            $changed = true;
            $this->logger->info("FlightInfo: updated DepDate from  {$segment->getDepartureDate()->format("Y-m-d H:i:s")} to {$segmentData['DepDate']->format("Y-m-d H:i:s")}", ["TripSegmentID" => $segment->getId()]);
            $segment->setDepartureDate($segmentData['DepDate']);
        }

        if ($segmentData['ArrDate']->getTimestamp() !== $segment->getArrivalDate()->getTimestamp()) {
            $changed = true;
            $this->logger->info("FlightInfo: updated ArrDate from  {$segment->getArrivalDate()->format("Y-m-d H:i:s")} to {$segmentData['ArrDate']->format("Y-m-d H:i:s")}", ["TripSegmentID" => $segment->getId()]);
            $segment->setArrivalDate($segmentData['ArrDate']);
        }

        foreach ($segmentData as $key => $value) {
            switch ($key) {
                case "DepDate":
                case "ArrDate":
                    break;

                case "Aircraft":
                    // @TODO V2: need aircraft IATA, record it in FlightStatusResponse
                    $aircraft = $this->aircraftRepository->findOneBy(["Name" => $value]);

                    if ($aircraft !== null) {
                        $this->logger->info("FlightInfo: updated Aircraft to {$aircraft->getName()}", ["TripSegmentID" => $segment->getId()]);
                        $segment->setAircraft($aircraft);
                    }

                    break;

                case "DepartureTerminal":
                    $this->logger->info("FlightInfo: updated DepartureTerminal from  {$segment->getDepartureTerminal()} to {$value}", ["TripSegmentID" => $segment->getId()]);
                    $segment->setDepartureTerminal($value);

                    break;

                case "ArrivalTerminal":
                    $this->logger->info("FlightInfo: updated ArrivalTerminal from  {$segment->getArrivalTerminal()} to {$value}", ["TripSegmentID" => $segment->getId()]);
                    $segment->setArrivalTerminal($value);

                    break;

                case "Gate":
                case "DepartureGate":
                    $this->logger->info("FlightInfo: updated DepartureGate from  {$segment->getDepartureGate()} to {$value}", ["TripSegmentID" => $segment->getId()]);
                    $segment->setDepartureGate($value);

                    break;

                case "ArrivalGate":
                    $this->logger->info("FlightInfo: updated ArrivalGate from  {$segment->getArrivalGate()} to {$value}", ["TripSegmentID" => $segment->getId()]);
                    $segment->setArrivalGate($value);

                    break;

                case "BaggageClaim":
                    $this->logger->info("FlightInfo: updated BaggageClaim from  {$segment->getBaggageClaim()} to {$value}", ["TripSegmentID" => $segment->getId()]);
                    $segment->setBaggageClaim($value);

                    break;

                default:
                    $this->logger->critical("unknown property from FlightInfo: $key");
            }
        }

        return $changed;
    }

    /**
     * @return array
     */
    private function getAllowedStates()
    {
        return [
            FlightInfoEntity::STATE_NEW,
            FlightInfoEntity::STATE_CHECKED,
            FlightInfoEntity::STATE_DONE,
        ];
    }

    private function loadConfig()
    {
        $configRules = $this->memcache->get(self::CONFIG_CACHE_KEY);

        if (empty($configRules)) {
            $connection = $this->em->getConnection();
            $q = $connection->executeQuery("SELECT * FROM FlightInfoConfig");
            $configRules = $q->fetchAll(\PDO::FETCH_ASSOC);
            $this->memcache->set(self::CONFIG_CACHE_KEY, $configRules, 60 * 60);
        }

        foreach ($configRules as $rule) {
            if (!array_key_exists($rule['Service'], $this->services)) {
                continue;
            }
            $this->config[$rule['Name']] = new ConfigRule($rule);
        }
    }
}
