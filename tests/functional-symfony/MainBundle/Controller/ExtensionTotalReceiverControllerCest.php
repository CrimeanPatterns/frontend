<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use Codeception\Example;

/**
 * @group frontend-functional
 */
class ExtensionTotalReceiverControllerCest
{
    /**
     * @dataProvider receiveDataProvider
     */
    public function receiveTest(\TestSymfonyGuy $I, Example $example)
    {
        if (!isset($example['anonymous'])) {
            $userId = $I->createAwUser();
            $I->switchToUser($userId);
        }

        $data = $example['data'];

        foreach ($data['urls'] as &$url) {
            $url['datetime'] += time();
        }
        $I->sendPost('/api/extension/v1/receive', json_encode($data));
        $I->seeResponseCodeIs(200);

        if (is_array($example['expectedRow'])) {
            $expectedRow = $example['expectedRow'];
            $expectedRow['ReceiveDate'] = date("Y-m-d H:i:s");
            $I->seeInDatabase('ReceivedTotal', array_diff_key(array_merge(['UserID' => $userId], $expectedRow), ['ReceiveDate' => false]));
            $I->assertLessThanOrEqual(5, abs(strtotime($expectedRow['ReceiveDate']) - time()));
        }
    }

    public function receiveDataProvider(): array
    {
        $time = time();

        return [
            [
                // anonymous
                'anonymous' => true,
                'data' => ['extensionVersion' => '1.0.0', 'urls' => [['url' => 'https://nike.com/checkout', 'total' => 12.34, 'datetime' => 0]]],
                'expectedRow' => false,
            ],
            [
                // valid
                'data' => ['extensionVersion' => '1.0.0', 'urls' => [['url' => 'https://nike.com/checkout', 'total' => 12.34, 'datetime' => 0]]],
                'expectedRow' => [
                    "URL" => 'https://nike.com/checkout',
                    "Total" => 12.34,
                    "ExtensionVersion" => '1000000',
                ],
            ],
            [
                // long extension version
                'data' => ['extensionVersion' => '3.2.88', 'urls' => [['url' => 'https://nike.com/checkout', 'total' => 12.34, 'datetime' => 0]]],
                'expectedRow' => [
                    "URL" => 'https://nike.com/checkout',
                    "Total" => 12.34,
                    "ExtensionVersion" => '3002088',
                ],
            ],
            [
                // invalid url
                'data' => ['extensionVersion' => '3.2.88', 'urls' => [['url' => 'httpnike.com/checkout', 'total' => 12.34, 'datetime' => 0]]],
                'expectedRow' => false,
            ],
            [
                // invalid total
                'data' => ['extensionVersion' => '3.2.88', 'urls' => [['url' => 'https://nike.com/checkout', 'total' => -13.23, 'datetime' => 0]]],
                'expectedRow' => false,
            ],
            [
                // invalid total
                'data' => ['extensionVersion' => '3.2.88', 'urls' => [['url' => 'https://nike.com/checkout', 'total' => 1000001, 'datetime' => 0]]],
                'expectedRow' => false,
            ],
            [
                // invalid total
                'data' => ['extensionVersion' => '3.2.88', 'urls' => [['url' => 'https://nike.com/checkout', 'total' => 1000001, 'datetime' => 0]]],
                'expectedRow' => false,
            ],
            [
                // invalid extension version
                'data' => ['extensionVersion' => '1000.2.88', 'urls' => [['url' => 'https://nike.com/checkout', 'total' => 12.34, 'datetime' => 0]]],
                'expectedRow' => false,
            ],
            [
                // corrected date
                'data' => ['extensionVersion' => '1.0.0', 'urls' => [['url' => 'https://nike.com/checkout', 'total' => 12.34, 'datetime' => 100]]],
                'expectedRow' => [
                    "URL" => 'https://nike.com/checkout',
                    "Total" => 12.34,
                    "ExtensionVersion" => '1000000',
                ],
            ],
        ];
    }
}
