<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request\FlightStats\FlightStatus;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\CacherInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\HttpRequest;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\HttpResponse;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\ErrorException;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\NotFoundException;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\RequestException;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\ResponseException;
use AwardWallet\MainBundle\Service\FlightInfo\Request\CachedRequestInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Request\CommonRequest;
use AwardWallet\MainBundle\Service\FlightInfo\Request\FlightInfoRequestInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Request\ScheduleRequestInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Response\FlightInfoResponseInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Response\FlightStats\FlightStatus\FlightStatusResponse;

/**
 * Class FlightStatusRequest.
 *
 * @see https://developer.flightstats.com/api-docs/flightstatus/v2/flight
 *
 * API returns the Flight Statuses for the given Carrier and Flight Number that departed/arrived on the given date.
 * Optionally, the departure/arrival airport may be specified.
 */
/**
 * @NoDI()
 */
class FlightStatusRequest extends CommonRequest implements FlightInfoRequestInterface, ScheduleRequestInterface, CachedRequestInterface
{
    public const UPDATE_INTERVALS = [
        // first update start between 24 and 48 hours
        60 * 60 * 24,   // 24h
        //        60 * 60 * 12,   // 12h
        60 * 60 * 6,    // 6h
        //        60 * 60 * 3,    // 3h
        60 * 60,        // 1h
        //        60 * 30,        // 30m
    ];

    protected $url = 'flightstatus/{protocol}/v2/{format}/flight/status/{carrier}/{flight}/{type}/{year}/{month}/{day}';

    protected $parameters = [
        'appId' => [
            'place' => 'request',
            'secure' => true,
        ],
        'appKey' => [
            'place' => 'request',
            'secure' => true,
        ],
        'protocol' => [
            'place' => 'url',
            'default' => 'rest',
        ],
        'format' => [
            'place' => 'url',
            'default' => 'json',
        ],
        'type' => [
            'place' => 'url',
            'default' => 'dep',
        ],
        'utc' => [
            'place' => 'request',
            'default' => false,
        ],
        'airport' => [
            'place' => 'request',
            'default' => null,
        ],
        'codeType' => [
            'place' => 'request',
            'default' => null,
        ],
    ];

    /** @var FlightInfoResponseInterface */
    protected $response;

    public function __construct()
    {
        // todo fix validator
        $this->values['format'] = 'json';
        $this->values['protocol'] = 'rest';
        $this->values['type'] = 'dep';
        $this->values['utc'] = false;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->host = $config['api'];
        $this->values['appId'] = $config['app_id'];
        $this->values['appKey'] = $config['app_key'];

        return $this;
    }

    /**
     * @return HttpRequest
     */
    public function getHttpRequest()
    {
        return (new HttpRequest())
            ->setUrl($this->getHttpRequestUrl())
            ->setDescription($this->getHttpRequestUrl(true))
            ->setService('flight_stats.flight_status');
    }

    /**
     * @param \DateTime|null $createDate
     * @return FlightStatusResponse
     * @throws ErrorException
     * @throws NotFoundException
     * @throws ResponseException
     */
    public function resolveHttpResponse(HttpResponse $httpResponse, $createDate = null)
    {
        $data = $httpResponse->getJSON();

        if (!is_array($data)) {
            $this->response = null;
            $this->setCacheState(CacherInterface::STATE_API_ERROR);

            throw new ResponseException('Unknown API Error');
        }

        if (array_key_exists('error', $data)) {
            $this->response = null;
            $this->setCacheState(CacherInterface::STATE_API_ERROR);

            throw (new ErrorException())->setError($data['error']['errorMessage']);
        }

        if (!array_key_exists('flightStatuses', $data) || empty($data['flightStatuses'])) {
            $this->response = null;
            $this->setCacheState(CacherInterface::STATE_API_ERROR);

            throw new NotFoundException('Not Found response');
        }

        $this->response = new FlightStatusResponse($data);

        if ($createDate) {
            $this->response->setCreateDate($createDate);
        }
        $this->setCacheState(CacherInterface::STATE_OK);
        $this->setCacheExpire('+60 minute');

        return $this->response;
    }

    /**
     * next update datetime, or false on dont update, or true on now.
     *
     * @param \DateTime $updateDate
     * @param \DateTime $depDate
     * @param \DateTime $arrDate
     * @return bool|\DateTime
     */
    public function getNextUpdate($updateDate, $depDate, $arrDate)
    {
        try {
            $requestDate = new \DateTime(implode('-', [$this->values['year'], $this->values['month'], $this->values['day']]));
        } catch (\Exception $e) {
            return false; // request date must set
        }

        if ($depDate->getTimestamp() >= $arrDate->getTimestamp()) {
            return false;
        } // error in parameters

        if ($depDate->format('Y-m-d') != $requestDate->format('Y-m-d')) {
            return false;
        } // error in parameters

        $finalUpdateDate = $depDate;

        if ($finalUpdateDate->getTimestamp() <= $updateDate->getTimestamp()) {
            return false;
        } // time is over

        if ($finalUpdateDate->getTimestamp() <= time()) {
            return true;
        } // last update

        $secondsToFinalUpdate = $finalUpdateDate->getTimestamp() - $updateDate->getTimestamp();

        // search next update interval
        $intervals = self::UPDATE_INTERVALS;

        while (($interval = current($intervals)) !== false && $interval >= $secondsToFinalUpdate) {
            next($intervals);
        }

        // found next update interval
        if ($interval !== false) {
            $nextUpdate = clone $finalUpdateDate;
            $nextUpdate->sub(new \DateInterval('PT' . $interval . 'S'));

            if ($nextUpdate->getTimestamp() <= time()) {
                return true;
            } // skipped interval

            return $nextUpdate;
        }

        // all intervals over
        return $finalUpdateDate;
    }

