<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\GoogleVision\GoogleVisionClient;
use AwardWallet\MainBundle\Globals\GoogleVision\GoogleVisionLogo;
use AwardWallet\MainBundle\Globals\GoogleVision\GoogleVisionResponseConverter;
use AwardWallet\MainBundle\Globals\GoogleVision\GoogleVisionResult;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertJsonStringEqualsJsonString;

/**
 * @group frontend-unit
 */
class GoogleVisionClientTest extends BaseTest
{
    public function testThrottleLimit()
    {
        $client = $this->getClient([], 200, true);
        assertEquals(null, $client->recognize(
            $cardImage = (new CardImage())
                ->setUser(new Usr())
                ->setAccount(new Account()),
            '',
            []
        ));

        assertEquals([], $cardImage->getComputerVisionResult());
    }

    public function testSuccessResult()
    {
        $response = [
            'responses' => [
                [
                    'logoAnnotations' => [[
                        'description' => 'Some Provider',
                        'score' => 0.5,
                    ]],
                    'textAnnotations' => [[
                        'description' => 'Some Text From Card',
                    ]],
                ],
            ],
        ];
        $result = new GoogleVisionResult();
        $result->logos = [new GoogleVisionLogo('Some Provider', 0.5)];
        $result->text = 'Some Text From Card';

        assertEquals(
            $result,
            $this->getClient($response, 200)->recognize(
                $cardImage = (new CardImage())
                    ->setUser(new Usr())
                    ->setAccount(new Account()),
                '',
                []
            )
        );
        assertJsonStringEqualsJsonString(
            json_encode(['googleVision' => $response['responses'][0]]),
            json_encode($cardImage->getComputerVisionResult())
        );
    }

    /**
     * @param int $responseCode
     * @param bool $isThrottled
     * @return GoogleVisionClient
     */
    protected function getClient(array $responseBody, $responseCode = 200, $isThrottled = false)
    {
        $throttler = $this->prophesize(AntiBruteforceLockerService::class);
        $throttler->checkForLockout(Argument::any())->willReturn($isThrottled ? 'some throttler error' : null);

        $httpClient = $this->prophesize(Client::class);
        $httpClient
            ->post(Argument::any(), Argument::cetera())
            ->willReturn(new Response($responseCode, [], json_encode($responseBody)));

        return new GoogleVisionClient(
            $httpClient->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $throttler->reveal(),
            new GoogleVisionResponseConverter(),
            'google_api_key'
        );
    }
}
