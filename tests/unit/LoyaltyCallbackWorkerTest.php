<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 21.04.16
 * Time: 21:02.
 */

namespace AwardWallet\Tests\Unit;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\Controller\LoyaltyCallbackController;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\CheckCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationResponse;
use AwardWallet\MainBundle\Worker\LoyaltyCallbackWorker;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Worker\LoyaltyCallbackWorker
 */
class LoyaltyCallbackWorkerTest extends BaseContainerTest
{
    /** @var Serializer */
    private $serializer;

    // only for debug. remove after #14844 fix
    public function __testOneTime()
    {
        $requestJson = '{"provider": "triprewards", "userId": "300557", "userData": "300557", "priority": 7, "callbackUrl": "http:\/\/awardwallet.dev\/api\/awardwallet\/loyalty\/callback\/confirmation\/7", "fields": [{"code": "FirstName", "value": "Steven"}, { "code": "LastName", "value": "Chapman" }, {"code": "ConfNo", "value": "5136B13175514"}]}';
        $responseJson = '{"method": "confirmation", "response": [{"requestId": "5923ed866e87b14b018b4594", "userData": "300557", "state": 1, "message": "", "checkDate": "2017-05-23T08:06:41+00:00", "requestDate": "2017-05-23T08:06:30+00:00", "itineraries": [{"providerDetails": {"confirmationNumber": "5136B13175514", "name": "AmeriHost Inn, Days Inn, Howard Johnson, Knights Inn, Ramada, Super 8, ...", "code": "triprewards"}, "totalPrice": {"total": 145.15, "currencyCode": "USD", "tax": 16.15, "rate": "129.00"}, "type": "hotelReservation", "hotelName": "Inn at USC Wyndham Garden", "checkInDate": "2017-05-25T15:00:00", "checkOutDate": "2017-05-26T12:00:00", "address": {"text": "1619 Pendleton Street Columbia, SC 29201 US", "addressLine": "1619 Pendleton Street", "city": "Columbia", "stateName": "South Carolina", "countryName": "United States", "postalCode": "29201", "lat": "34.0011079", "lng": "-81.025522", "timezone": "-14400"}, "phone": "1-803-779-7779", "fax": "1-803-779-2197", "guestCount": 2, "kidsCount": 1, "roomsCount": 1, "cancellationPolicy": "Cancel 24 Hours prior to arrival by 6pm to avoid 1 Night charge plus tax"}]}]}';

        $message = new AMQPMessage($responseJson);
        $request = $this->serializer->deserialize($requestJson, CheckConfirmationRequest::class, 'json');

        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $memcached = $this->getMockBuilder(\Memcached::class)->disableOriginalConstructor()->getMock();

        $memcached->expects($this->once())->method('get')
                  ->with('check_confirmation_request_5923ed866e87b14b018b4594')
                  ->willReturn($request);

        $converter = $this->container->get(Converter::class);

        $worker = new LoyaltyCallbackWorker($logger, $converter, $memcached, $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock());
        $result = $worker->execute($message);
    }

    public function __S7FlightProblem()
    {
        $requestJson = '{"provider":"s7","userId":"62895","userData":"{\"accountId\":3318555,\"priority\":7,\"source\":1}","priority":7,"callbackUrl":"http:\/\/awardwallet.docker\/api\/awardwallet\/loyalty\/callback\/account\/7","login":"nadezhda-priv@yandex.ru","password":"kpgg6grSLaud9x0Bw9H3H78Sj0P47SMUWTKmCUhl9kSi4IuGBHoOohlqRIqAHDUjlfJ8Jv9OsrjiPSRzO62F2CnmceacJTKwKnnZT1hZLqsfYAkx2ok3dxrdDe8CDw425tPnXHNxrScgd3NIsUy44N5s5\/zA4eUFcDu9GkJXapg=","parseItineraries":true,"history":{"range":"incremental","state":"q0CCioctQQ5yqbsEkFKW8JPdDASFCwrcon7qzxvcRX9+yATcjeamX4iNGLMAeOAkRXeUY1P05nXOOCLypx\/2LA=="}}';
        $responseJson = '{"method": "account", "response": [{"requestId":"59dc5e7b4dfbed01185737eb","userData":"{\"accountId\":3318555,\"priority\":7,\"source\":1}","debugInfo":"","state":1,"message":"","errorReason":"","checkDate":"2017-10-10T05:46:04+00:00","requestDate":"2017-10-10T05:45:31+00:00","login":"nadezhda-priv@yandex.ru","balance":9894,"properties":[{"code":"Name","name":"Name","value":"Nadezhda Rydannykh"},{"code":"Status","name":"Status","kind":"3","value":"CLASSIC"},{"code":"CreationDate","name":"Date of creation","kind":"5","value":"26.10.2016"},{"code":"StatusFlights","name":"Status Flights","value":"8"},{"code":"StatusMiles","name":"Status Miles","value":"6592"},{"code":"Number","name":"Card number","kind":"1","value":"827878877"}],"noItineraries":false,"history":{"range":"incremental","state":"q0CCioctQQ5yqbsEkFKW8JPdDASFCwrcon7qzxvcRX9+yATcjeamX4iNGLMAeOAkRXeUY1P05nXOOCLypx\/2LA==","rows":[{"fields":[{"code":"PostingDate","name":"Date","value":"1501891200"},{"code":"Description","name":"Description","value":"Sochi, (all airports), Russia - Moscow, Domodedovo, Russia (S7 1022 \/ Economy \/ S)"},{"code":"Miles","name":"Miles","value":"+ 857"}]},{"fields":[{"code":"PostingDate","name":"Date","value":"1501891200"},{"code":"Description","name":"Description","value":"Moscow, Domodedovo, Russia - Perm, (all airports), Russia (S7 0301 \/ Economy \/ S)"},{"code":"Miles","name":"Miles","value":"+ 719"}]}]}}]}';

        $message = new AMQPMessage($responseJson);
        $request = $this->serializer->deserialize($requestJson, CheckConfirmationRequest::class, 'json');

        $logger = $this->container->get(LoggerInterface::class);
        $memcached = $this->container->get(\Memcached::class);
        $converter = $this->container->get(Converter::class);

        $worker = new LoyaltyCallbackWorker($logger, $converter, $memcached, $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock());
        $result = $worker->execute($message);
    }

