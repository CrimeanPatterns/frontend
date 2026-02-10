<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Email\EmailOptions;
use AwardWallet\MainBundle\Email\Util;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class CacheTest extends BaseUserTest
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var QueryOptions
     */
    private $options;

    public function _before()
    {
        parent::_before();
        $this->manager = $this->container->get(Manager::class);
        $this->options = (new QueryOptions())
            ->setFormat(ItemFormatterInterface::DESKTOP)
            ->setWithDetails(false)
            ->setUser($this->user);
    }

    public function testSaveTripsByEmail()
    {
        $items = $this->manager->query($this->options);
        $this->assertEmpty($items);

        $data = '{
                   "apiVersion":2,
                   "status":"success",
                   "providerCode":"delta",
                   "itineraries":[
                      {
                         "providerInfo":{
                            "code":"delta",
                            "name":"Delta Air Lines"
                         },
                         "segments":[
                            {
                               "departure":{
                                  "airportCode":"PHX",
                                  "name":"Phoenix Sky Harbor International Airport",
                                  "localDateTime":"2018-08-23T13:00:00",
                                  "address":{
                                     "text":"PHX",
                                     "addressLine":"3400 East Sky Harbor Boulevard",
                                     "city":"Phoenix",
                                     "stateName":"Arizona",
                                     "countryName":"United States",
                                     "postalCode":"85034",
                                     "lat":33.4372686,
                                     "lng":-112.0077881,
                                     "timezone":-25200
                                  }
                               },
                               "arrival":{
                                  "airportCode":"LAX",
                                  "name":"Los Angeles International Airport",
                                  "localDateTime":"2018-08-23T15:00:00",
                                  "address":{
                                     "text":"LAX",
                                     "addressLine":"1 World Way",
                                     "city":"Los Angeles",
                                     "stateName":"California",
                                     "countryName":"United States",
                                     "postalCode":"90045",
                                     "lat":33.9415889,
                                     "lng":-118.40853,
                                     "timezone":-25200
                                  }
                               },
                               "marketingCarrier":{
                                  "airline":{
                                     "name":"Delta Air Lines",
                                     "iata":"DL",
                                     "icao":"DAL"
                                  },
                                  "flightNumber":"12345",
                                  "confirmationNumber":"A7BU5S",
                                  "phoneNumbers":[
                                     {
                                        "number":"+1-404-714-2300"
                                     }
                                  ]
                               }
                            }
                         ],
                         "issuingCarrier":{
                            "airline":{
                               "name":"Delta Air Lines",
                               "iata":"DL",
                               "icao":"DAL"
                            },
                            "confirmationNumber":"A7BU5S",
                            "phoneNumbers":[
                               {
                                  "number":"+1-404-714-2300"
                               }
                            ]
                         },
                         "type":"flight"
                      }
                   ],
                   "metadata":{
                      "to":[
                
                      ],
                      "cc":[
                
                      ]
                   }
                }';
        $this->saveDataFromEmail($data);

        $this->db->seeInDatabase("Trip", ["UserID" => $this->user->getUserid(), "RecordLocator" => "A7BU5S"]);
        $items = $this->manager->query($this->options);
        $this->assertNotEmpty($items);

        $data = '{
                   "apiVersion":2,
                   "status":"success",
                   "providerCode":"spg",
                   "itineraries":[
                      {
                         "providerInfo":{
                            "code":"spg",
                            "name":"Starwood Hotels"
                         },
                         "confirmationNumbers":[
                            {
                               "number":"01203460"
                            }
                         ],
                         "hotelName":"Near JFK Hotel",
                         "address":{
                            "text":"London, England",
                            "city":"London",
                            "stateName":"England",
                            "countryName":"United Kingdom",
                            "lat":51.5073509,
                            "lng":-0.1277583,
                            "timezone":3600
                         },
                         "checkInDate":"2030-02-01T00:00:00",
                         "checkOutDate":"2030-02-02T00:00:00",
                         "type":"hotelReservation"
                      }
                   ],
                   "metadata":{
                      "to":[
                
                      ],
                      "cc":[
                
                      ]
                   }
                }';
        $this->saveDataFromEmail($data);
        $this->db->seeInDatabase("Reservation", ["UserID" => $this->user->getUserid(), "ConfirmationNumber" => "01203460"]);
        $newItems = $this->manager->query($this->options);
        $this->assertGreaterThan(count($items), count($newItems));
    }

    public function _after()
    {
        $this->manager = null;

        parent::_after(); // TODO: Change the autogenerated stub
    }

    private function saveDataFromEmail(string $json)
    {
        $this->mockServiceWithBuilder('aw.email.mailer');
        $processor = $this->container->get("aw.email.callback_processor");
        $data = $this->container->get('jms_serializer')->deserialize($json, ParseEmailResponse::class, 'json');
        $email = file_get_contents(__DIR__ . '/../../_data/expedia.eml');
        $email = str_ireplace("ialabuzheva.123@awardwallet.com", $this->user->getLogin() . '@awardwallet.com', $email);
        $data->email = base64_encode($email);
        $result = $processor->process($data, new EmailOptions($data, false), null, null);
        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);
    }
}
