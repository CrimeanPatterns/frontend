<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Loyalty\EmailApiHistoryParser;
use AwardWallet\MainBundle\Loyalty\Resources\History;

/**
 * @group frontend-unit
 */
class EmailApiHistoryParserTest extends BaseContainerTest
{
    public $apiResponse;
    /**
     * @var EmailApiHistoryParser
     */
    public $emailApiHistoryParser;

    public function _before()
    {
        parent::_before();
        $this->apiResponse = file_get_contents(__DIR__ . '/../_data/AccountHistory/mileageplus.json');
        $this->emailApiHistoryParser = $this->container->get('aw.loyalty_api.email_history.parser');
    }

    public function _after()
    {
        $this->emailApiHistoryParser = null;
        $this->apiResponse = null;
        parent::_after();
    }

    public function testApiResponseConvert()
    {
        $apiResponse = $this
            ->container->get('jms_serializer')
            ->deserialize($this->apiResponse, ParseEmailResponse::class, 'json');

        /** @var History $history */
        $history = $this->emailApiHistoryParser->convertEmailParserCallback($apiResponse);

        $this->assertInstanceOf(History::class, $history);
        $this->assertCount(13, $history->getRows());
    }
}
