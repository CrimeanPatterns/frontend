<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 23.03.16
 * Time: 18:53.
 */

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Saver;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\CurlSender;
use AwardWallet\MainBundle\Loyalty\CurlSenderResult;
use AwardWallet\MainBundle\Loyalty\HistoryState\HistoryStateBuilder;
use AwardWallet\MainBundle\Loyalty\Resources\AutoLoginRequest;
use AwardWallet\MainBundle\Loyalty\Resources\AutoLoginResponse;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportPackageRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportPackageResponse;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportRequest;
use AwardWallet\MainBundle\Loyalty\Resources\InputField;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\QueueInfoResponse;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Serializer;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @group frontend-unit
 */
class LoyaltyApiCommunicatorTest extends BaseContainerTest
{
    /** @var Serializer */
    protected $serializer;
    protected $userId;
    protected $accountId;

    public function _before()
    {
        parent::_before();
        $this->serializer = $this->container->get('jms_serializer');
        $this->userId = $this->aw->createAwUser();
        $this->accountId = $this->aw->createAwAccount($this->userId, 'testprovider', 'history');
    }

    public function _after()
    {
        $this->serializer = null;
        $this->userId = null;
        $this->accountId = null;
        parent::_after();
    }

    public function testProcessCheckConfirmationRequest()
    {
        $requestId = 'qwerty123456';
        $request = new CheckConfirmationRequest();
        $request->setUserId('SomeID')
                ->setProvider('testprovider')
                ->setFields($this->getConfirmationRequestFields());

        $converter = $this->getConverter();
        $cacheManager = $this->getMockBuilder(CacheManager::class)->disableOriginalConstructor()->getMock();
        $loggerMock = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $senderMock = $this->getMockBuilder(CurlSender::class)->disableOriginalConstructor()->getMock();
        $senderMock->expects($this->once())->method('call')
                   ->with(ApiCommunicator::METHOD_CONFIRMATION_CHECK, $converter->serialize($request))
                   ->willReturn((new CurlSenderResult())->setCode(200)->setResponse($converter->serialize((new PostCheckAccountResponse())->setRequestid($requestId))));

        $communicator = new ApiCommunicator($this->serializer, $senderMock, $loggerMock, $cacheManager, $this->createMock(EventDispatcherInterface::class), $this->createMock(AccountRepository::class));

        /** @var PostCheckAccountResponse $response */
        $response = $communicator->CheckConfirmation($request);
        $this->assertInstanceOf(PostCheckAccountResponse::class, $response);
        $this->assertEquals($requestId, $response->getRequestid());
    }

    public function testProcessCheckAccountRequest()
    {
        $requestId = 'qwerty123456';
        $request = new CheckAccountRequest();
        $request->setUserdata((new UserData())->setAccountId($this->accountId))
                ->setProvider('testprovider')
                ->setLogin('balance.comma')
                ->setUserid('SomeID');

        $converter = $this->getConverter();
        $cacheManager = $this->getMockBuilder(CacheManager::class)->disableOriginalConstructor()->getMock();
        $loggerMock = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $senderMock = $this->getMockBuilder(CurlSender::class)->disableOriginalConstructor()->getMock();
        $senderMock->expects($this->once())->method('call')
                   ->with(ApiCommunicator::METHOD_ACCOUNT_CHECK, $converter->serialize($request))
                   ->willReturn((new CurlSenderResult())->setCode(200)->setResponse($converter->serialize((new PostCheckAccountResponse())->setRequestid($requestId))));

        $communicator = new ApiCommunicator($this->serializer, $senderMock, $loggerMock, $cacheManager, $this->createMock(EventDispatcherInterface::class), $this->createMock(AccountRepository::class));

        /** @var PostCheckAccountResponse $response */
        $response = $communicator->CheckAccount($request);
        $this->assertInstanceOf(PostCheckAccountResponse::class, $response);
        $this->assertEquals($requestId, $response->getRequestid());
    }

    public function testQueueInfo()
    {
        $res = '{"queues":[{"provider":"ozon","itemsCount":2},{"provider":"domru","itemsCount":2},{"provider":"gazneft","itemsCount":2},{"provider":"testprovider","itemsCount":1}]}';

        $cacheManager = $this->getMockBuilder(CacheManager::class)->disableOriginalConstructor()->getMock();
        $loggerMock = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $senderMock = $this->getMockBuilder(CurlSender::class)->disableOriginalConstructor()->getMock();
        $senderMock->expects($this->once())->method('call')
                   ->with(ApiCommunicator::METHOD_ACCOUNT_QUEUE_INFO)
                   ->willReturn((new CurlSenderResult())->setCode(200)->setResponse($res));

        $communicator = new ApiCommunicator($this->serializer, $senderMock, $loggerMock, $cacheManager, $this->createMock(EventDispatcherInterface::class), $this->createMock(AccountRepository::class));

        /** @var QueueInfoResponse $response */
        $response = $communicator->GetQueueInfo(ApiCommunicator::METHOD_ACCOUNT_QUEUE_INFO);
        $this->assertInstanceOf(QueueInfoResponse::class, $response);
        $this->assertEquals(4, count($response->getQueues()));
    }

