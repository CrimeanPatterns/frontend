<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards\MerchantMatcher;

use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\RegexMetadataFactory;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\RegexMetadataFactory
 * @group frontend-unit
 */
class RegexMetadataFactoryTest extends BaseTest
{
    /**
     * @dataProvider factoryDataProvider
     * @covers ::create
     */
    public function testFactory(string $input, array $expectedOutput): void
    {
        $this->assertEquals(
            $expectedOutput,
            RegexMetadataFactory::create($input)
        );
    }

    public function factoryDataProvider(): array
    {
        return [
            'simple implicit positive' => [
                <<<EOF_simple_positive
first second
EOF_simple_positive,
                [
                    [
                        'template' => 'first second',
                        'isPreg' => false,
                        'isPositive' => true,
                        'original' => 'first second',
                        'originalWithoutSign' => 'first second',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
            'multiple simple implicit positive' => [
                <<<EOF_simple_positive
first second
third fourth
EOF_simple_positive,
                [
                    [
                        'template' => 'first second',
                        'isPreg' => false,
                        'isPositive' => true,
                        'original' => 'first second',
                        'originalWithoutSign' => 'first second',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                    [
                        'template' => 'third fourth',
                        'isPreg' => false,
                        'isPositive' => true,
                        'original' => 'third fourth',
                        'originalWithoutSign' => 'third fourth',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
            'simple explicit positive' => [
                <<<EOF_simple_positive
+first second
EOF_simple_positive,
                [
                    [
                        'template' => 'first second',
                        'isPreg' => false,
                        'isPositive' => true,
                        'original' => '+first second',
                        'originalWithoutSign' => 'first second',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
            'multiple simple explicit positive' => [
                <<<EOF_simple_positive
+first second
+third fourth
EOF_simple_positive,
                [
                    [
                        'template' => 'first second',
                        'isPreg' => false,
                        'isPositive' => true,
                        'original' => '+first second',
                        'originalWithoutSign' => 'first second',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                    [
                        'template' => 'third fourth',
                        'isPreg' => false,
                        'isPositive' => true,
                        'original' => '+third fourth',
                        'originalWithoutSign' => 'third fourth',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],

            'regex implicit positive' => [
                <<<EOF_simple_positive
#first\s+second#
EOF_simple_positive,
                [
                    [
                        'template' => '#first\s+second#',
                        'isPreg' => true,
                        'isPositive' => true,
                        'original' => '#first\s+second#',
                        'originalWithoutSign' => '#first\s+second#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],

            'multiple regex implicit positive' => [
                <<<EOF_simple_positive
#first\s+second#
#third\s+fourth#
EOF_simple_positive,
                [
                    [
                        'template' => '#first\s+second#',
                        'isPreg' => true,
                        'isPositive' => true,
                        'original' => '#first\s+second#',
                        'originalWithoutSign' => '#first\s+second#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                    [
                        'template' => '#third\s+fourth#',
                        'isPreg' => true,
                        'isPositive' => true,
                        'original' => '#third\s+fourth#',
                        'originalWithoutSign' => '#third\s+fourth#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],

            'regex explicit positive' => [
                <<<EOF_simple_positive
+#first\s+second#
EOF_simple_positive,
                [
                    [
                        'template' => '#first\s+second#',
                        'isPreg' => true,
                        'isPositive' => true,
                        'original' => '+#first\s+second#',
                        'originalWithoutSign' => '#first\s+second#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
            'multiple regex explicit positive' => [
                <<<EOF_simple_positive
+#first\s+second#
+#third\s+fourth#
EOF_simple_positive,
                [
                    [
                        'template' => '#first\s+second#',
                        'isPreg' => true,
                        'isPositive' => true,
                        'original' => '+#first\s+second#',
                        'originalWithoutSign' => '#first\s+second#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                    [
                        'template' => '#third\s+fourth#',
                        'isPreg' => true,
                        'isPositive' => true,
                        'original' => '+#third\s+fourth#',
                        'originalWithoutSign' => '#third\s+fourth#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],

            'simple explicit negative' => [
                <<<EOF_simple_positive
-first second
EOF_simple_positive,
                [
                    [
                        'template' => 'first second',
                        'isPreg' => false,
                        'isPositive' => false,
                        'original' => '-first second',
                        'originalWithoutSign' => 'first second',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
            'multiple simple explicit negative' => [
                <<<EOF_simple_positive
-first second
-third fourth
EOF_simple_positive,
                [
                    [
                        'template' => 'first second',
                        'isPreg' => false,
                        'isPositive' => false,
                        'original' => '-first second',
                        'originalWithoutSign' => 'first second',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                    [
                        'template' => 'third fourth',
                        'isPreg' => false,
                        'isPositive' => false,
                        'original' => '-third fourth',
                        'originalWithoutSign' => 'third fourth',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],

            'regex explicit negative' => [
                <<<EOF_simple_positive
-#first\s+second#
EOF_simple_positive,
                [
                    [
                        'template' => '#first\s+second#',
                        'isPreg' => true,
                        'isPositive' => false,
                        'original' => '-#first\s+second#',
                        'originalWithoutSign' => '#first\s+second#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
            'multiple regex explicit negative' => [
                <<<EOF_simple_positive
-#first\s+second#
-#third\s+fourth#
EOF_simple_positive,
                [
                    [
                        'template' => '#first\s+second#',
                        'isPreg' => true,
                        'isPositive' => false,
                        'original' => '-#first\s+second#',
                        'originalWithoutSign' => '#first\s+second#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                    [
                        'template' => '#third\s+fourth#',
                        'isPreg' => true,
                        'isPositive' => false,
                        'original' => '-#third\s+fourth#',
                        'originalWithoutSign' => '#third\s+fourth#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
            'simple explicit mixed' => [
                <<<EOF_simple_positive
+first second
-third fourth
EOF_simple_positive,
                [
                    [
                        'template' => 'first second',
                        'isPreg' => false,
                        'isPositive' => true,
                        'original' => '+first second',
                        'originalWithoutSign' => 'first second',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                    [
                        'template' => 'third fourth',
                        'isPreg' => false,
                        'isPositive' => false,
                        'original' => '-third fourth',
                        'originalWithoutSign' => 'third fourth',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
            'regex explicit mixed' => [
                <<<EOF_simple_positive
+#first\s+second#
-#third\s+fourth#
EOF_simple_positive,
                [
                    [
                        'template' => '#first\s+second#',
                        'isPreg' => true,
                        'isPositive' => true,
                        'original' => '+#first\s+second#',
                        'originalWithoutSign' => '#first\s+second#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                    [
                        'template' => '#third\s+fourth#',
                        'isPreg' => true,
                        'isPositive' => false,
                        'original' => '-#third\s+fourth#',
                        'originalWithoutSign' => '#third\s+fourth#',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
            'simple escaped explicit mixed' => [
                <<<EOF_simple_positive
\\+first second
\\-third fourth
EOF_simple_positive,
                [
                    [
                        'template' => '\\+first second',
                        'isPreg' => false,
                        'isPositive' => true,
                        'original' => '\\+first second',
                        'originalWithoutSign' => '\\+first second',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                    [
                        'template' => '\\-third fourth',
                        'isPreg' => false,
                        'isPositive' => true,
                        'original' => '\\-third fourth',
                        'originalWithoutSign' => '\\-third fourth',
                        'pregError' => null,
                        'beginSymbol' => false,
                    ],
                ],
            ],
        ];
    }
}
