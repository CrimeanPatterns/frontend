<?php

namespace AwardWallet\MainBundle\Timeline\Formatter;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Account as AccountEntity;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Account;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\ConfirmationNumber;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Email;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Tripit;
use AwardWallet\MainBundle\Timeline\Item\ItineraryInterface;

class Origin
{
    public const TYPE_ACCOUNT = 'account';
    public const TYPE_CONFIRMATION_NUMBER = 'confNumber';
    public const TYPE_EMAIL = 'email';
    public const TYPE_TRIPIT = 'tripit';
    /**
     * @var AccountRepository
     */
    private $accountRep;

    /**
     * @var ProviderRepository
     */
    private $providerRep;

    public function __construct(AccountRepository $accountRep, ProviderRepository $providerRep)
    {
        $this->accountRep = $accountRep;
        $this->providerRep = $providerRep;
    }

    /**
     * @return array|null
     *      [
     *          'auto' => [
     *              [
     *                  'type' => 'account',
     *                  'accountId' => 123,
     *                  'provider' => 'Delta',
     *                  'accountNumber' => '654321',
     *                  'owner' => 'John Brown',
     *              ],
     *              [
     *                  'type' => 'confNumber',
     *                  'provider' => 'Delta',
     *                  'confNumber' => 'Z12345',
     *              ],
     *              [
     *                  'type' => 'email',
     *                  'from' => 0, \AwardWallet\MainBundle\Email\ParsedEmailSource::SOURCE_* constants
     *                  'email' => 'test@test.com',
     *              ],
     *          ],
     *          'manual' => <bool>
     *      ]
     */
    public function format(ItineraryInterface $item): ?array
    {
        /** @var SourceListInterface|Itinerary|Tripsegment $itSource */
        $itSource = $item->getSource();
        $itinerary = $item->getItinerary();

        if (is_null($itSource) || !($itSource instanceof SourceListInterface)) {
            return null;
        }

        $accountSources = [];
        $confSources = [];
        $emailSources = [];
        $haveUnknownEmail = false;
        $confNumberSourcesAdded = [];
        $tripitSources = [];

        foreach ($itSource->getSources() as $source) {
            /** @var SourceInterface $source */
            if ($source instanceof Account) {
                /** @var AccountEntity $account */
                $account = $this->accountRep->find($source->getAccountId());

                if ($account && ($provider = $account->getProviderid())) {
                    $accountSources[] = [
                        'type' => self::TYPE_ACCOUNT,
                        'accountId' => $account->getId(),
                        'provider' => $provider->getShortname(),
                        'accountNumber' => $account->getAccountNumber(),
                        'owner' => $account->getOwner()->getFullName(),
                    ];
                }
            } elseif ($source instanceof ConfirmationNumber) {
                /** @var Provider $provider */
                $provider = $this->providerRep->findOneBy(['code' => $source->getProviderCode()]);
                $fields = $source->getConfirmationFields();

                if (
                    $provider
                    && is_array($fields)
                    && isset($fields['ConfNo'])
                    && !isset($confNumberSourcesAdded[$key = sprintf('%s-%s', $source->getProviderCode(), strtolower($fields['ConfNo']))])
                ) {
                    $confNumberSourcesAdded[$key] = true;
                    $confSources[] = [
                        'type' => self::TYPE_CONFIRMATION_NUMBER,
                        'provider' => $provider->getShortname(),
                        'confNumber' => $fields['ConfNo'],
                    ];
                }
            } elseif ($source instanceof Email) {
                $recipient = $source->getRecipient();

                if (!is_null($recipient)) {
                    $emailSources[$recipient] = [
                        'type' => self::TYPE_EMAIL,
                        'from' => $source->getFrom(),
                        'email' => $recipient,
                    ];
                } else {
                    $haveUnknownEmail = true;
                }
            } elseif ($source instanceof Tripit) {
                $tripitSources[] = [
                    'type' => self::TYPE_TRIPIT,
                    'email' => $source->getEmail(),
                ];
            } else {
                throw new \RuntimeException('Unknown type of itinerary source');
            }
        }

        if ($haveUnknownEmail && count($emailSources) === 0) {
            $emailSources[] = [
                'type' => self::TYPE_EMAIL,
                'from' => ParsedEmailSource::SOURCE_UNKNOWN,
            ];
        }

        $result = [
            'auto' => array_merge(
                $accountSources,
                $confSources,
                array_values($emailSources),
                $tripitSources
            ),
            'manual' => false,
        ];

        if (count($result['auto']) === 0 && !$itinerary->getParsed()) {
            $result['manual'] = true;
        }

        if (count($result['auto']) === 0 && !$result['manual']) {
            return null;
        }

        return $result;
    }
}
