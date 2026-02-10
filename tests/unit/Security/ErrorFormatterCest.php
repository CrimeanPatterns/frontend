<?php

namespace AwardWallet\Tests\Unit\Security;

use AwardWallet\Common\Monolog\Formatter\HtmlFormatter;
use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use Codeception\Module\Mail;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 */
class ErrorFormatterCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\CodeGuy $I)
    {
        $I->setSource(Mail::SOURCE_SWIFT);
    }

    public function testPasswords(\CodeGuy $I)
    {
        $this->sendCritical($I, "here are password in context" . date("Y-m-d H:i:s"), ["DBPass1" => "aaa"], "thisissecret");
        $mail = $I->grabLastMailMessageBody();
        $I->assertNotEmpty($mail);
        $I->assertStringNotContainsString("thisissecret", $mail);
    }

    public function testIds(\CodeGuy $I)
    {
        $this->sendCritical($I, "here are some id in context " . date("Y-m-d H:i:s"), ["anycontext" => "allowedcontext"], ["accountId" => "123678", "ID" => 87654, "AccountID" => "393754835", "BadID" => "123x"]);
        $body = $I->grabLastMailMessageBody();
        $I->assertStringContainsString("allowedcontext", $body);
        $I->assertStringContainsString("87654", $body);
        $I->assertStringContainsString("393754835", $body);
        $I->assertStringContainsString("393754835", $body);
        $I->assertStringNotContainsString("123x", $body);
    }

    public function testServer(\CodeGuy $I)
    {
        $keys = HtmlFormatter::SERVER_KEYS_ALLOWED;

        foreach ($keys as $key) {
            $_SERVER[$key] = sha1($key);
        }

        $_SERVER['SOMEOTHER_KEY'] = 'SOMEVAL';

        $I->grabService(LoggerInterface::class)->critical("here are some server " . date("Y-m-d H:i:s"));
        $body = $I->grabLastMailMessageBody();

        foreach ($keys as $key) {
            $I->assertStringContainsString($key, $body);
            $I->assertStringContainsString(sha1($key), $body);
        }
        $I->assertStringNotContainsString("SOMEOTHER_KEY", $body);
        $I->assertStringNotContainsString("SOMEVAL", $body);
    }

    public function testPost(\CodeGuy $I)
    {
        $_POST = [
            'UserID' => 2110,
            'Login' => 'myname',
            'Pass' => 'mypass',
        ];

        $I->grabService(LoggerInterface::class)->critical("here are some post " . date("Y-m-d H:i:s"));
        $body = $I->grabLastMailMessageBody();
        $I->assertStringContainsString('UserID', $body);
        $I->assertStringContainsString('2110', $body);
        $I->assertStringContainsString('Login', $body);
        $I->assertStringNotContainsString('myname', $body);
        $I->assertStringContainsString('Pass', $body);
        $I->assertStringNotContainsString('mypass', $body);
    }

    public function testSession(\CodeGuy $I)
    {
        $_SESSION = [
            'UserID' => 2110,
            'Login' => 'myname',
            'Pass' => 'mypass',
        ];

        $I->grabService(LoggerInterface::class)->critical("here are some session " . date("Y-m-d H:i:s"));
        $body = $I->grabLastMailMessageBody();
        $I->assertStringContainsString('UserID', $body);
        $I->assertStringContainsString('2110', $body);
        $I->assertStringContainsString('Login', $body);
        $I->assertStringNotContainsString('myname', $body);
        $I->assertStringContainsString('Pass', $body);
        $I->assertStringNotContainsString('mypass', $body);
    }

    public function testWarning(\CodeGuy $I)
    {
        $this->throwWarning("Somevalue");
        $body = $I->grabLastMailMessageBody();
        $I->assertStringContainsString('some warning', $body);
        $I->assertStringContainsString('123', $body);
        $I->assertStringNotContainsString('Somevalue', $body);
    }

    public function testCyclicDependency(\CodeGuy $I)
    {
        $args = new \stdClass();
        $a = new \stdClass();
        $b = new \stdClass();
        $b->aLink = &$a;
        $b->AccountID = 123;
        $a->bLink = &$b;
        $a->AccountID = 456;
        $args->a = $a;
        $args->b = $b;
        $result = TraceProcessor::filterArguments($args, 1);
        $I->assertNotEmpty($result);
    }

    public function testBasicTypesFiltering()
    {
        $this->assertFiltering(
            [true, 1, 1.44, '1', NAN, INF, '<string>', '<resource>', '<stdClass>', '<array>', null],
            TraceProcessor::filterArguments([true, 1, 1.44, '1', NAN, INF,  'some string', fopen('data://text/plain,some resource', 'r'), new \stdClass(), ['some' => 'foo', 'bar' => 'value'], null])
        );
    }

    public function testNestedLevelFiltering()
    {
        $this->assertFiltering(
            [
                'some key' => [
                    'some nested key' => 100,
                    '1005001' => '11',
                    '1005002' => '<string>',
                    'some key' => '<array>',
                ],
                'some zero level' => '1',
            ],
            TraceProcessor::filterArguments(
                [
                    'some key' => [
                        'some nested key' => 100,
                        '1005001' => '11',
                        '1005002' => 'some string',
                        'some key' => [
                            'some double nested key',
                        ],
                    ],
                    'some zero level' => '1',
                ],
                1
            )
        );
    }

    public function testObjectFiltering()
    {
        $object = new TestClass();
        $object->runtimeProp1 = 1;
        $object->runtimeProp2 = 'some string 2';

        $this->assertFiltering(
            [
                '_class_' => TestClass::class,
                'publicDefinedProp1' => 1,
                'protectedDefinedProp2' => '<array>',
                'privateDefinedProp3' => '<string>',
                'runtimeProp1' => 1,
                'runtimeProp2' => '<string>',
            ],
            TraceProcessor::filterArguments($object, 0, 0, [], false)
        );
    }

    public function testNestedObjectFiltering()
    {
        $object = new TestClass();
        $object->runtimeProp1 = 1;
        $object->runtimeProp2 = 'some string 2';
        $object->runtimeProp3 = $object;

        $this->assertFiltering(
            [
                [
                    '_class_' => TestClass::class,
                    'publicDefinedProp1' => 1,
                    'protectedDefinedProp2' => '<array>',
                    'privateDefinedProp3' => '<string>',
                    'runtimeProp1' => 1,
                    'runtimeProp2' => '<string>',
                    'runtimeProp3' => '<' . TestClass::class . '>',
                ],
                '<ref:' . TestClass::class . '>',
            ],
            TraceProcessor::filterArguments([$object, $object], 1, 0, [], false)
        );
    }

    public function testSafeStringsFiltering()
    {
        $this->assertFiltering(
            [
                'empty string' => '',
                'host' => '<string>',
                'email' => 'yandex+email@google.com',
                'ipv4' => '127.0.0.1',
                'ipv6_short' => '::1',
                'ipv6_long' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            ],
            TraceProcessor::filterArguments(
                [
                    'empty string' => '',
                    'host' => 'some.hos.ru',
                    'email' => 'yandex+email@google.com',
                    'ipv4' => '127.0.0.1',
                    'ipv6_short' => '::1',
                    'ipv6_long' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
                ]
            )
        );
    }

    public function testDevNotification(\CodeGuy $I)
    {
        /** @var Logger $logger */
        $logger = $I->grabService(LoggerInterface::class);
        $logger->pushProcessor(function (array $record) {
            $record["extra"]["requestId"] = "xxx";
            $record["extra"]["accountId"] = 1234;

            return $record;
        });
        $logger->info("hello");
        $logger->notice("some notice");
        $logger->debug("some debug");
        $logger->alert("Some text from parser", ["DevNotification" => true, "EmailSubject" => "Marriott failed"]);
        $body = $I->grabLastMailMessageBody();
        $I->assertStringContainsString("Marriott failed", $body);
    }

    protected function assertFiltering(array $expected, array $filtered)
    {
        assertEquals(var_export($expected, true), var_export($filtered, true));
    }

    private function sendCritical(\CodeGuy $I, $message, $context, $extra)
    {
        $I->grabService(LoggerInterface::class)->critical($message, $context);
    }

    private function throwWarning($extra)
    {
        DieTrace("some warning", false, 0, ["Jessica" => "Bob", "SomeID" => 123]);
    }
}

class TestClass
{
    public $publicDefinedProp1 = 1;
    protected $protectedDefinedProp2 = ['some value'];
    private $privateDefinedProp3 = 'some string';
}
