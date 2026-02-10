<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Booking;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MobileBundle\View\Booking\Messages\MessagesFormatter;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Scenario;
use Symfony\Component\Routing\RouterInterface;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-functional
 * @group mobile
 */
class MessagesCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;

    /**
     * @var int
     */
    private $abRequestId;

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var int
     */
    private $bookerId;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');

        $this->bookerId = $I->createBusinessUserWithBookerInfo();
        $this->abRequestId = $I->createAbRequest([
            'UserID' => $this->user->getUserid(),
            'BookerUserID' => $this->bookerId,
        ]);
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.16.0+b100500');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);

        $this->abRequestId = null;
        $this->bookerId = null;
    }

    public function loadAllUnread(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $this->login($I);
        $I->executeQuery("update AbRequest set CreateDate = adddate(now(), interval -3 hour) where AbRequestID = {$this->abRequestId}");
        $messages = $this->addMessages($I, $this->abRequestId, $this->bookerId, 15); // date = now
        $I->markAbMessageRead($this->abRequestId, $this->user->getUserid(), strtotime('-1 hour'));

        // 2 first messages in request is read
        foreach (array_slice($messages, 0, 2) as $message) {
            $I->executeQuery("update AbMessage set CreateDate = adddate(now(), interval -2 hour) where AbMessageID = {$message}");
        }

        $I->sendGET('/m/api/data');
        assertCount(13, $responseMessages = $I->grabDataFromJsonResponse('booking.requests.0.messages'));

        $expectedReads = array_pad([], 13, false);
        assertEquals(
            $expectedReads,
            array_map(function ($m) { return $m['readed']; }, $responseMessages),
            'Invalid read\unread status'
        );
    }

    public function loadAllUnreadPlusRead(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $this->login($I);
        $messages = $this->addMessages($I, $this->abRequestId, $this->bookerId, 15); // date = now
        $I->markAbMessageRead($this->abRequestId, $this->user->getUserid(), strtotime('- 1 hour'));

        // 12 first messages is read
        foreach (array_slice($messages, 0, 12) as $message) {
            $I->executeQuery("update AbMessage set CreateDate = adddate(now(), interval -2 hour) where AbMessageID = {$message}");
        }

        $I->sendGET('/m/api/data');
        assertCount(10, $responseMessages = $I->grabDataFromJsonResponse('booking.requests.0.messages'));
        $expectedReads = array_merge(
            array_pad([], 7, true), // first 7 in chunk read
            array_pad([], 3, false)   // last 3 in chunk unread
        );
        assertEquals(
            $expectedReads,
            array_map(function ($m) { return $m['readed']; }, $responseMessages),
            'Invalid read\unread status'
        );
    }

    public function testMessagesChunk(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $this->login($I);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));
        $message = $I->grabFromDatabase("AbBookerInfo", "AutoReplyMessage", [
            'UserID' => $this->bookerId,
        ]);
        $I->assertNotEmpty($message);

        $this->addMessages($I, $this->abRequestId, $this->bookerId, 12, true);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        $I->assertEquals(10, count($messages));

        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
            'oldestSeenMessageId' => $messages[0]['id'],
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        $I->assertEquals(3, count($messages));
    }

    public function testReadUnreadMessages(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $I->executeQuery("update AbRequest set CreateDate = adddate(now(), interval -2 hour) where AbRequestID = {$this->abRequestId}");
        $lastReadMessageDate = time();
        $messages[] = $I->createAbMessage($this->abRequestId, $this->bookerId, "Zzz", $lastReadMessageDate, true);
        $lastMessageDate = $lastReadMessageDate + 1;
        $messages[] = $I->createAbMessage($this->abRequestId, $this->bookerId, "Zzz", $lastMessageDate, true);
        $markId = $I->markAbMessageRead($this->abRequestId, $this->user->getUserid(), $lastReadMessageDate);

        $this->login($I);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));

        $messages = $I->grabDataFromJsonResponse("messages");
        $I->assertEquals(3, count($messages));
        $I->assertEquals([true, true, false], array_column($messages, 'readed'));

        $I->updateInDatabase("AbRequestMark", ["ReadDate" => date("Y-m-d H:i:s", $lastMessageDate)], ["AbRequestMarkID" => $markId]);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        $I->assertEquals(3, count($messages));
        $I->assertEquals([true, true, true], array_column($messages, 'readed'));
    }

    public function test404GetMessagesChunkRequest(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $this->login($I);
        $_SERVER['REMOTE_ADDR'] = null;
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => time(),
        ]));
        $I->seeResponseCodeIs(404);
    }

    public function testMarkRead(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $I->executeQuery("update AbRequest set CreateDate = adddate(now(), interval -3 hour) where AbRequestID = {$this->abRequestId}");
        $lastReadMessageDate = strtotime("-2 hour");
        $I->createAbMessage($this->abRequestId, $this->bookerId, "Zzz", $lastReadMessageDate);
        $I->createAbMessage($this->abRequestId, $this->bookerId, "Xxx", strtotime("-1 hour"));
        $I->createAbMessage($this->abRequestId, $this->bookerId, "Yyy", strtotime("-10 minute"), false);
        $I->markAbMessageRead($this->abRequestId, $this->user->getUserid(), $lastReadMessageDate);

        $this->login($I);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        assertEquals([true, true, false, false], array_column($messages, 'readed'));

        $I->sendGET($this->router->generate('awm_newapp_booking_messages_read', [
            'abRequest' => $this->abRequestId,
            'lastReadId' => $messages[1]['id'],
        ]));
        $I->seeResponseCodeIs(200);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        assertEquals([true, true, false, false], array_column($messages, 'readed'));

        $I->sendGET($this->router->generate('awm_newapp_booking_messages_read', [
            'abRequest' => $this->abRequestId,
            'lastReadId' => $messages[2]['id'],
        ]));
        $I->seeResponseCodeIs(200);

        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        assertEquals([true, true, true, false], array_column($messages, 'readed'));
    }

    public function test404MarkRead(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $this->login($I);
        $_SERVER['REMOTE_ADDR'] = null;
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_read', [
            'abRequest' => time(),
            'lastReadId' => time(),
        ]));
        $I->seeResponseCodeIs(404);
    }

    public function testAutoReplyMessage(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $message = $I->grabFromDatabase("AbBookerInfo", "AutoReplyMessage", [
            'UserID' => $this->bookerId,
        ]);
        $I->assertNotEmpty($message);
        // 1 message
        $this->login($I);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        $I->assertEquals(1, count($messages));
        $I->assertTrue(isset($messages[0]['id']));

        // 5 messages
        $this->addMessages($I, $this->abRequestId, $this->bookerId, 5, true);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        $I->assertEquals(6, count($messages));
        $I->assertTrue(isset($messages[0]['id']));
        $I->assertNotEmpty($messages[5]['id']);

        // +5 messages
        $this->addMessages($I, $this->abRequestId, $this->bookerId, 5, true);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        $I->assertEquals(10, count($messages));
        $I->assertNotEmpty($messages[0]['id']);
        $I->assertNotEmpty($messages[9]['id']);
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_chunk', [
            'abRequest' => $this->abRequestId,
            'oldestSeenMessageId' => $messages[0]['id'],
        ]));
        $messages = $I->grabDataFromJsonResponse("messages");
        $I->assertEquals(1, count($messages));
        $I->assertTrue(isset($messages[0]['id']));
    }

    public function testCreateMessage(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $requestId = $this->abRequestId;
        $this->login($I);
        $I->saveCsrfToken();

        // 404
        $I->sendPUT($this->router->generate('awm_newapp_booking_messages_add', [
            'abRequest' => time(),
        ]));
        $I->seeResponseCodeIs(404);

        // PUT Method
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_add', [
            'abRequest' => time(),
        ]));
        $I->seeResponseCodeIs(405);

        $I->sendPUT($this->router->generate('awm_newapp_booking_messages_add', [
            'abRequest' => $requestId,
        ]));

        $I->seeResponseJsonMatchesJsonPath("$.error");
        $I->assertStringContainsString('This value should not be blank', $I->grabDataFromJsonResponse("error"));

        $I->sendPUT($this->router->generate('awm_newapp_booking_messages_add', [
            'abRequest' => $requestId,
        ]), ['message' => "  Test<br> test\n\n12345<script>alert(123);</script>     "]);
        $I->dontSeeResponseJsonMatchesJsonPath("$.error");
        $I->seeResponseContainsJson(["success" => true]);
        $message = $I->grabDataFromJsonResponse("message");
        $I->seeInDatabase("AbMessage", [
            'AbMessageID' => $message['id'],
        ]);
        $I->assertEquals("Test&lt;br&gt; test<br />\n<br />\n12345&lt;script&gt;alert(123);&lt;/script&gt;", $I->grabFromDatabase("AbMessage", "Post", [
            'AbMessageID' => $message['id'],
        ]));
        $I->assertEquals("Test&lt;br&gt; test<br /><br />12345&lt;script&gt;alert(123);&lt;/script&gt;", $message['body']);
        $I->assertTrue($message['readed']);
        $I->assertEquals("userText", $message['type']);
    }

    public function testEditMessage(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $requestId = $this->abRequestId;
        $this->login($I);
        $I->saveCsrfToken();

        // 404
        $I->sendPOST($this->router->generate('awm_newapp_booking_messages_edit', [
            'abRequest' => time(),
            'abMessage' => time(),
        ]));
        $I->seeResponseCodeIs(404);

        $I->sendPOST($this->router->generate('awm_newapp_booking_messages_edit', [
            'abRequest' => $requestId,
            'abMessage' => time(),
        ]));
        $I->seeResponseCodeIs(404);

        // POST Method
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_edit', [
            'abRequest' => $requestId,
            'abMessage' => time(),
        ]));
        $I->seeResponseCodeIs(405);

        $I->sendPUT($this->router->generate('awm_newapp_booking_messages_add', [
            'abRequest' => $requestId,
        ]), ['message' => "  Test<br> test\n\n12345<script>alert(123);</script>     "]);
        $I->dontSeeResponseJsonMatchesJsonPath("$.error");
        $I->seeResponseContainsJson(["success" => true]);
        $message = $I->grabDataFromJsonResponse("message");
        $messageId = $message['id'];

        $I->sendPOST($this->router->generate('awm_newapp_booking_messages_edit', [
            'abRequest' => $requestId,
            'abMessage' => $messageId,
        ]), ['message' => '']);

        $I->seeResponseJsonMatchesJsonPath("$.error");
        $I->assertStringContainsString('This value should not be blank', $I->grabDataFromJsonResponse("error"));

        $I->sendPOST($this->router->generate('awm_newapp_booking_messages_edit', [
            'abRequest' => $requestId,
            'abMessage' => $messageId,
        ]), ['message' => "Test<br> test\n\n12345"]);
        $I->dontSeeResponseJsonMatchesJsonPath("$.error");
        $I->seeResponseContainsJson(["success" => true]);
        $message = $I->grabDataFromJsonResponse("message");
        $I->seeInDatabase("AbMessage", [
            'AbMessageID' => $messageId,
        ]);
        $I->assertEquals("Test&lt;br&gt; test<br />\n<br />\n12345", $I->grabFromDatabase("AbMessage", "Post", [
            'AbMessageID' => $messageId,
        ]));
        $I->assertEquals("Test&lt;br&gt; test<br /><br />12345", $message['body']);
        $I->assertTrue($message['readed']);
        $I->assertEquals("userText", $message['type']);
    }

    public function testDeleteMessage(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $requestId = $this->abRequestId;
        $this->login($I);
        $I->saveCsrfToken();

        // 404
        $I->sendDELETE($this->router->generate('awm_newapp_booking_messages_delete', [
            'abRequest' => time(),
            'abMessage' => time(),
        ]));
        $I->seeResponseCodeIs(404);

        $I->sendDELETE($this->router->generate('awm_newapp_booking_messages_delete', [
            'abRequest' => $requestId,
            'abMessage' => time(),
        ]));
        $I->seeResponseCodeIs(404);

        // DELETE Method
        $I->sendGET($this->router->generate('awm_newapp_booking_messages_delete', [
            'abRequest' => $requestId,
            'abMessage' => time(),
        ]));
        $I->seeResponseCodeIs(405);

        $I->sendPUT($this->router->generate('awm_newapp_booking_messages_add', [
            'abRequest' => $requestId,
        ]), ['message' => "12345"]);
        $I->dontSeeResponseJsonMatchesJsonPath("$.error");
        $I->seeResponseContainsJson(["success" => true]);
        $message = $I->grabDataFromJsonResponse("message");
        $messageId = $message['id'];

        $I->sendDELETE($this->router->generate('awm_newapp_booking_messages_delete', [
            'abRequest' => $requestId,
            'abMessage' => $messageId,
        ]));
        $I->dontSeeResponseJsonMatchesJsonPath("$.error");
        $I->seeResponseContainsJson(["success" => true]);
        $I->dontSeeInDatabase("AbMessage", [
            "AbMessageID" => $messageId,
        ]);
    }

    public function testSyncMessages(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $this->login($I);
        $messages = $this->addMessages($I, $this->abRequestId, $this->bookerId, 5, true);

        $I->sendPOST(
            $this->router->generate('awm_newapp_booking_messages_sync', ['abRequest' => $this->abRequestId]),
            ['messages' => []]
        );

        $messageIds = [];

        foreach ($I->grabDataFromJsonResponse('messages') as $message) {
            $messageIds[$message['id']] = $message['internalDate'];
        }

        $I->sendPOST(
            $this->router->generate('awm_newapp_booking_messages_sync', ['abRequest' => $this->abRequestId]),
            ['messages' => $messageIds]
        );
        $I->seeResponseContainsJson(['success' => true]);
        $I->assertEquals(5, count($I->grabDataFromJsonResponse('messages')));

        // some message absents in client model
        $staleMessages = $messageIds;
        unset(
            $staleMessages[$messages[0]],
            $staleMessages[$messages[1]],
            $staleMessages[$messages[3]]
        );
        $I->sendPOST(
            $this->router->generate('awm_newapp_booking_messages_sync', ['abRequest' => $this->abRequestId]),
            ['messages' => $staleMessages]
        );
        $I->seeResponseContainsJson(['success' => true]);
        $I->assertEquals(3, count($responseMessages = $I->grabDataFromJsonResponse('messages')));
        // check
        assertEquals([
            ['id' => $messages[2]],
            ['id' => $messages[3], 'body' => "3"],
            ['id' => $messages[4]],
        ], $this->getSyncedData($responseMessages));

        // some message absents on server
        $I->executeQuery("delete from AbMessage where AbMessageID = {$messages[3]}");
        $I->sendPOST(
            $this->router->generate('awm_newapp_booking_messages_sync', ['abRequest' => $this->abRequestId]),
            ['messages' => $messageIds]
        );
        $I->assertEquals(4, count($I->grabDataFromJsonResponse('messages')));

        // test auto reply
        $messageIds[0] =
            strtotime($I->grabFromDatabase("AbRequest", "CreateDate", ["AbRequestID" => $this->abRequestId])) +
            MessagesFormatter::AUTO_REPLY_MESSAGE_INTERNAL_DATE_OFFSET +
            MessagesFormatter::MESSAGE_INTERNAL_DATE_OFFSET;

        $I->sendPOST(
            $this->router->generate('awm_newapp_booking_messages_sync', ['abRequest' => $this->abRequestId]),
            ['messages' => $messageIds]
        );
        $I->assertEquals(5, count($responseMessages = $I->grabDataFromJsonResponse('messages')));
        assertEquals([
            ['id' => 0],
            ['id' => $messages[0]],
            ['id' => $messages[1]],
            ['id' => $messages[2]], // 3 was removed
            ['id' => $messages[4]],
        ], $this->getSyncedData($responseMessages));

        // test empty client
        $I->sendPOST(
            $this->router->generate('awm_newapp_booking_messages_sync', ['abRequest' => $this->abRequestId]),
            ['messages' => []]
        );
        $I->assertEquals(4, count($I->grabDataFromJsonResponse('messages')));
    }

    /**
     * @return array
     */
    private function getSyncedData(array $responseMessages)
    {
        return array_map(
            function ($message) {
                return array_intersect_key($message, array_flip(['id', 'body']));
            },
            $responseMessages
        );
    }

    /**
     * @param bool $fromBooker
     * @return AbMessage[]
     */
    private function addMessages(\TestSymfonyGuy $I, int $requestId, $userId, $countMessages, $fromBooker = true): array
    {
        $messages = [];

        for ($i = 0; $i < $countMessages; $i++) {
            $messages[] = $I->createAbMessage($requestId, $userId, $i, null, $fromBooker);
        }

        return $messages;
    }

    private function login(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate('awm_new_login_status', [
            '_switch_user' => $this->user->getLogin(),
        ]));
    }
}
