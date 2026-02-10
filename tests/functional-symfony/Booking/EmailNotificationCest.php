<?php

namespace AwardWallet\Tests\FunctionalSymfony\Booking;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use Codeception\Example;
use Codeception\Module\Aw;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;

/**
 * @group booking
 * @group frontend-functional
 */
class EmailNotificationCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var EntityManager
     */
    protected $em;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService("router");
        $this->em = $I->grabService("doctrine")->getManager();
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        $this->em = null;

        parent::_after($I);
    }

    /**
     * @dataProvider newBookingRequestProvider
     */
    public function testNewBookingRequest(\TestSymfonyGuy $I, Example $example)
    {
        [$booker, $admin] = $this->getBooker($I, $example);
        $this->setUserDefaultBooker($I, $this->user, $booker);

        try {
            $route = $this->router->generate('aw_booking_add_index');
            $I->amOnPage($route);
            $I->saveCsrfToken();

            $data = $this->getRequestFormData();

            $I->sendPOST($route, $data);

            $body = 'Contact information';
            $this->assertEmail($I, $example['toAdmin'], $admin->getEmail(), null, $body);
            $this->assertEmail($I, $example['toAuthor'], $data['booking_request']['ContactEmail'], null, $body);
            $this->assertEmail($I, $example['toCommonBooker'], $booker->getBookerInfo()->getFromEmail(), null, $body);
        } finally {
            $this->removeBooker($I, $example, $booker, $admin);
        }
    }

    /**
     * @dataProvider bookingShareAccountsProvider
     */
    public function testBookingShareAccounts(\TestSymfonyGuy $I, Example $example)
    {
        [$booker, $admin] = $this->getBooker($I, $example);
        $request = $this->getRequest($I, $example, $this->user, $booker, $admin);

        try {
            $this->openRequestView($I, $request, $admin);
            $I->sendPOST($this->router->generate('aw_booking_view_clarify', [
                'id' => $request->getAbRequestID(),
            ]), [
                'accounts' => [
                    [
                        'providerId' => Aw::TEST_PROVIDER_ID,
                    ],
                ],
            ]);

            $body = 'requesting access to your account';
            $this->assertEmail($I, $example['toAdmin'], $admin->getEmail(), null, $body);
            $this->assertEmail($I, $example['toAuthor'], $request->getContactEmail(), null, $body);
            $this->assertEmail($I, $example['toCommonBooker'], $booker->getBookerInfo()->getFromEmail(), null, $body);
        } finally {
            $this->removeBooker($I, $example, $booker, $admin);
            $this->removeRequest($request);
        }
    }

    /**
     * @dataProvider bookingRespondToBookerProvider
     */
    public function testBookingRespondToBooker(\TestSymfonyGuy $I, Example $example)
    {
        [$booker, $admin] = $this->getBooker($I, $example);
        $request = $this->getRequest($I, $example, $this->user, $booker, $admin);

        try {
            $this->openRequestView($I, $request);
            $I->sendPOST($this->router->generate('aw_booking_message_ajaxaddmessage', [
                'id' => $request->getAbRequestID(),
            ]), ['booking_request_message' => ['Post' => 'Test message']]);
            $I->seeResponseCodeIs(200);

            $body = 'responded to your booking request';
            $this->assertEmail($I, $example['toAdmin'], $admin->getEmail(), null, $body);
            $this->assertEmail($I, $example['toAuthor'], $request->getContactEmail(), null, $body);
            $this->assertEmail($I, $example['toCommonBooker'], $booker->getBookerInfo()->getFromEmail(), null, $body);
        } finally {
            $this->removeBooker($I, $example, $booker, $admin);
            $this->removeRequest($request);
        }
    }

    /**
     * @dataProvider bookingRespondToUserProvider
     */
    public function testBookingRespondToUser(\TestSymfonyGuy $I, Example $example)
    {
        [$booker, $admin] = $this->getBooker($I, $example);
        $request = $this->getRequest($I, $example, $this->user, $booker, $admin);

        try {
            $this->openRequestView($I, $request, $admin);
            $I->sendPOST($this->router->generate('aw_booking_message_ajaxaddmessage', [
                'id' => $request->getAbRequestID(),
            ]), ['booking_request_message' => ['Post' => 'Test message']]);
            $I->seeResponseCodeIs(200);

            $body = 'responded to your booking request';
            $this->assertEmail($I, $example['toAdmin'], $admin->getEmail(), null, $body);
            $this->assertEmail($I, $example['toAuthor'], $request->getContactEmail(), null, $body);
            $this->assertEmail($I, $example['toCommonBooker'], $booker->getBookerInfo()->getFromEmail(), null, $body);
        } finally {
            $this->removeBooker($I, $example, $booker, $admin);
            $this->removeRequest($request);
        }
    }

    /**
     * @dataProvider bookingInvoiceProvider
     */
    public function testBookingInvoice(\TestSymfonyGuy $I, Example $example)
    {
        [$booker, $admin] = $this->getBooker($I, $example);
        $request = $this->getRequest($I, $example, $this->user, $booker, $admin);

        try {
            $this->openRequestView($I, $request, $admin);
            $I->sendPOST($this->router->generate('aw_booking_message_createinvoice', [
                'id' => $request->getAbRequestID(),
            ]), [
                'booking_request_invoice' => [
                    'Items' => [
                        [
                            'description' => 'XXX',
                            'quantity' => 1,
                            'price' => 10,
                            'discount' => 0,
                        ],
                        [
                            'description' => 'YYY',
                            'quantity' => 3,
                            'price' => 5,
                            'discount' => 10,
                        ],
                    ],
                    'Miles' => [
                        [
                            'CustomName' => 'Name1',
                            'Owner' => $request->getPassengers()->first()->getFullName(),
                            'Balance' => 10000,
                        ],
                    ],
                    '_token' => $I->grabTextFrom("//*[@name='booking_request_invoice[_token]']/@value"),
                ],
            ]);
            $I->seeResponseCodeIs(200);

            $body = 'generated you an invoice';
            $this->assertEmail($I, $example['toAdmin'], $admin->getEmail(), null, $body);
            $this->assertEmail($I, $example['toAuthor'], $request->getContactEmail(), null, $body);
            $this->assertEmail($I, $example['toCommonBooker'], $booker->getBookerInfo()->getFromEmail(), null, $body);
        } finally {
            $this->removeBooker($I, $example, $booker, $admin);
            $this->removeRequest($request);
        }
    }

    /**
     * @dataProvider bookingChangeStatusToBookerProvider
     */
    public function testBookingChangeStatusToBooker(\TestSymfonyGuy $I, Example $example)
    {
        [$booker, $admin] = $this->getBooker($I, $example);
        $request = $this->getRequest($I, $example, $this->user, $booker, $admin);

        try {
            $this->openRequestView($I, $request);
            $I->sendPOST($this->router->generate('aw_booking_view_cancel', [
                'id' => $request->getAbRequestID(),
            ]));
            $I->seeResponseCodeIs(200);

            $body = 'status of the following award booking request has been changed';
            $this->assertEmail($I, $example['toAdmin'], $admin->getEmail(), null, $body);
            $this->assertEmail($I, $example['toAuthor'], $request->getContactEmail(), null, $body);
            $this->assertEmail($I, $example['toCommonBooker'], $booker->getBookerInfo()->getFromEmail(), null, $body);
        } finally {
            $this->removeBooker($I, $example, $booker, $admin);
            $this->removeRequest($request);
        }
    }

    /**
     * @dataProvider bookingChangeStatusToUserProvider
     */
    public function testBookingChangeStatusToUser(\TestSymfonyGuy $I, Example $example)
    {
        [$booker, $admin] = $this->getBooker($I, $example);
        $request = $this->getRequest($I, $example, $this->user, $booker, $admin);

        try {
            $this->openRequestView($I, $request, $admin);
            $I->sendPOST($this->router->generate('aw_booking_view_cancel', [
                'id' => $request->getAbRequestID(),
            ]));
            $I->seeResponseCodeIs(200);

            $body = 'status of the following award booking request has been changed';
            $this->assertEmail($I, $example['toAdmin'], $admin->getEmail(), null, $body);
            $this->assertEmail($I, $example['toAuthor'], $request->getContactEmail(), null, $body);
            $this->assertEmail($I, $example['toCommonBooker'], $booker->getBookerInfo()->getFromEmail(), null, $body);
        } finally {
            $this->removeBooker($I, $example, $booker, $admin);
            $this->removeRequest($request);
        }
    }

    /**
     * @dataProvider acceptBookingInvoiceProvider
     */
    public function testAcceptBookingInvoice(\TestSymfonyGuy $I, Example $example)
    {
        [$booker, $admin] = $this->getBooker($I, $example);
        $request = $this->getRequest($I, $example, $this->user, $booker, $admin);

        try {
            $messageId = $I->haveInDatabase('AbMessage', [
                'RequestID' => $request->getAbRequestID(),
                'UserID' => $admin->getUserid(),
                'FromBooker' => 1,
            ]);
            $I->haveInDatabase('AbInvoice', [
                'Status' => AbInvoice::STATUS_UNPAID,
                'PaymentType' => AbInvoice::PAYMENTTYPE_CHECK,
                'MessageID' => $messageId,
            ]);
            $this->openRequestView($I, $request);
            $I->sendPOST($this->router->generate('aw_booking_payment_by_check', [
                'id' => $request->getAbRequestID(),
            ]));
            $I->seeResponseCodeIs(200);

            $body = 'Check has been sent for booking request';
            $this->assertEmail($I, $example['toAdmin'], $admin->getEmail(), null, $body);
            $this->assertEmail($I, $example['toAuthor'], $request->getContactEmail(), null, $body);
            $this->assertEmail($I, $example['toCommonBooker'], $booker->getBookerInfo()->getFromEmail(), null, $body);
        } finally {
            $this->removeBooker($I, $example, $booker, $admin);
            $this->removeRequest($request);
        }
    }

    /**
     * @dataProvider changeRequestToBookerProvider
     */
    public function testChangeRequestToBooker(\TestSymfonyGuy $I, Example $example)
    {
        [$booker, $admin] = $this->getBooker($I, $example);
        $request = $this->getRequest($I, $example, $this->user, $booker, $admin);

        try {
            $I->amOnPage($this->router->generate('aw_booking_add_edit', [
                'id' => $request->getAbRequestID(),
            ]));
            $I->seeResponseCodeIs(200);
            $I->saveCsrfToken();
            $I->sendPOST($this->router->generate('aw_booking_add_edit', [
                'id' => $request->getAbRequestID(),
            ]), $this->getRequestFormData());
            $I->seeResponseCodeIs(200);

            $body = 'Contact information';
            $this->assertEmail($I, $example['toAdmin'], $admin->getEmail(), null, $body);
            $this->assertEmail($I, $example['toAuthor'], $request->getContactEmail(), null, $body);
            $this->assertEmail($I, $example['toCommonBooker'], $booker->getBookerInfo()->getFromEmail(), null, $body);
        } finally {
            $this->removeBooker($I, $example, $booker, $admin);
            $this->removeRequest($request);
        }
    }

    protected function newBookingRequestProvider(): array
    {
        return [
            array_merge(
                static::toAdmin(true, false, false),
                static::toAuthor(true, false),
                static::toCommonBooker(false, false, false)
            ),
            array_merge(
                static::toAdmin(true, false, false),
                static::toAuthor(true, true),
                static::toCommonBooker(false, false, false)
            ),
            array_merge(
                static::toAdmin(true, false, false),
                static::toAuthor(true, false),
                static::toCommonBooker(false, true, false)
            ),
            array_merge(
                static::toAdmin(true, true, false),
                static::toAuthor(true, false),
                static::toCommonBooker(true, true, true)
            ),
            array_merge(
                static::toAdmin(true, false, false),
                static::toAuthor(true, false),
                static::toCommonBooker(false, false, true)
            ),
        ];
    }

    protected function bookingShareAccountsProvider(): array
    {
        return [
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, false),
                static::toCommonBooker(false, true, true)
            ),
            array_merge(
                static::toAdmin(false, true, true),
                static::toAuthor(true, true),
                static::toCommonBooker(false, false, true)
            ),
        ];
    }

    protected function bookingRespondToBookerProvider(): array
    {
        return [
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, true),
                static::toCommonBooker(true, false, true)
            ),
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, true),
                static::toCommonBooker(false, false, false)
            ),
            array_merge(
                static::toAdmin(false, false, true),
                static::toAuthor(false, false),
                static::toCommonBooker(false, true, false)
            ),
            array_merge(
                static::toAdmin(true, true, true),
                static::toAuthor(false, false),
                static::toCommonBooker(false, false, true)
            ),
            array_merge(
                static::toAdmin(true, true, true),
                static::toAuthor(false, false),
                static::toCommonBooker(true, true, true)
            ),
        ];
    }

    protected function bookingRespondToUserProvider(): array
    {
        return [
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, false),
                static::toCommonBooker(false, true, true)
            ),
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(true, true),
                static::toCommonBooker(false, true, true)
            ),
            array_merge(
                static::toAdmin(false, true, true),
                static::toAuthor(true, true),
                static::toCommonBooker(false, false, true)
            ),
        ];
    }

    protected function bookingInvoiceProvider(): array
    {
        return [
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, false),
                static::toCommonBooker(false, true, true)
            ),
            array_merge(
                static::toAdmin(false, true, true),
                static::toAuthor(true, true),
                static::toCommonBooker(false, false, true)
            ),
        ];
    }

    protected function bookingChangeStatusToBookerProvider(): array
    {
        return [
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, false),
                static::toCommonBooker(false, false, false)
            ),
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, false),
                static::toCommonBooker(true, false, true)
            ),
            array_merge(
                static::toAdmin(false, false, true),
                static::toAuthor(false, false),
                static::toCommonBooker(true, false, true)
            ),
            array_merge(
                static::toAdmin(true, true, true),
                static::toAuthor(false, false),
                static::toCommonBooker(false, false, true)
            ),
            array_merge(
                static::toAdmin(true, true, true),
                static::toAuthor(false, true),
                static::toCommonBooker(true, true, true)
            ),
        ];
    }

    protected function bookingChangeStatusToUserProvider(): array
    {
        return [
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, false),
                static::toCommonBooker(false, true, true)
            ),
            array_merge(
                static::toAdmin(false, true, true),
                static::toAuthor(true, true),
                static::toCommonBooker(false, true, true)
            ),
        ];
    }

    protected function acceptBookingInvoiceProvider(): array
    {
        return [
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, false),
                static::toCommonBooker(false, false, false)
            ),
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, true),
                static::toCommonBooker(true, false, true)
            ),
            array_merge(
                static::toAdmin(false, false, true),
                static::toAuthor(false, true),
                static::toCommonBooker(true, false, true)
            ),
            array_merge(
                static::toAdmin(true, true, true),
                static::toAuthor(false, true),
                static::toCommonBooker(false, false, true)
            ),
            array_merge(
                static::toAdmin(true, true, true),
                static::toAuthor(false, true),
                static::toCommonBooker(true, true, true)
            ),
        ];
    }

    protected function changeRequestToBookerProvider(): array
    {
        return [
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, false),
                static::toCommonBooker(false, false, false)
            ),
            array_merge(
                static::toAdmin(false, false, false),
                static::toAuthor(false, false),
                static::toCommonBooker(true, false, true)
            ),
            array_merge(
                static::toAdmin(false, false, true),
                static::toAuthor(false, false),
                static::toCommonBooker(true, false, true)
            ),
            array_merge(
                static::toAdmin(true, true, true),
                static::toAuthor(false, false),
                static::toCommonBooker(false, false, true)
            ),
            array_merge(
                static::toAdmin(true, true, true),
                static::toAuthor(false, true),
                static::toCommonBooker(true, true, true)
            ),
        ];
    }

    private function assertEmail(\TestSymfonyGuy $I, bool $expected, $to, $subject = null, $content = null, $timeout = 5)
    {
        if ($expected) {
            $I->seeEmailTo($to, $subject, $content, $timeout);
        } else {
            $I->dontSeeEmailTo($to, $subject, $content, $timeout);
        }
    }

    private function openRequestView(\TestSymfonyGuy $I, AbRequest $request, ?Usr $admin = null)
    {
        if ($admin) {
            $this->logoutUser($I);
            $I->amOnSubdomain('business');
            $this->loginUser($I, $admin);
        }
        $I->amOnPage($this->router->generate('aw_booking_view_index', [
            'id' => $request->getAbRequestID(),
        ]));
        $I->see("Booking Request #");
        $I->saveCsrfToken();
    }

    /**
     * @return Usr[]
     */
    private function getBooker(\TestSymfonyGuy $I, Example $example): array
    {
        /** @var Usr $booker */
        $booker = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($I->createBusinessUserWithBookerInfo());
        $booker->getBookerInfo()->setFromEmail($example['FromEmail'] ? 'from@booker.com' : null);
        $this->em->flush();

        if ($example['DefaultBooker']) {
            $this->mockDefaultBooker($I, $booker);
        }

        $admin = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($I->createAwUser(null, null, [
            'Email' => sprintf('book%s@booker.bo', $I->grabRandomString(10)),
            'EmailBookingMessages' => $example['EmailBookingMessages'],
        ]));
        $I->createConnection($booker->getUserid(), $admin->getUserid(), true, null, [
            'AccessLevel' => ACCESS_ADMIN,
        ]);
        $I->createConnection($admin->getUserid(), $booker->getUserid(), true, null, [
            'AccessLevel' => ACCESS_NONE,
        ]);

        return [$booker, $admin];
    }

    private function removeBooker(\TestSymfonyGuy $I, Example $example, Usr $booker, Usr $admin)
    {
        $this->em->remove($booker);
        $this->em->remove($admin);
        $this->em->flush();
    }

    private function getRequest(\TestSymfonyGuy $I, Example $example, Usr $author, Usr $booker, Usr $admin): AbRequest
    {
        return $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find($I->createAbRequest([
            "UserID" => $author->getUserid(),
            "AssignedUserID" => $example['AssignedBooker'] ? $admin->getUserid() : null,
            "BookerUserID" => $booker->getUserid(),
            "SendMailUser" => (int) $example['SendMailUser'],
        ]));
    }

    private function removeRequest(AbRequest $request)
    {
        $this->em->remove($request);
        $this->em->flush();
    }

    private function setUserDefaultBooker(\TestSymfonyGuy $I, Usr $user, Usr $booker)
    {
        $I->updateInDatabase('Usr', ['DefaultBookerID' => $booker->getUserid()], ['UserID' => $user->getUserid()]);
    }

    private function mockDefaultBooker(\TestSymfonyGuy $I, Usr $newDefaultBooker)
    {
        $I->mockService(DefaultBookerParameter::class, $I->stubMake(DefaultBookerParameter::class, [
            'get' => $newDefaultBooker->getUserid(),
        ]));
    }

    private function getRequestFormData(): array
    {
        return [
            "booking_request" => [
                "ContactName" => "Billy Villy",
                "ContactPhone" => "123-456",
                "ContactEmail" => "addrequest@gmail.com",
                "PriorSearchResults" => [
                    "choice" => "-",
                ],
                "CabinFirst" => 1,
                "Passengers" => [
                    [
                        "FirstName" => "John",
                        "LastName" => "Popov",
                        "Birthday" => "1980-06-01",
                        "Gender" => "M",
                        "Nationality" => ["choice" => "US"],
                    ],
                    [
                        "FirstName" => "Vassily",
                        "LastName" => "Charlot",
                        "Birthday" => "1986-10-13",
                        "Gender" => "M",
                        "Nationality" => ["choice" => "US"],
                    ],
                ],
                "Segments" => [
                    [
                        "RoundTrip" => 2,
                        "Dep" => "Zombilend",
                        "Arr" => "Perm",
                        "DepDateIdeal" => date("Y-m-d", strtotime("+2 days")),
                        "ReturnDateIdeal" => date("Y-m-d", strtotime("+10 days")),
                        "RoundTripDaysIdeal" => 6,
                    ],
                ],
                "CustomPrograms" => [
                    [
                        "Name" => "Test Provider",
                        "Owner" => "John Popov",
                        "EliteStatus" => "",
                        "Balance" => "12345",
                    ],
                ],
            ],
        ];
    }

    private static function toAdmin(bool $result, bool $emailBooking, bool $assigned)
    {
        return [
            'toAdmin' => $result,
            'EmailBookingMessages' => $emailBooking,
            'AssignedBooker' => $assigned,
        ];
    }

    private static function toAuthor(bool $result, bool $sendMailUser)
    {
        return [
            'toAuthor' => $result,
            'SendMailUser' => $sendMailUser,
        ];
    }

    private static function toCommonBooker(bool $result, bool $defaultBooker, bool $fromEmail)
    {
        return [
            'toCommonBooker' => $result,
            'DefaultBooker' => $defaultBooker,
            'FromEmail' => $fromEmail,
        ];
    }
}