    public function testAutoLoginSuccess()
    {
        $responseData = 'SomeData' . time();
        $userData = 'UserData' . time();
        $response = (new AutoLoginResponse())->setResponse($responseData)->setUserData($userData);
        $request = (new AutoLoginRequest())->setProvider('testprovider')->setLogin('someLogin');
        $cacheManager = $this->getMockBuilder(CacheManager::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $sender = $this->getMockBuilder(CurlSender::class)->disableOriginalConstructor()->getMock();
        $sender->expects($this->once())
               ->method("call")
               ->with(ApiCommunicator::METHOD_ACCOUNT_AUTOLOGIN, $this->serializer->serialize($request, 'json'))
               ->willReturn(
                   (new CurlSenderResult())
                       ->setCode(200)
                       ->setResponse($this->serializer->serialize($response, 'json'))
               );

        $communicator = new ApiCommunicator($this->serializer, $sender, $logger, $cacheManager, $this->createMock(EventDispatcherInterface::class), $this->createMock(AccountRepository::class));
        $response = $communicator->AutoLogin($request);

        $this->assertEquals($responseData, $response->getResponse());
        $this->assertEquals($userData, $response->getUserData());
    }

    public function testExtensionSupport()
    {
        $senderMock = $this
            ->prophesize(CurlSender::class)
            ->call(ApiCommunicator::METHOD_CHECK_EXTENSION_SUPPORT_PACKAGE, Argument::that(function (string $json) {
                $requestBody = \json_decode($json, true);
                $this->assertArrayContainsArray(
                    [
                        [
                            'provider' => 'provider_1',
                            'id' => 'id_1',
                            'login' => 'login_1',
                            'login2' => 'login2_1',
                            'login3' => 'login3_1',
                            'isMobile' => true,
                        ],
                        [
                            'provider' => 'provider_2',
                            'id' => 'id_2',
                            'login' => 'login_2',
                            'login2' => 'login2_2',
                            'login3' => 'login3_2',
                            'isMobile' => true,
                        ],
                    ],
                    $requestBody['package'] ?? []
                );

                return true;
            }))
            ->willReturn(
                (new CurlSenderResult())
                ->setCode(200)
                ->setResponse('{"package":{"id_1":true,"id_2":false}}')
            )
            ->getObjectProphecy()
        ;
        $communicator = new ApiCommunicator(
            $this->serializer,
            $senderMock->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(CacheManager::class)->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            $this->prophesize(AccountRepository::class)->reveal()
        );

        /** @var QueueInfoResponse $response */
        $response = $communicator->CheckExtensionSupport(
            (new CheckExtensionSupportPackageRequest())
            ->setPackage([
                (new CheckExtensionSupportRequest())
                    ->setProvider('provider_1')
                    ->setId('id_1')
                    ->setLogin('login_1')
                    ->setLogin2('login2_1')
                    ->setLogin3('login3_1')
                    ->setIsMobile(true),
                (new CheckExtensionSupportRequest())
                    ->setProvider('provider_2')
                    ->setId('id_2')
                    ->setLogin('login_2')
                    ->setLogin2('login2_2')
                    ->setLogin3('login3_2')
                    ->setIsMobile(true),
            ])
        );
        $this->assertInstanceOf(CheckExtensionSupportPackageResponse::class, $response);
        $this->assertEquals(['id_1' => true, 'id_2' => false], $response->getPackage());
    }

    private function getConverter()
    {
        $loggerMock = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $emMock = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $processor = $this->getMockBuilder(AccountProcessor::class)->disableOriginalConstructor()->getMock();
        $tracker = $this->getMockBuilder(ItineraryTracker::class)->disableOriginalConstructor()->getMock();
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->disableOriginalConstructor()->getMock();
        $userRepository = $this->getMockBuilder(UsrRepository::class)->disableOriginalConstructor()->getMock();
        $userAgentRepository = $this->getMockBuilder(UseragentRepository::class)->disableOriginalConstructor()->getMock();
        $accountRepository = $this->getMockBuilder(AccountRepository::class)->disableOriginalConstructor()->getMock();
        $providerRepository = $this->getMockBuilder(ProviderRepository::class)->disableOriginalConstructor()->getMock();
        $itinerariesProcessor = $this->getMockBuilder(ItinerariesProcessor::class)->disableOriginalConstructor()->getMock();
        $scannerApi = $this->getMockBuilder(EmailScannerApi::class)->disableOriginalConstructor()->getMock();

        return new Converter($loggerMock, $this->serializer, $emMock, $userRepository, $userAgentRepository,
            $accountRepository, $providerRepository, $processor, $itinerariesProcessor, $tracker, $scannerApi, $dispatcher,
            '', '', '', $this->makeEmpty(HistoryStateBuilder::class),
            $this->makeEmpty(Saver::class), $this->makeEmpty(\Memcached::class)
        );
    }

    private function getConfirmationRequestFields()
    {
        $fields = [];
        $fields[] = (new InputField())->setCode('ConfNo')->setValue('TESTVALUE');
        $fields[] = (new InputField())->setCode('LastName')->setValue('TESTNAME');

        return $fields;
    }
}
