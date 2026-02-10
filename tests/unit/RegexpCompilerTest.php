<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Manager\CardImage\RegexpHandler\RegexpCompiler;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;

/**
 * @group frontend-unit
 */
class RegexpCompilerTest extends BaseTest
{
    /**
     * @var RegexpCompiler
     */
    protected $compiler;

    public function _before()
    {
        $this->compiler = new RegexpCompiler();
    }

    public function testKeywordsLessThan3CharsAreIgnored()
    {
        $this->assertNotMatch(['ab', 'ba'], 'check ba account, check abba account');
    }

    public function testCoalescedWordsShouldMatch()
    {
        $keywords = ['these words are coalesced', 'abcde fg'];

        $this->assertMatch($keywords, 'sentence in these words are coalesced');
        $this->assertMatch($keywords, 'sentence in these wordsare coalesced');
        $this->assertMatch($keywords, 'sentence in thesewordsarecoalesced');
    }

    public function testMultilineSpannedKeywordShouldMatch()
    {
        $keyworsd = ['multiline keyword', 'abc'];

        $this->assertMatch($keyworsd, "text\nwith multiline\nkeyword\noccurrences");
        $this->assertMatch($keyworsd, "text\nwith multiline keyword\noccurrences");
    }

    public function testStopWordsSouldBeIgnored()
    {
        $keywords = ['abcd', 'loyalty', 'defg', 'loyalty super', 'programs'];

        $this->assertNotMatch($keywords, 'loyalty program');
        $this->assertNotMatch($keywords, 'program');
        $this->assertNotMatch($keywords, 'some programs');
        $this->assertMatch($keywords, 'loyalty super');
    }

    public function testRegexpKeyword()
    {
        $this->assertMatch(
            ['/(test|alternative|match)\s+[0-9]{3}\s+ending/'],
            "alternative         982   \nending"
        );
    }

    public function testUrlMatch()
    {
        $this->assertMatch(['abcd', 'somesite.domain/path', 'defg'], 'some card text somesite.domain/path');
    }

    protected function _after()
    {
        $this->compiler = null;
    }

    /**
     * @param string[] $keywords
     */
    protected function assertMatch(array $keywords, string $subject)
    {
        $this->doAssertMatch($keywords, $subject, true);
    }

    /**
     * @param string[] $keywords
     */
    protected function assertNotMatch(array $keywords, string $subject)
    {
        $this->doAssertMatch($keywords, $subject, false);
    }

    protected function doAssertMatch(array $keywords, string $subject, bool $expectedMatchResult)
    {
        $regexp = $this->compiler->compile(implode(', ', $keywords));

        if (null === $regexp) {
            assertFalse($expectedMatchResult);
        } else {
            assertEquals($expectedMatchResult, (bool) preg_match($regexp, $subject));
        }
    }
}
