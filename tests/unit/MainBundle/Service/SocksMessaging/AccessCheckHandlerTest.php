<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\SocksMessaging;

use AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter\MessagingChannelVoter;
use AwardWallet\MainBundle\Security\Voter\Subject\MessagingChannel;
use AwardWallet\MainBundle\Service\SocksMessaging\AccessCheckHandler;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Service\SocksMessaging\AccessCheckHandler
 */
class AccessCheckHandlerTest extends BaseTest
{
    /**
     * @dataProvider invalidArgumentShouldThrowAnExceptionDataProvider
     * @covers ::checkAuth
     */
    public function testInvalidArgumentShouldThrowAnException(object $input, string $exceptionClass, string $exceptionMessage): void
    {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        $accessCheckHandler = new AccessCheckHandler(
            $this->prophesize(Client::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(AuthorizationCheckerInterface::class)->reveal()
        );
        $accessCheckHandler->checkAuth($input);
    }

    public function invalidArgumentShouldThrowAnExceptionDataProvider(): array
    {
        return [
            'no channels array' => [
                new \stdClass(),
                BadRequestHttpException::class,
                'Invalid channels',
            ],
            'no client info' => [
                (function () {
                    $obj = new \stdClass();
                    $obj->channels = ['a', 'b'];

                    return $obj;
                })(),
                BadRequestHttpException::class,
                'Invalid client',
            ],
        ];
    }

    /**
     * @dataProvider accessCheckDataProvider
     * @covers ::checkAuth
     */
    public function testAccessCheck(object $input, bool $isGrantedResult, bool $generateChannelSignShouldBeCalled, array $result): void
    {
        $sockClientMock = $this->prophesize(Client::class);

        if ($generateChannelSignShouldBeCalled) {
            $sockClientMock
                ->generateChannelSign(Argument::is($input->client), 'channel_name')
                ->shouldBeCalledOnce()
                ->willReturn('some_sign');
        } else {
            $sockClientMock
                ->generateChannelSign(Argument::cetera())
                ->shouldBeCalledOnce()
                ->shouldNotBeCalled();
        }

        $accessCheckHandler = new AccessCheckHandler(
            $sockClientMock->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(AuthorizationCheckerInterface::class)
                ->isGranted(
                    Argument::is(MessagingChannelVoter::ATTRIBUTE_READ),
                    Argument::type(MessagingChannel::class)
                )
                ->willReturn($isGrantedResult)
                ->shouldBeCalledOnce()
                ->getObjectProphecy()
                ->reveal()
        );

        $this->assertEquals($result, $accessCheckHandler->checkAuth($input));
    }

    public function accessCheckDataProvider(): array
    {
        return [
            'success' => [
                $this->prepareValidInput(['channel_name'], 'some_client'),
                true, // isGranted result
                true, // need to call generateChannelSign,
                [
                    'channel_name' => [
                        'sign' => 'some_sign',
                        'info' => '',
                    ],
                ],
            ],
            'fail' => [
                $this->prepareValidInput(['channel_name'], 'some_client'),
                false, // isGranted result
                false, // need to call generateChannelSign,
                ['channel_name' => ['status' => 403]],
            ],
        ];
    }

    /**
     * @param string[] $channels
     */
    protected function prepareValidInput(array $channels, string $client): object
    {
        $object = new \stdClass();
        $object->channels = $channels;
        $object->client = $client;

        return $object;
    }
}
