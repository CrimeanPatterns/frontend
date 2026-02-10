<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Service\HotelPointValue\PatternLoader;
use AwardWallet\MainBundle\Service\HotelPointValue\PatternLoadException;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 */
class PatternLoaderTest extends BaseTest
{
    public function testComment()
    {
        $this->assertEquals(
            [],
            PatternLoader::load("
#Hello
# Test
 # Row
            ")
        );
    }

    public function testInclude()
    {
        $this->assertEquals(
            ['/(Foo)|(Bar\.%)|(Test)/uims'],
            PatternLoader::load("
 # Test include
 Foo
+ Bar.%
 +Test
            ")
        );
    }

    public function testExclude()
    {
        $this->assertEquals(
            [
                '/(Foo)/uims',
                '/^((?!(Bar\.%)|(Test)).)*$/uims',
            ],
            PatternLoader::load("
#Test exclude
 Foo
- Bar.%
 -Test
            ")
        );
    }

    public function testRegex()
    {
        $this->assertEquals(
            [
                '/word/i',
                '/test/uims',
            ],
            PatternLoader::load("
/word/i

# test
 /test/uims
            ")
        );
    }

    public function testComplex()
    {
        $this->assertEquals(
            [
                '/this is test/ui',
                '/pattern/',
                '/(one)|(foo)|(bar)/uims',
                '/^((?!(two)).)*$/uims',
            ],
            PatternLoader::load("
 # test
       /this is test/ui
       # test
   one
   - two
+foo
   + bar
   # TEST
   /pattern/
            ")
        );
    }

    /**
     * @dataProvider testExceptionProvider
     */
    public function testException(string $patterns, string $invalidPattern, int $line)
    {
        $this->expectException(PatternLoadException::class);
        $this->expectExceptionMessage(sprintf('Invalid pattern: "%s" on line %d', $invalidPattern, $line));
        PatternLoader::load($patterns);
    }

    public function testExceptionProvider()
    {
        return [
            [
                "
/word/i
# test
 //aaa/i
                ",
                '//aaa/i',
                4,
            ],
            [
                "
 //aaa
                ",
                '//aaa',
                2,
            ],
            [
                "        /[^\/]+/ims",
                '/[^\/]+/ims',
                1,
            ],
        ];
    }
}
