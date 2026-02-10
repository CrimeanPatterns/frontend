<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountResponse;
use Psr\Log\LoggerInterface;

class RetrieveConfirmationSender
{
    /** @var LoggerInterface */
    private $logger;
    /** @var \Memcached */
    private $memcached;
    /** @var Converter */
    private $converter;
    /** @var ApiCommunicator */
    private $api;

    public function __construct(
        LoggerInterface $logger,
        \Memcached $memcached,
        Converter $converter,
        ApiCommunicator $api
    ) {
        $this->logger = $logger;
        $this->memcached = $memcached;
        $this->converter = $converter;
        $this->api = $api;
    }

    public function send(Owner $owner, string $providerCode, string $locator, array $fields)
    {
        if (!$this->memcached->get($this->cacheKey($providerCode, $locator))) {
            $loyaltyRequest = $this->converter->prepareCheckConfirmationRequest($providerCode, $fields, $owner->getUser()->getId(), $owner->isFamilyMember() ? $owner->getFamilyMember()->getId() : null);

            try {
                $loyaltyResponse = $this->api->CheckConfirmation($loyaltyRequest);

                if (!$loyaltyResponse instanceof PostCheckAccountResponse) {
                    $this->logger->warning('confirmation retrieve error', ['providerCode' => $providerCode, 'confNo' => $locator]);
                } else {
                    $this->logger->info('called checkConfirmation', ['providerCode' => $providerCode, 'confNo' => $locator]);
                }
                $this->memcached->set('check_confirmation_request_' . $loyaltyResponse->getRequestid(), $loyaltyRequest, 30 * 60);
                $this->memcached->set($this->cacheKey($providerCode, $locator), '1', DateTimeUtils::SECONDS_PER_HOUR);
            } catch (ApiCommunicatorException $e) {
                $this->logger->warning(sprintf('%s thrown: %s', get_class($e), $e->getMessage()));
            }
        } else {
            $this->logger->info('cc request with these parameters throttled (1hour)');
        }
    }

    private function cacheKey(string $providerCode, string $locator): string
    {
        return sprintf('callback_cc_call_%s_%s', $providerCode, $locator);
    }
}
