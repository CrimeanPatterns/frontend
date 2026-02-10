<?php

namespace AwardWallet\Tests\Unit\src\lib\dParty\plancake;

/**
 * @group frontend-unit
 */
class PlancakeEmailParserCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testIllegalCharacters(\TestSymfonyGuy $I)
    {
        $parser = new \PlancakeEmailParser("Content-Type: text/html; charset=\"UTF-8\"
From: Novotel Beijing Xinqiao via Booking.com <1445866321-xp4s.ubrk.4kv5.cw5r@property.booking.com> 
Date: Thu, 29 Nov 2018 06:12:59 +0100 
To: sharewithaddy@gmail.com 
Subject: 答复: Reservation for Walia family from Dec 19 to 22 

this is body");
        $I->assertEquals("答复: Reservation for Walia family from Dec 19 to 22", $parser->getSubject());
    }
}
