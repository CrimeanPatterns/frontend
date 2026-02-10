<?php

namespace AwardWallet\Tests\FunctionalSymfony\Globals;

use AwardWallet\MainBundle\Globals\StringHandler;
use Codeception\Example;

/**
 * @group frontend-functional
 */
class StringHandlerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataprovider tagsAttributes
     */
    public function stripTagsAttributesTest(\TestSymfonyGuy $I, Example $example): void
    {
        $result = StringHandler::stripTagsAttributes($example['rawHtml'], $example['allowedTagsAttributes']);
        $I->assertEquals($example['cleanResult'], $result);
    }

    protected function tagsAttributes(): array
    {
        return [
            [
                'rawHtml' => '<b>text</b>',
                'cleanResult' => '<b>text</b>',
                'allowedTagsAttributes' => ['b' => []],
            ],
            [
                'rawHtml' => '<b>text</b>',
                'cleanResult' => 'text',
                'allowedTagsAttributes' => [],
            ],
            [
                'rawHtml' => '<b class="left">text</b>',
                'cleanResult' => '<b>text</b>',
                'allowedTagsAttributes' => ['b' => []],
            ],
            [
                'rawHtml' => '<a data-ng="ratata" href="about:blank" onclick="alert()">text</a>',
                'cleanResult' => '<a href="about:blank">text</a>',
                'allowedTagsAttributes' => ['a' => ['href']],
            ],
            [
                'rawHtml' => '<p>Lorem I<b class="boldClass">ps<script type>js?</script></b>um<img src="javascript:" alt=""></p>',
                'cleanResult' => '<p>Lorem I<b>psjs?</b>um<img src="#" alt=""></p>',
                'allowedTagsAttributes' => ['p' => [], 'b' => [], 'img' => ['src', 'alt']],
            ],
        ];
    }
}
