<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Account as EntityAccount;
use AwardWallet\MainBundle\Entity\Fee as EntityFee;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Provider as EntityProvider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\MainBundle\Service\LogProcessor;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Fee as SchemaFee;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\ParsedNumber as SchemaParsedNumber;
use AwardWallet\Schema\Itineraries\PhoneNumber as SchemaPhoneNumber;
use Psr\Log\LoggerInterface;

class BaseConverter implements ItinerarySchema2EntityConverterInterface, ItinerarySegmentSchema2EntityConverterInterface
{
    protected LoggerInterface $logger;

    protected LogProcessor $logProcessor;

    private ProviderRepository $providerRepository;

    private Validator $sourcesValidator;

    public function __construct(
        LoggerFactory $loggerFactory,
        ProviderRepository $providerRepository,
        Validator $sourcesValidator
    ) {
        $this->logProcessor = $loggerFactory->createProcessor();
        $this->logger = $loggerFactory->createLogger($this->logProcessor);
        $this->providerRepository = $providerRepository;
        $this->sourcesValidator = $sourcesValidator;
    }

    public function convert(
        SchemaItinerary $schemaItinerary,
        ?EntityItinerary $entityItinerary,
        SavingOptions $options
    ): EntityItinerary {
        if (is_null($entityItinerary)) {
            throw new \InvalidArgumentException('Expected "EntityItinerary", got null');
        }

        $update = !is_null($entityItinerary->getId());

        try {
            $this->logProcessor->pushContext(['it' => $entityItinerary]);
            $realProviderFromSchema = $this->detectProvider($schemaItinerary, $options->getAccount(), true);

            if ($update) {
                $entityItinerary->setUpdateDate(new \DateTime());

                // restoring deleted itinerary
                if (
                    $options->isInitializedByUser()
                    // && !$entityItinerary->getCopied() @see #22181
                    && empty($schemaItinerary->cancelled)
                    && !$options->isSilent()
                    && $entityItinerary->getHidden()
                ) {
                    $this->logger->warning('restoring deleted itinerary because update initialized by user');
                    $entityItinerary->setHidden(false);
                }

                // marking itinerary as parsed from account
                if (
                    ($account = $options->getAccount())
                    && (
                        is_null($entityItinerary->getAccount())
                        || $entityItinerary->getAccount()->getId() !== $account->getId()
                        || empty($entityItinerary->getRealProvider())
                    )
                ) {
                    if (is_null($account->getProviderid())) {
                        throw new \InvalidArgumentException(sprintf('Custom account #%d cannot be a source of itinerary %s', $account->getId(), $entityItinerary->getIdString()));
                    }

                    $this->logger->warning('marking itinerary as parsed from {account}', [
                        'account' => $account,
                    ]);
                    $entityItinerary->setAccount($account);
                    $entityItinerary->setRealProvider($account->getProviderid());
                }

                // modified flag will be removed
                if ($entityItinerary->getModified()) {
                    $this->logger->warning('modified flag will be removed');
                    $entityItinerary->setModified(false);
                }

                // changing itinerary provider
                if (!empty($providerCode = $schemaItinerary->providerInfo->code ?? null)) {
                    $entityProvider = $entityItinerary->getRealProvider();

                    if (!is_null($entityProvider) && in_array($entityProvider->getKind(), [PROVIDER_KIND_OTHER, PROVIDER_KIND_CREDITCARD])) {
                        /** @var EntityProvider $schemaProvider */
                        $schemaProvider = $this->providerRepository->findOneBy(['code' => $providerCode]);

                        if (!is_null($schemaProvider) && in_array($schemaProvider->getKind(), [PROVIDER_KIND_AIRLINE, PROVIDER_KIND_CAR_RENTAL, PROVIDER_KIND_HOTEL, PROVIDER_KIND_CRUISES, PROVIDER_KIND_TRAIN])) {
                            $this->logger->warning(sprintf('changing itinerary provider from %s to %s', $entityProvider->getCode(), $schemaProvider->getCode()));
                            $entityItinerary->setRealProvider($schemaProvider);
                        }
                    }
                }

                if (!empty($schemaItinerary->cancelled)) {
                    $this->logger->info('cancelling itinerary');
                    $entityItinerary->cancel();
                }
            } else {
                $account = $options->getAccount();
                $source = $options->getSource();
                $entityItinerary->setRealProvider($realProviderFromSchema);
                $entityItinerary->setAccount($account);

                if (!is_null($source->getDate())) {
                    $entityItinerary->setFirstSeenDate($source->getDate());
                }
            }

            // sources
            if ($entityItinerary instanceof SourceListInterface) {
                $entityItinerary->addSource($options->getSource());
                $entityItinerary->setSources($this->sourcesValidator->getLiveSources($entityItinerary->getSources()));
            }

            // travelAgency (не обновляется)
            if (!empty($code = $schemaItinerary->travelAgency->providerInfo->code ?? null) && !$update) {
                $entityItinerary->setTravelAgency($this->providerRepository->findOneBy(['code' => $code]));
            }

            if (!is_null($confirmationNumbers = $schemaItinerary->travelAgency->confirmationNumbers ?? null)) {
                $entityItinerary->setTravelAgencyConfirmationNumbers(
                    array_map(fn (SchemaConfNo $confirmationNumber) => $confirmationNumber->number, $confirmationNumbers)
                );
            }

            if (!is_null($phoneNumbers = $schemaItinerary->travelAgency->phoneNumbers ?? null)) {
                $entityItinerary->setTravelAgencyPhones(
                    array_map(fn (SchemaPhoneNumber $phoneNumber) => $phoneNumber->number, $phoneNumbers)
                );
            }

            if (!is_null($accountNumbers = $schemaItinerary->travelAgency->providerInfo->accountNumbers ?? null)) {
                $entityItinerary->setTravelAgencyParsedAccountNumbers(
                    array_map(fn (SchemaParsedNumber $number) => $number->number, $accountNumbers)
                );
            }

            // pricingInfo
            if (!is_null($total = $schemaItinerary->pricingInfo->total ?? null)) {
                $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withTotal($total));
            }

            if (!is_null($cost = $schemaItinerary->pricingInfo->cost ?? null)) {
                $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withCost($cost));
            }

