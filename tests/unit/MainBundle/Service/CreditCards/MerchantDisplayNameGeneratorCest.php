<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Service\CreditCards\MerchantDisplayNameGenerator;
use Codeception\Example;

/**
 * @group frontend-unit
 */
class MerchantDisplayNameGeneratorCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider dataProvider
     */
    public function testCreate(\TestSymfonyGuy $I, Example $example)
    {
        $I->assertEquals($example['name'], MerchantDisplayNameGenerator::create($example['desc']));
    }

    protected function dataProvider()
    {
        return [
            [
                'desc' => 'At&t Mobile Recurring W',
                'name' => 'AT&T Mobile',
            ],
            [
                'desc' => 'at&t mobile recurring w',
                'name' => 'AT&T Mobile',
            ],
            [
                'desc' => 'AT&T MOBILE RECURRING W',
                'name' => 'AT&T Mobile',
            ],
            [
                'desc' => 'AT&amp;T MOBILE RECURRING W',
                'name' => 'AT&T Mobile',
            ],
            [
                'desc' => 'At&t*bill 80003434343',
                // 'name' => 'AT&T Bill',
                'name' => 'AT&T Bill 80003434343',
            ],
            [
                'desc' => 'Zu Den 3 Goldenen Kuge Graz-puntigam Aut Digital Account Number Xxxxxxxxxxxx1234',
                'name' => 'Zu Den 3 Goldenen Kuge Graz-puntigam Aut Digital',
            ],
            [
                'desc' => 'Z!abar\'s & Co1., 2Inc. 3New % York # Ny @ Digital Account Number XXXXxxxxxxx1234  ',
                'name' => 'Z!abar\'s & Co1., 2inc. 3new % York # Ny @ Digital',
            ],
            [
                'desc' => '{some name}',
                'name' => 'Some Name',
            ],
            [
                'desc' => "'name",
                'name' => 'Name',
            ],
            [
                'desc' => "______",
                'name' => '______',
            ],
            [
                'desc' => '___name___',
                'name' => 'Name',
            ],
            [
                'desc' => 'Zoom.us 888-799-9666 San Jose Ca - Virtual Account Number 1234',
                'name' => 'Zoom.us 888-799-9666 San Jose Ca',
            ],
            [
                'desc' => 'Wholefds Utc 00 Sarasota Digital Account Number Xxxxxxxxxxxx123',
                'name' => 'Wholefds Utc 00 Sarasota Digital',
            ],
            [
                'desc' => '" Scarlett" Bar',
                'name' => '" Scarlett" Bar',
            ],
            [
                'desc' => '"afanasiev a O Bus"',
                'name' => 'Afanasiev a O Bus',
            ],
            [
                'desc' => 'some "test"',
                'name' => 'Some "test"',
            ],
            [
                'desc' => '#1 1 2 string 123 name 1 2 some # 1',
                'name' => 'String 123 Name 1 2 Some # 1',
            ],
            [
                'desc' => '#name',
                'name' => 'Name',
            ],
            [
                'desc' => '$ name',
                'name' => 'Name',
            ],
            [
                'desc' => '$1name',
                'name' => 'Name',
            ],
            [
                'desc' => '$ 12 Name',
                'name' => 'Name',
            ],
            [
                'desc' => '$ 1.2 Name $ Test',
                'name' => 'Name $ Test',
            ],
            [
                'desc' => '$ 45,12 Name',
                'name' => 'Name',
            ],
            [
                'desc' => '$1.2Name',
                'name' => 'Name',
            ],
            [
                'desc' => '+name',
                'name' => 'Name',
            ],
            [
                'desc' => '+name+',
                'name' => 'Name+',
            ],
            [
                'desc' => '%name',
                'name' => 'Name',
            ],
            [
                'desc' => '%name%',
                'name' => 'Name%',
            ],
            [
                'desc' => '-name',
                'name' => 'Name',
            ],
            [
                'desc' => '/ /name',
                'name' => 'Name',
            ],
            [
                'desc' => '1 800 flowers',
                'name' => '1 800 Flowers',
            ],
            [
                'desc' => '1-800-flowers',
                'name' => '1-800-flowers',
            ],
            /*
            [
                'desc' => 'a 9999 10000 name',
                'name' => 'A 9999 Name',
            ],
            [
                'desc' => '42644 - 7th and Pile',
                'name' => '7th and Pile',
            ],
            [
                'desc' => 'Amc Southgate 9 12345',
                'name' => 'Amc Southgate 9',
            ],
            */
        ];
    }
}