    public function _before()
    {
        parent::_before();
        $this->serializer = $this->container->get('jms_serializer');
    }

    public function _after()
    {
        $this->serializer = null;
        parent::_after();
    }

    public function testProcessAccountSuccess()
    {
        $requestId = 'SomeRequestID';
        $response = (new CheckAccountResponse())->setRequestid($requestId);
        $callback = (new CheckAccountCallback())
                        ->setType(LoyaltyCallbackController::ACCOUNT_METHOD)
                        ->setResponse([$response]);
        $msgContent = $this->serializer->serialize($callback, 'json');

        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $memcached = $this->container->get(\Memcached::class);

        $converter = $this->getMockBuilder(Converter::class)->disableOriginalConstructor()->getMock();
        $converter->expects($this->once())->method('deserialize')
                  ->with($msgContent, CheckCallback::class)
                  ->willReturn($callback);
        $converter->expects($this->once())->method('processCallbackPackage')
                  ->with($callback)
                  ->willReturn([]);

        $message = $this->getMockBuilder(AMQPMessage::class)->disableOriginalConstructor()->getMock();
        $message->expects($this->once())->method('getBody')
                ->willReturn($msgContent);

        $worker = new LoyaltyCallbackWorker(
            $logger,
            $converter,
            $memcached,
            $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock(),
            $this->createMock(AppProcessor::class),
            $this->createMock(ProducerInterface::class)
        );

        $result = $worker->execute($message);
        $this->assertEquals(true, $result);
    }

    public function testProcessConfirmationSuccess()
    {
        $requestId = 'SomeRequestID' . rand(1000, 10000) . time();
        $trips = ['trip1', 'trip2'];
        $request = (new CheckConfirmationRequest())->setProvider('testprovider');
        $response = (new CheckConfirmationResponse())->setRequestid($requestId);
        $callback = (new CheckConfirmationCallback())
                        ->setType(LoyaltyCallbackController::CONFIRMATION_METHOD)
                        ->setResponse([$response]);
        $msgContent = $this->serializer->serialize($callback, 'json');

        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $memcached = $this->container->get(\Memcached::class);
        $memcached->set('check_confirmation_request_' . $requestId, $request);
        //        $memcached = $this->getMockBuilder(Memcached::class)->disableOriginalConstructor()->getMock();
        //        $memcached->expects($this->once())->method('set')
        //                  ->with('check_confirmation_result_'.$requestId, $trips);
        //        $memcached->expects($this->once())->method('get')
        //                  ->with('check_confirmation_request_'.$requestId)
        //                  ->willReturn($request);

        $converter = $this->getMockBuilder(Converter::class)->disableOriginalConstructor()->getMock();
        $converter->expects($this->once())->method('deserialize')
                  ->with($msgContent, CheckCallback::class)
                  ->willReturn($callback);
        $converter->expects($this->once())->method('processCheckConfirmationResponse')
                  ->with($response, $request)
                  ->willReturn($trips);

        $message = $this->getMockBuilder(AMQPMessage::class)->disableOriginalConstructor()->getMock();
        $message->expects($this->once())->method('getBody')
                ->willReturn($msgContent);

        $worker = new LoyaltyCallbackWorker(
            $logger,
            $converter,
            $memcached,
            $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock(),
            $this->createMock(AppProcessor::class),
            $this->createMock(ProducerInterface::class)
        );

        $result = $worker->execute($message);
        $this->assertEquals(true, $result);
    }
}
