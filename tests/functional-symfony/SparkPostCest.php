<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Globals\StringHandler;

/**
 * @group frontend-functional
 */
class SparkPostCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        $password = $I->grabService("service_container")->getParameter("sparkpost_callback_password");
        $I->amHttpAuthenticated("sparkpost:", $password);
    }

    public function testSparkBounce(\TestSymfonyGuy $I)
    {
        $data = file_get_contents(__DIR__ . '/../_data/sparkBounce.json');
        $email = StringHandler::getRandomCode(20) . "@awardwallet.com";
        $data = str_replace("recipient@example.com", $email, $data);
        $I->dontSeeInDatabase("EmailNDR", ["Address" => $email]);

        $I->sendPOST("/api/sparkpost/bounce", $data);

        $I->seeInDatabase("EmailNDR", ["Address" => $email]);
        $I->assertEquals(
            6,
            $I->query("select count(*) from EmailNDRContent c join EmailNDR n on n.EmailNDRID = c.EmailNDRID where n.Address = :email", ["email" => $email])->fetchColumn()
        );
        $I->seeInDatabase("DoNotSend", ["Email" => $email]);

        $I->seeResponseCodeIs(200);
    }

    public function testHardBounce(\TestSymfonyGuy $I)
    {
        $data = file_get_contents(__DIR__ . '/../_data/sparkHardBounce.json');
        $userId = $I->createAwUser();
        $email = $I->grabFromDatabase("Usr", "Email", ["UserID" => $userId]);
        $data = str_replace("recipient@example.com", $email, $data);

        $I->assertEquals(EMAIL_VERIFIED, $I->grabFromDatabase("Usr", "EmailVerified", ["UserID" => $userId]));

        $I->sendPOST("/api/sparkpost/bounce", $data);

        $I->seeInDatabase("EmailNDR", ["Address" => $email]);
        $I->assertEquals(
            1,
            $I->query("select count(*) from EmailNDRContent c join EmailNDR n on n.EmailNDRID = c.EmailNDRID where n.Address = :email", ["email" => $email])->fetchColumn()
        );
        $I->dontSeeInDatabase("DoNotSend", ["Email" => $email]);
        $I->assertEquals(EMAIL_NDR, $I->grabFromDatabase("Usr", "EmailVerified", ["UserID" => $userId]));

        $I->seeResponseCodeIs(200);
    }

    public function testOfferSpamComplaint(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $email = $I->grabFromDatabase("Usr", "Email", ["UserID" => $userId]);
        $data = file_get_contents(__DIR__ . '/../_data/sparkSpamOffer.json');
        $data = str_replace("recipient@example.com", $email, $data);
        $I->assertEquals(1, $I->grabFromDatabase("Usr", "EmailOffers", ["UserID" => $userId]));

        $I->sendPOST("/api/sparkpost/bounce", $data);

        $I->seeResponseCodeIs(200);
        $I->dontSeeInDatabase("DoNotSend", ["Email" => $email]);
        $I->assertEquals(0, $I->grabFromDatabase("Usr", "EmailOffers", ["UserID" => $userId]));
    }

    public function testGenericSpamComplaint(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $email = $I->grabFromDatabase("Usr", "Email", ["UserID" => $userId]);
        $data = file_get_contents(__DIR__ . '/../_data/sparkSpam.json');
        $data = str_replace("recipient@example.com", $email, $data);
        $I->assertEquals(1, $I->grabFromDatabase("Usr", "EmailOffers", ["UserID" => $userId]));

        $I->sendPOST("/api/sparkpost/bounce", $data);

        $I->seeResponseCodeIs(200);
        $I->seeInDatabase("DoNotSend", ["Email" => $email]);
        $I->assertEquals(1, $I->grabFromDatabase("Usr", "EmailOffers", ["UserID" => $userId]));
    }
}
