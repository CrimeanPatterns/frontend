<?php

namespace AwardWallet\Tests\Unit\MainBundle\Scanner;

use AwardWallet\MainBundle\Scanner\MailboxStatusHelper;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\ObjectSerializer;
use Codeception\Example;
use Codeception\Stub;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Scanner\MailboxStatusHelper
 */
class MailboxStatusHelperCest
{
    /**
     * @dataProvider testStatusDataProvider
     */
    public function testStatus(Example $example, \TestSymfonyGuy $I)
    {
        $mailbox = ObjectSerializer::deserialize(json_decode(json_encode(['type' => $example['type'], 'state' => $example['state'], 'errorCode' => $example['errorCode']]), false), Mailbox::class);

        $translator = $I->stubMakeEmpty(TranslatorInterface::class, [
            'trans' => Stub\Expected::once(function ($key) use ($example, $I) {
                $I->assertEquals($example['transKey'], $key);

                return $example['transKey'] . 'Translated';
            }),
        ]);

        $helper = new MailboxStatusHelper($translator);
        $I->assertEquals($example['transKey'] . 'Translated', $helper->mailboxStatus($mailbox));

        $I->verifyMocks();
    }

    private function testStatusDataProvider()
    {
        return [
            [
                'type' => Mailbox::TYPE_IMAP,
                'state' => Mailbox::STATE_SCANNING,
                'errorCode' => null,
                'transKey' => 'mailbox.status.scanning',
            ],
            [
                'type' => Mailbox::TYPE_IMAP,
                'state' => Mailbox::STATE_ERROR,
                'errorCode' => Mailbox::ERROR_CODE_CONNECTION,
                'transKey' => 'mailbox.status.error.connection',
            ],
            [
                'type' => Mailbox::TYPE_IMAP,
                'state' => Mailbox::STATE_ERROR,
                'errorCode' => Mailbox::ERROR_CODE_AUTHENTICATION,
                'transKey' => 'mailbox.status.error.authentication',
            ],
            [
                'type' => Mailbox::TYPE_GOOGLE,
                'state' => Mailbox::STATE_ERROR,
                'errorCode' => Mailbox::ERROR_CODE_CONNECTION,
                'transKey' => 'mailbox.status.error.connection',
            ],
            // special case for oauth provider
            [
                'type' => Mailbox::TYPE_GOOGLE,
                'state' => Mailbox::STATE_ERROR,
                'errorCode' => Mailbox::ERROR_CODE_AUTHENTICATION,
                'transKey' => 'mailbox.status.error.connection-lost',
            ],
        ];
    }
}