    /**
     * seconds to next update or false.
     *
     * @param \DateTime $updateDate
     * @param \DateTime $depDate
     * @param \DateTime $arrDate
     * @return bool|int
     */
    public function getNextUpdateInSeconds($updateDate, $depDate, $arrDate)
    {
        $nextUpdate = $this->getNextUpdate($updateDate, $depDate, $arrDate);

        if ($nextUpdate === false) {
            return false;
        }

        if ($nextUpdate === true) {
            return 0;
        }

        return $nextUpdate->getTimestamp() - time();
    }

    /**
     * @return $this
     */
    public function carrier($code)
    {
        $this->values['carrier'] = $code;

        return $this;
    }

    /**
     * @return $this
     */
    public function flight($flightNumber)
    {
        $this->values['flight'] = $flightNumber;

        return $this;
    }

    /**
     * @return $this
     */
    public function date(\DateTime $date)
    {
        $this->values['year'] = $date->format('Y');
        $this->values['month'] = $date->format('m');
        $this->values['day'] = $date->format('d');

        return $this;
    }

    /**
     * @return $this
     */
    public function year($year)
    {
        $this->values['year'] = $year;

        return $this;
    }

    /**
     * @return $this
     */
    public function month(\DateTime $month)
    {
        $this->values['month'] = $month;

        return $this;
    }

    /**
     * @return $this
     */
    public function day($day)
    {
        $this->values['day'] = $day;

        return $this;
    }

    /**
     * @return $this
     */
    public function departure($airportCode)
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function arrival($airportCode)
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function isUTCDate($isUTC)
    {
        $this->values['utc'] = (bool) $isUTC;

        return $this;
    }

    /**
     * @throws RequestException
     */
    protected function validate()
    {
        parent::validate();

        if (!preg_match('/^([0-9][A-Z]|[A-Z][0-9]|[A-Z]{2,3})$/i', $this->values['carrier'])) {
            throw new RequestException('Error in parameter Carrier: ' . $this->values['carrier']);
        }

        if (!preg_match('/^[0-9]+$/', $this->values['flight']) || intval($this->values['flight']) == 0) {
            throw new RequestException('Error in parameter Flight: ' . $this->values['flight']);
        }

        if (!preg_match('/^[0-9]{4}$/', $this->values['year'])) {
            throw new RequestException('Error in parameter Year: ' . $this->values['year']);
        }

        if (!preg_match('/^[0-9]{1,2}$/', $this->values['month']) || intval($this->values['month']) > 12 || intval($this->values['month']) < 1) {
            throw new RequestException('Error in parameter Month: ' . $this->values['month']);
        }

        if (!preg_match('/^[0-9]{1,2}$/', $this->values['day']) || intval($this->values['day']) > 31 || intval($this->values['day']) < 1) {
            throw new RequestException('Error in parameter Day: ' . $this->values['day']);
        }

        if (!in_array($this->values['type'], ['dep', 'arr'])) {
            throw new RequestException('Unsupported Request Type: ' . $this->values['type']);
        }

        if (!is_bool($this->values['utc'])) {
            throw new RequestException('Error in parameter UTC: ' . $this->values['utc']);
        }

        if (!empty($this->values['airport']) && !preg_match('/^[A-Z]{3,4}$/i', $this->values['airport'])) {
            throw new RequestException('Error in parameter Airport: ' . $this->values['airport']);
        }

        if (!empty($this->values['codeType']) && !in_array(strtolower($this->values['codeType']), ['iata', 'icao', 'fs'])) {
            throw new RequestException('Unsupported Code Type: ' . $this->values['codeType']);
        }

        try {
            $date = new \DateTime(implode('-', [$this->values['year'], $this->values['month'], $this->values['day']]));
        } catch (\Exception $e) {
            throw new RequestException('Unsupported Date: ' . implode('-', [$this->values['year'], $this->values['month'], $this->values['day']]));
        }

        if ($date->format('Y') != $this->values['year']) {
            throw new RequestException('Error in parameter Year: ' . $this->values['year']);
        }

        if ($date->format('m') != $this->values['month']) {
            throw new RequestException('Error in parameter Month: ' . $this->values['month']);
        }

        if ($date->format('d') != $this->values['day']) {
            throw new RequestException('Error in parameter Day: ' . $this->values['day']);
        }

        if ($this->values['protocol'] != 'rest') {
            throw new RequestException('Unsupported Protocol: ' . $this->values['protocol']);
        }

        if ($this->values['format'] != 'json') {
            throw new RequestException('Unsupported Format: ' . $this->values['format']);
        }
    }
}
