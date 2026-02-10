<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards\MerchantMatcher;

use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MultiRegexBuilder\MultiRegexBuilder;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MultiRegexBuilder\MultiRegexBuilder
 * @group frontend-unit
 */
class MultiRegexBuilderTest extends BaseTest
{
    public function testNested()
    {
        $builder = new MultiRegexBuilder();
        $builder->addPattern('#HYATT#', 'HYATT', '#', '1');
        $builder->addPattern('#HYAK#', 'HYAK', '#', '2');
        $builder->addPattern('#HILTON#', 'HILTON', '#', '3');
        $builder->addPattern('#HILTI#', 'HILTI', '#', '4');
        $builder->addPattern('#H\s+M#', 'H\s+M', '#', '5');
        $builder->addPattern('#H\s+M\s+SHOP#', 'H\s+M\s+SHOP', '#', '6');
        $builder->addPattern('#H\s{2}M\s+SHOP#', 'H\s{2}M\s+SHOP', '#', '7');
        $builder->addPattern('#OPA#', 'OPA', '#', '8');
        $megaPatterns = $builder->buildMegaPatterns(10_000, 50, true);
        $this->assertCount(1, $megaPatterns, 'should be one megapattern');
        $this->assertEquals(/** @lang PhpRegExp */ <<<'REG'
#(?
    |H(?
        |\s{2}M\s+SHOP(*:7)
        |\s+(?
            |M\s+SHOP(*:6)
            |M(*:5)
        )
        |YA(?
            |TT(*:1)
            |K(*:2)
        )
        |ILT(?
            |ON(*:3)
            |I(*:4)
        )
    )
    |OPA(*:8)
)#si
REG
            ,
            $megaPatterns[0]
        );
    }

    public function testNested2()
    {
        $builder = new MultiRegexBuilder();
        $builder->addPattern('#RACETRAC#', 'RACETRAC', '#', '1');
        $builder->addPattern('#RBB\s+AFI#', 'RBB\s+AFI', '#', '2');
        $builder->addPattern('#READYFRESH\s+BY#', 'READYFRESH\s+BY', '#', '3');
        $builder->addPattern('#RED\s+ROBIN#', 'RED\s+ROBIN', '#', '4');
        $builder->addPattern('#REI#', 'REI', '#', '5');
        $builder->addPattern('#RESTORATION\s+HARDWARE#', 'RESTORATION\s+HARDWARE', '#', '6');
        $builder->addPattern('#RIOT\s+GAMES#', 'RIOT\s+GAMES', '#', '7');
        $megaPatterns = $builder->buildMegaPatterns(10_000, 50, true);
        $this->assertCount(1, $megaPatterns, 'should be one megapattern');
        $this->assertEquals(/** @lang PhpRegExp */ <<<'REG'
#(?
    |R(?
        |ESTORATION\s+HARDWARE(*:6)
        |IOT\s+GAMES(*:7)
        |E(?
            |ADYFRESH\s+BY(*:3)
            |D\s+ROBIN(*:4)
        )
        |BB\s+AFI(*:2)
        |ACETRAC(*:1)
        |EI(*:5)
    )
)#si
REG
            ,
            $megaPatterns[0]
        );
    }

    public function testSpecialSymbols()
    {
        $builder = new MultiRegexBuilder();
        $builder->addPattern('#CA(?:\s+)?N(?:\s+)?DLEWOOD\s+SUITE(?:\s+)?S#', 'CA(?:\s+)?N(?:\s+)?DLEWOOD\s+SUITE(?:\s+)?S', '#', '1');
        $builder->addPattern('#CA(?:\s+)?N(?:\s+)?VA\s+COM#', 'CA(?:\s+)?N(?:\s+)?VA\s+COM', '#', '2');
        $builder->addPattern('#CHEVRON#', 'CHEVRON', '#', '3');
        $builder->addPattern('#CAPITAL\s+GRILLE#', 'CAPITAL\s+GRILLE', '#', '4');
        $builder->addPattern('#CI(?:\s+)?N(?:\s+)?EMARK\s+COM#', 'CI(?:\s+)?N(?:\s+)?EMARK\s+COM', '#', '5');
        $builder->addPattern('#CLASSPASS#', 'CLASSPASS', '#', '6');
        $builder->addPattern('#CLOUDFLAR#', 'CLOUDFLAR', '#', '7');
        $builder->addPattern('#COFFEE\s+BEAN#', 'COFFEE\s+BEAN', '#', '8');
        $builder->addPattern('#APPLE#', 'APPLE', '#', '9');
        $builder->addPattern('#IMB#', 'IMB', '#', '10');
        $megaPatterns = $builder->buildMegaPatterns(10_000, 3, true);
        $this->assertCount(1, $megaPatterns, 'should be one megapattern');
        $this->assertEquals(/** @lang PhpRegExp */ <<<'REG'
#(?
    |C(?
        |A(?:\s+)?N(?:\s+)?DLEWOOD\s+SUITE(?:\s+)?S(*:1)
        |I(?:\s+)?N(?:\s+)?EMARK\s+COM(*:5)
        |A(?
            |(?:\s+)?N(?:\s+)?VA\s+COM(*:2)
            |PITAL\s+GRILLE(*:4)
        )
        |OFFEE\s+BEAN(*:8)
        |L(?
            |OUDFLAR(*:7)
            |ASSPASS(*:6)
        )
        |HEVRON(*:3)
    )
    |APPLE(*:9)
    |IMB(*:10)
)#si
REG
            ,
            $megaPatterns[0]
        );
    }

    public function testSpecificity()
    {
        $builder = new MultiRegexBuilder();
        $builder->addPattern('#ABC#', 'ABC', '#', '1');
        $builder->addPattern('#ABC\s+HOTELS#', 'ABC\s+HOTELS', '#', '2');
        $builder->addPattern('#AB#', 'AB', '#', '3');
        $builder->addPattern('#0ABC#', '0ABC', '#', '4');
        $builder->addPattern('#BABC#', 'BABC', '#', '5');
        $megaPatterns = $builder->buildMegaPatterns(10_000, 3, true);
        $this->assertCount(1, $megaPatterns, 'should be one megapattern');
        $this->assertEquals(/** @lang PhpRegExp */ <<<'REG'
#(?
    |ABC\s+HOTELS(*:2)
    |BABC(*:5)
    |0ABC(*:4)
    |A(?
        |BC(*:1)
        |B(*:3)
    )
)#si
REG
            ,
            $megaPatterns[0]
        );
    }

    public function testNonMatchingGroupForAlternateSymbol()
    {
        $builder = new MultiRegexBuilder();
        $builder->addPattern('#IKEA#', 'IKEA', '#', '1');
        $builder->addPattern('#RESTORA|TION\s+HARDWARE#', 'RESTORA|TION\s+HARDWARE', '#', '2');
        $megaPatterns = $builder->buildMegaPatterns(10_000, 50, true);
        $this->assertCount(1, $megaPatterns, 'should be one megapattern');
        $this->assertEquals(/** @lang PhpRegExp */ <<<'REG'
#(?
    |(?:RESTORA|TION\s+HARDWARE)(*:2)
    |IKEA(*:1)
)#si
REG
            ,
            $megaPatterns[0]
        );
    }

    public function testStartLineMatchOrder()
    {
        $builder = new MultiRegexBuilder();
        $builder->addPattern('#^RALPHS#', 'RALPHS#', '#', 'short');
        $builder->addPattern('#RALPHS\s+FUEL#', 'RALPHS\s+FUEL#', '#', 'medium');
        $builder->addPattern('#^RALPHS\s+FUEL\s+INDUSTRIES#', 'RALPHS\s+FUEL\s+INDUSTRIES#', '#', 'long');
        $builder->addPattern('#RALPHS\s+FUEL\s+INDUSTRIES\s+AMERICA#', 'RALPHS\s+FUEL\s+INDUSTRIES\s+AMERICA#', '#', 'very_long');
        $megaPatterns = $builder->buildMegaPatterns(10_000, 50, true);
        $this->assertCount(1, $megaPatterns, 'should be one megapattern');
        $this->assertEquals(/** @lang PhpRegExp */ <<<'REG'
#(?
    |RALPHS\s+FUEL\s+INDUSTRIES\s+AMERICA(*:very_long)
    |^RALPHS\s+FUEL\s+INDUSTRIES(*:long)
    |RALPHS\s+FUEL(*:medium)
    |^RALPHS(*:short)
)#si
REG
            ,
            $megaPatterns[0]
        );
    }
}
