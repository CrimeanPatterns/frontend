<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\LogProcessor;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 */
class LogProcessorTest extends BaseTest
{
    public function testServiceName()
    {
        $processor = new LogProcessor('testService');
        $this->assertEquals($this->getRecord('test message', [], [
            'service' => 'testService',
        ]), $processor($this->getRecord('test message')));
    }

    public function testAddExtra()
    {
        $processor = new LogProcessor('testService');
        $processor->addExtraField('new', 5);
        $this->assertEquals($this->getRecord('test message', [], [
            'service' => 'testService',
            'new' => 5,
        ]), $processor($this->getRecord('test message')));
    }

    public function testMapping()
    {
        $processor = new LogProcessor(null, [], [
            Tripsegment::class => fn (Tripsegment $ts): string => $ts->getDepcode(),
        ]);
        $this->assertEquals($this->getRecord('test message, <null> XXX 7 <DateTime 2022-01-01 00:00:00> <object LogProcessor> <array {"a1":2,"a2":3}> {fake}', [
            'xxx' => 10,
        ]), $processor(
            $this->getRecord(
                'test message, {null} {ts} {number} {date} {obj} {arr} {fake}',
                [
                    'null' => null,
                    'ts' => (new Tripsegment())->setDepcode('XXX'),
                    'number' => 7,
                    'date' => new \DateTime('2022-01-01 00:00:00'),
                    'obj' => new LogProcessor(),
                    'arr' => ['a1' => 2, 'a2' => 3],
                    'xxx' => 10,
                ]
            )
        ));
        $this->assertEquals(
            $this->getRecord('test message <Provider abc>'),
            $processor(
                $this->getRecord(
                    'test message {provider}',
                    [
                        'provider' => (new Provider())->setCode('abc'),
                    ]
                )
            )
        );
    }

    public function testPrefixes()
    {
        $processor = new LogProcessor(null, [], [
            Usr::class => fn (Usr $user): string => $user->getLogin(),
        ], ['first', 'second', 'label:%s!yyy']);
        $this->assertEquals($this->getRecord('[hhh][5][label:1 2 3] test message', [
            'xxx' => 'test context',
        ]), $processor($this->getRecord('test message', [
            'second' => 5,
            'xxx' => 'test context',
            'yyy' => '1 2 3',
            'first' => (new Usr())->setLogin('hhh'),
        ])));
    }

    public function testBaseContext()
    {
        $processor = new LogProcessor(null, ['fff' => 300], [], ['fff']);
        $this->assertEquals($this->getRecord('[300] test message'), $processor($this->getRecord('test message')));
    }

    public function testPushContext()
    {
        $processor = new LogProcessor();
        $processor->pushContext(['yyy' => 5]);
        $this->assertEquals(
            $this->getRecord('test message 5'),
            $processor($this->getRecord('test message {yyy}'))
        );
        $this->assertEquals(
            $this->getRecord('test message <null>'),
            $processor($this->getRecord('test message {yyy}', ['yyy' => null]))
        );
        $processor->popContext();
        $this->assertEquals(
            $this->getRecord('test message {yyy}'),
            $processor($this->getRecord('test message {yyy}'))
        );
    }

    public function testMapDoctrineEntities()
    {
        $processor = new LogProcessor();
        $account = new Account();

        $this->assertEquals(
            $this->getRecord('test message <object Account>'),
            $processor($this->getRecord('test message {obj}', ['obj' => $account]))
        );

        $class = new \ReflectionClass(get_class($account));
        $property = $class->getProperty('accountid');
        $property->setAccessible(true);
        $property->setValue($account, 1);
        $this->assertEquals(
            $this->getRecord('test message <Account 1>'),
            $processor($this->getRecord('test message {obj}', ['obj' => $account]))
        );
    }

    private function getRecord(string $msg, array $context = [], array $extra = []): array
    {
        return [
            'message' => $msg,
            'context' => $context,
            'extra' => $extra,
        ];
    }
}
