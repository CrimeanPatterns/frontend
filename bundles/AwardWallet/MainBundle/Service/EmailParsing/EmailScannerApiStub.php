<?php

namespace AwardWallet\MainBundle\Service\EmailParsing;

use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Configuration;
use AwardWallet\MainBundle\Service\EmailParsing\Client\HeaderSelector;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\DetectTypeResponse;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\GoogleMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\MicrosoftMailbox;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;

class EmailScannerApiStub extends EmailScannerApi
{
    public function __construct(?ClientInterface $client = null, ?Configuration $config = null, ?HeaderSelector $selector = null)
    {
    }

    public function getConfig()
    {
        return new Configuration();
    }

    public function authMailbox($code): void
    {
    }

    public function authMailboxWithHttpInfo($code): array
    {
        return [null, null, null];
    }

    public function authMailboxAsync($code)
    {
        return $this->getEmptyPromise();
    }

    public function authMailboxAsyncWithHttpInfo($code)
    {
        return $this->getEmptyPromise();
    }

    public function connectGoogleMailbox($request)
    {
        return new GoogleMailbox();
    }

    public function connectGoogleMailboxWithHttpInfo($request)
    {
        return [new GoogleMailbox(), null, null];
    }

    public function connectGoogleMailboxAsync($request)
    {
        return $this->getEmptyPromise();
    }

    public function connectGoogleMailboxAsyncWithHttpInfo($request)
    {
        return $this->getEmptyPromise();
    }

    public function connectImapMailbox($request)
    {
        return new Mailbox();
    }

    public function connectImapMailboxWithHttpInfo($request)
    {
        return [new Mailbox(), null, null];
    }

    public function connectImapMailboxAsync($request)
    {
        return $this->getEmptyPromise();
    }

    public function connectImapMailboxAsyncWithHttpInfo($request)
    {
        return $this->getEmptyPromise();
    }

    public function connectMicrosoftMailbox($request)
    {
        return new MicrosoftMailbox();
    }

    public function connectMicrosoftMailboxWithHttpInfo($request)
    {
        return [new MicrosoftMailbox(), null, null];
    }

    public function connectMicrosoftMailboxAsync($request)
    {
        return $this->getEmptyPromise();
    }

    public function connectMicrosoftMailboxAsyncWithHttpInfo($request)
    {
        return $this->getEmptyPromise();
    }

    public function detectType($email)
    {
        return new DetectTypeResponse();
    }

    public function detectTypeWithHttpInfo($email)
    {
        return [new DetectTypeResponse(), null, null];
    }

    public function detectTypeAsync($email)
    {
        return $this->getEmptyPromise();
    }

    public function detectTypeAsyncWithHttpInfo($email)
    {
        return $this->getEmptyPromise();
    }

    public function disconnectMailbox($mailboxId, $revoke = null): void
    {
    }

    public function disconnectMailboxWithHttpInfo($mailboxId, $revoke = null): array
    {
        return [null, null, null];
    }

    public function disconnectMailboxAsync($mailboxId, $revoke = null)
    {
        return $this->getEmptyPromise();
    }

    public function disconnectMailboxAsyncWithHttpInfo($mailboxId, $revoke = null)
    {
        return $this->getEmptyPromise();
    }

    public function getAuthCodeMailbox($request): string
    {
        return 'mailboxcode';
    }

    public function getAuthCodeMailboxWithHttpInfo($request)
    {
        return ['mailboxcode', null, null];
    }

    public function getAuthCodeMailboxAsync($request)
    {
        return $this->getEmptyPromise();
    }

    public function getAuthCodeMailboxAsyncWithHttpInfo($request)
    {
        return $this->getEmptyPromise();
    }

    public function getMailbox($mailboxId): void
    {
    }

    public function getMailboxWithHttpInfo($mailboxId)
    {
        return [null, null, null];
    }

    public function getMailboxAsync($mailboxId)
    {
        return $this->getEmptyPromise();
    }

    public function getMailboxAsyncWithHttpInfo($mailboxId)
    {
        return $this->getEmptyPromise();
    }