            if (!is_null($discount = $schemaItinerary->pricingInfo->discount ?? null)) {
                $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withDiscount($discount));
            }

            if (!is_null($currencyCode = $schemaItinerary->pricingInfo->currencyCode ?? null)) {
                $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withCurrencyCode($currencyCode));
            }

            if (is_array($fees = $schemaItinerary->pricingInfo->fees ?? null)) {
                $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withFees(
                    array_map(fn (SchemaFee $fee) => new EntityFee($fee->name, $fee->charge), $fees)
                ));
            }

            if (!is_null($earnedRewards = $schemaItinerary->travelAgency->providerInfo->earnedRewards ?? null)) {
                $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withTravelAgencyEarnedAwards($earnedRewards));
            }

            $providerIsAgency =
                !empty($realProvider = $entityItinerary->getRealProvider())
                && !empty($travelAgency = $entityItinerary->getTravelAgency())
                && $realProvider->getCode() === $travelAgency->getCode();

            if ($update) {
                if (
                    (
                        empty($realProvider = $entityItinerary->getRealProvider())
                        || empty($providerCode = $options->getProviderCode())
                        || $realProvider->getCode() === $providerCode
                    ) && (
                        is_null($schemaItinerary->travelAgency)
                        || is_null($schemaItinerary->travelAgency->providerInfo)
                        || $providerIsAgency
                    )
                ) {
                    if (!is_null($spentAwards = $schemaItinerary->pricingInfo->spentAwards ?? null)) {
                        $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withSpentAwards($spentAwards));
                        $entityItinerary->setSpentAwardsProvider($realProviderFromSchema);
                    }
                }
            } else {
                if (
                    is_null($schemaItinerary->travelAgency)
                    || is_null($schemaItinerary->travelAgency->providerInfo)
                    || $providerIsAgency
                ) {
                    $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withSpentAwards($schemaItinerary->pricingInfo->spentAwards ?? null));
                    $entityItinerary->setSpentAwardsProvider($realProviderFromSchema);
                }

                if (
                    !is_null($realProviderFromSchema)
                    && !empty($providerCode = $options->getProviderCode())
                    && $realProviderFromSchema->getCode() !== $providerCode
                ) {
                    $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withSpentAwards(null));
                    $entityItinerary->setSpentAwardsProvider($realProviderFromSchema);
                }
            }

            // status
            if (!is_null($status = $schemaItinerary->status)) {
                $entityItinerary->setParsedStatus($status);
            }

            // reservationDate
            if (!is_null($reservationDate = $schemaItinerary->reservationDate)) {
                $entityItinerary->setReservationDate(new \DateTime($reservationDate));
            }

            // providerInfo
            if (is_array($accountNumbers = $schemaItinerary->providerInfo->accountNumbers ?? null)) {
                $entityItinerary->setParsedAccountNumbers(
                    array_map(fn (SchemaParsedNumber $number) => $number->number, $accountNumbers)
                );
            }

            if (!is_null($earnedRewards = $schemaItinerary->providerInfo->earnedRewards ?? null)) {
                $entityItinerary->setPricingInfo($entityItinerary->getPricingInfo()->withEarnedAwards($earnedRewards));
            }

            // cancellationPolicy
            if (!is_null($cancellationPolicy = $schemaItinerary->cancellationPolicy)) {
                $entityItinerary->setCancellationPolicy($cancellationPolicy);
            }

            // notes
            if (!is_null($notes = $schemaItinerary->notes)) {
                $entityItinerary->setComment($notes);
            }
        } finally {
            $this->logProcessor->popContext();
        }

        return $entityItinerary;
    }

    public function convertSegment(
        SchemaItinerary $schemaItinerary,
        $schemaSegment,
        EntityTrip $entityTrip,
        ?EntitySegment $entitySegment,
        SavingOptions $options
    ): EntitySegment {
        if (is_null($entitySegment)) {
            throw new \InvalidArgumentException('Expected "EntitySegment", got null');
        }

        $update = !is_null($entitySegment->getId());

        try {
            $this->logProcessor->pushContext(['it' => $entityTrip, 'segment' => $entitySegment]);

            if ($update) {
                $entityTrip->setUpdateDate(new \DateTime());
            }

            // sources
            if ($entitySegment instanceof SourceListInterface) {
                $entitySegment->addSource($options->getSource());
                $entitySegment->setSources($this->sourcesValidator->getLiveSources($entitySegment->getSources()));
            }
        } finally {
            $this->logProcessor->popContext();
        }

        return $entitySegment;
    }

    private function detectProvider(SchemaItinerary $schemaItinerary, ?EntityAccount $account, bool $log): ?EntityProvider
    {
        $provider = null;
        $providerCode = $schemaItinerary->providerInfo->code ?? $schemaItinerary->travelAgency->providerInfo->code ?? null;

        if (!is_null($providerCode)) {
            $provider = $this->providerRepository->findOneBy(['code' => $providerCode]);

            if (is_null($provider) && $log) {
                $this->logger->info(sprintf('unknown real provider, code "%s"', $providerCode));
            }
        }

        if (is_null($provider) && !is_null($account)) {
            if ($log) {
                $this->logger->info('provider {provider} from {account}', [
                    'provider' => $account->getProviderid(),
                    'account' => $account,
                ]);
            }

            return $account->getProviderid();
        }

        return $provider;
    }
}
