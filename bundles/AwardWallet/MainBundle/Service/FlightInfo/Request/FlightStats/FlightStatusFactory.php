<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request\FlightStats;

use AwardWallet\MainBundle\Service\FlightInfo\Engine\CacherInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\LockableEngine;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\Exception;
use AwardWallet\MainBundle\Service\FlightInfo\Request\CommonRequestFactory;
use AwardWallet\MainBundle\Service\FlightInfo\Request\FlightStats\FlightStatus\FlightStatusRequest;
use AwardWallet\MainBundle\Service\FlightInfo\Request\RequestFactoryInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Request\RequestInterface;

class FlightStatusFactory extends CommonRequestFactory implements RequestFactoryInterface
{
    protected $api = 'https://api.flightstats.com/flex/';

    protected $app_id;

    protected $app_key;

    protected $namespace = '\\AwardWallet\\MainBundle\\Service\\FlightInfo\\Request\\FlightStats\\FlightStatus';

    protected $classMap = [
        'flight_status' => 'FlightStatusRequest',
        //        'airport_status' => '',
        //        'route_status' => '',
        //        'flight_track' => '',
        //        'airport_track' => '',
        //        'flights_near' => ''
    ];

    public function __construct(LockableEngine $engine, CacherInterface $cacher, $app_id, $app_key)
    {
        $this->engine = $engine;
        $this->cacher = $cacher;
        $this->app_id = $app_id;
        $this->app_key = $app_key;
    }

    /**
     * @param string $class
     * @return RequestInterface
     * @throws Exception
     */
    public function create($class)
    {
        /** @var FlightStatusRequest $request */
        $request = parent::create($class);
        $request->setConfig([
            'api' => $this->api,
            'app_id' => $this->app_id,
            'app_key' => $this->app_key,
        ]);

        return $request;
    }
}