    public function listMailboxes($tags = null, $states = null, $types = null, $errorCodes = null)
    {
        return [];
    }

    public function listMailboxesWithHttpInfo($tags = null, $states = null, $types = null, $errorCodes = null)
    {
        return [[], null, null];
    }

    public function listMailboxesAsync($tags = null, $states = null, $types = null, $errorCodes = null)
    {
        return $this->getEmptyPromise();
    }

    public function listMailboxesAsyncWithHttpInfo($tags = null, $states = null, $types = null, $errorCodes = null)
    {
        return $this->getEmptyPromise();
    }

    /**
     * Operation putMailbox.
     *
     * Update Mailbox Info
     *
     * @param  int $mailboxId mailboxId (required)
     * @param  \AwardWallet\MainBundle\Service\EmailParsing\Client\Model\UpdateMailboxRequest $request request (required)
     * @return \AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox
     * @throws \AwardWallet\MainBundle\Service\EmailParsing\Client\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     */
    public function putMailbox($mailboxId, $request)
    {
    }

    /**
     * Operation putMailboxWithHttpInfo.
     *
     * Update Mailbox Info
     *
     * @param  int $mailboxId (required)
     * @param  \AwardWallet\MainBundle\Service\EmailParsing\Client\Model\UpdateMailboxRequest $request (required)
     * @return array of \AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox, HTTP status code, HTTP response headers (array of strings)
     * @throws \AwardWallet\MainBundle\Service\EmailParsing\Client\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     */
    public function putMailboxWithHttpInfo($mailboxId, $request)
    {
    }

    /**
     * Operation putMailboxAsync.
     *
     * Update Mailbox Info
     *
     * @param  int $mailboxId (required)
     * @param  \AwardWallet\MainBundle\Service\EmailParsing\Client\Model\UpdateMailboxRequest $request (required)
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \InvalidArgumentException
     */
    public function putMailboxAsync($mailboxId, $request)
    {
    }

    /**
     * Operation putMailboxAsyncWithHttpInfo.
     *
     * Update Mailbox Info
     *
     * @param  int $mailboxId (required)
     * @param  \AwardWallet\MainBundle\Service\EmailParsing\Client\Model\UpdateMailboxRequest $request (required)
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \InvalidArgumentException
     */
    public function putMailboxAsyncWithHttpInfo($mailboxId, $request)
    {
    }

    protected function getEmptyPromise(): PromiseInterface
    {
        return new Promise(
            function () {},
            function () {}
        );
    }

    protected function getEmptyRequest(): Request
    {
        return new Request('-', '-');
    }

    protected function authMailboxRequest($code)
    {
        return $this->getEmptyRequest();
    }

    protected function connectGoogleMailboxRequest($request)
    {
        return $this->getEmptyRequest();
    }

    protected function connectImapMailboxRequest($request)
    {
        return $this->getEmptyRequest();
    }

    protected function connectMicrosoftMailboxRequest($request)
    {
        return $this->getEmptyRequest();
    }

    protected function detectTypeRequest($email)
    {
        return $this->getEmptyRequest();
    }

    protected function disconnectMailboxRequest($mailboxId, $revoke = null)
    {
        return $this->getEmptyRequest();
    }

    protected function getAuthCodeMailboxRequest($request)
    {
        return $this->getEmptyRequest();
    }

    protected function getMailboxRequest($mailboxId)
    {
        return $this->getEmptyRequest();
    }

    protected function listMailboxesRequest($tags = null, $states = null, $types = null, $errorCodes = null)
    {
        return $this->getEmptyRequest();
    }

    /**
     * Create request for operation 'putMailbox'.
     *
     * @param  int $mailboxId (required)
     * @param  \AwardWallet\MainBundle\Service\EmailParsing\Client\Model\UpdateMailboxRequest $request (required)
     * @return \GuzzleHttp\Psr7\Request
     * @throws \InvalidArgumentException
     */
    protected function putMailboxRequest($mailboxId, $request)
    {
    }

    protected function createHttpClientOption()
    {
        return [];
    }
}
