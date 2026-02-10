<?php

use Codeception\Module\TestHelper;

/**
 * Class JsonPathGrabberTest.
 *
 * @covers \Codeception\Module\TestHelper::_parseJsonPath
 * @covers \Codeception\Module\TestHelper::_grapJsonPath
 * @group frontend-unit
 */
class JsonPathGrabberTest extends \AwardWallet\Tests\Unit\BaseTest
{
    private $grabberData = [
        0 => 1,
        1 => 100,
        40 => 500,
        'accounts' => [
            '123' => [
                'ID' => 10,
                'Access' => [
                    'eat' => true,
                    'kill' => false,
                    'punch' => true,
                ],
            ],
            '124' => [
                'ID' => 20,
                'Access' => [
                    'eat' => true,
                    'kill' => false,
                    'punch' => true,
                ],
            ],
            '125' => [
                'ID' => 30,
                'Access' => [
                    'eat' => true,
                    'kill' => false,
                    'punch' => true,
                ],
            ],
            '126' => [
                'ID' => 40,
                'Access' => [
                    'eat' => 100500,
                    'kill' => false,
                    'punch' => true,
                ],
            ],
            '127' => [
                'ID' => 50,
                'Access' => [
                    'eat' => true,
                    'kill' => false,
                    'punch' => true,
                ],
            ],
        ],
    ];

    /**
     * @dataProvider pathProvider
     */
    public function testParser($path, $expectedResult)
    {
        $this->assertEquals($expectedResult, TestHelper::_parseJsonPath($path));
    }

    public function pathProvider()
    {
        $data = [
            '' => [],
            'abc' => ['abc'],
            'abc.123' => ['abc', '123'],
            'abc.123.qwe' => ['abc', '123', 'qwe'],
            'abc\.123' => ['abc.123'],
            'abc\..123' => ['abc.', '123'],
            'abc\..\.123.qwe' => ['abc.', '.123', 'qwe'],
            '\.\.\.\..\.\.\.\.\.' => ['....', '.....'],
        ];

        foreach ($data as $path => $expected) {
            yield [$path, $expected];
        }
    }

    /**
     * @dataProvider invalidPathProvider
     */
    public function testParserInvalidPath($path)
    {
        $this->expectException(\RuntimeException::class);
        TestHelper::_parseJsonPath($path);
    }

    public function invalidPathProvider()
    {
        $data = [
            '.',
            'abc.',
            'abc.def.',
            '..',
            '.a.',
            '...',
            '.\..',
            '..abc.',
        ];

        foreach ($data as $path) {
            yield [$path];
        }
    }

    /**
     * @dataProvider aggregationProvider
     */
    public function testParserAggregation($path, $expected)
    {
        $this->assertEquals($expected, TestHelper::_parseJsonPath($path));
    }

    public function aggregationProvider()
    {
        $data = [
            '*' => [],
            '*.*' => [[], []],
            '*.*.*' => [[], [], []],
            'ab\.c\*.*.qwe.*.\*' => ['ab.c*', [], 'qwe', [], '*'],
        ];

        foreach ($data as $path => $expected) {
            yield [$path, $expected];
        }
    }

    /**
     * @dataProvider invalidAggregationProvider
     */
    public function testParserInvalidAggregation($path)
    {
        $this->expectException(\RuntimeException::class);
        TestHelper::_parseJsonPath($path);
    }

    public function invalidAggregationProvider()
    {
        $data = [
            's*.sdf',
            '*s.asd',
            's.*sdf',
            's.sdf*',
            '**.abc.*',
        ];

        foreach ($data as $path) {
            yield [$path];
        }
    }

    /**
     * @dataProvider grabberProvider
     */
    public function testGrabber($path, $expected)
    {
        $this->assertEquals($expected, TestHelper::_grabJsonPath($this->grabberData, TestHelper::_parseJsonPath($path)));
    }

    public function grabberProvider()
    {
        $data = [
            '' => $this->grabberData,
            '*' => $this->grabberData,
            'accounts.*.Access.eat' => [true, true, true, 100500, true],
            'accounts.126.Access.eat' => 100500,
            'accounts.*.ID' => [10, 20, 30, 40, 50],
            '40' => 500,
            '0' => 1,
        ];

        foreach ($data as $path => $expected) {
            yield [$path, $expected];
        }
    }

    /**
     * @dataProvider invalidGrabberProvider
     */
    public function testGrabberInvalid($path)
    {
        $this->expectException(\RuntimeException::class);
        TestHelper::_grabJsonPath($this->grabberData, TestHelper::_parseJsonPath($path));
    }

    public function invalidGrabberProvider()
    {
        $data = [
            '*.*',
            'accounts.access',
            'accounts.Access.sleep',
            'accounts.Access.eat.*',
            '0.0',
            '40.*',
        ];

        foreach ($data as $path) {
            yield [$path];
        }
    }
}
