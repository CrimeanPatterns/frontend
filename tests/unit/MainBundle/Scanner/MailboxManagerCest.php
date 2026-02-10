<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Repository\UserOAuthRepository;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\GoogleMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\ObjectSerializer;
use AwardWallet\MainBundle\Service\EmailParsing\EmailScannerApiStub;
use Codeception\Example;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class MailboxManagerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider dataProvider
     */
    public function testDelete(\TestSymfonyGuy $I, Example $example)
    {
        $email = bin2hex(random_bytes(10)) . "@gmail.com";
        $userId = $I->createAwUser(null, null, ["Email" => $email]);

        if ($example['haveOauthRecord']) {
            $I->haveInDatabase("UserOAuth", ["UserID" => $userId, "Provider" => "google", "OAuthID" => bin2hex(random_bytes(10)), "Email" => $email, "FirstName" => "John", "LastName" => "Smith"]);
        }
        /** @var Usr $user */
        $user = $I->grabService(UsrRepository::class)->find($userId);

        $expectedCalls = [
            'disconnectMailbox' => Stub::once(),
            'listMailboxes' => Stub::atLeastOnce(function () use ($user) {
                return [
                    ObjectSerializer::deserialize((object) ['id' => 12345, "email" => $user->getEmail(), "type" => "google", "userData" => "{}"], GoogleMailbox::class),
                ];
            }),
        ];

        /** @var EmailScannerApi $emailScannerApi */
        $emailScannerApi = $I->stubMakeEmpty(EmailScannerApiStub::class, $expectedCalls);

        /** @var MailboxManager $mm */
        $mm = $I->createInstance(MailboxManager::class, [
            'emailScannerApi' => $emailScannerApi,
            'mailboxFinder' => new MailboxFinder($emailScannerApi),
            'userOAuthRepository' => $I->grabService(UserOAuthRepository::class),
        ]);

        $mm->delete($user, 12345);

        if ($example['haveOauthRecord']) {
            $I->assertEquals(1, $I->grabFromDatabase("UserOAuth", "DeclinedMailboxAccess", ["UserID" => $userId]));
        }

        $emailScannerApi->__phpunit_getInvocationHandler()->verify();
    }

    private function dataProvider(): array
    {
        return [
            ['haveOauthRecord' => false],
            ['haveOauthRecord' => true],
        ];
    }
}
