<?php

namespace AwardWallet\MainBundle\Form\Type\Helpers;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\CountryRepository;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\StateRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options as AccountListOptions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Repository\CreditCardRepository;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Record\Subdivision;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DocumentHelper
{
    private AwTokenStorageInterface $tokenStorage;
    private AccountListManager $accountListManager;
    private OwnerRepository $ownerRepository;
    private Reader $geoReader;
    private StateRepository $stateRepository;
    private LocalizeService $localizer;
    private CountryRepository $countryRepository;
    private CreditCardRepository $cardRepository;
    private TranslatorInterface $translator;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        AccountListManager $accountListManager,
        OwnerRepository $ownerRepository,
        Reader $geoReader,
        StateRepository $stateRepository,
        CountryRepository $countryRepository,
        LocalizeService $localizer,
        CreditCardRepository $cardRepository,
        TranslatorInterface $translator
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->accountListManager = $accountListManager;
        $this->ownerRepository = $ownerRepository;
        $this->geoReader = $geoReader;
        $this->stateRepository = $stateRepository;
        $this->localizer = $localizer;
        $this->countryRepository = $countryRepository;
        $this->cardRepository = $cardRepository;
        $this->translator = $translator;
    }

    public function preFillData(string $type, FormBuilderInterface $builder, array $params = []): array
    {
        $isNewRecord = null === $builder->getData()->getId();

        if (!$isNewRecord) {
            return [];
        }

        $data = '';

        if ('fullName' === $type) {
            $data = $this->tokenStorage->getToken()->getUser()->getFullName();
        } elseif ('policyHolder' === $type) {
            $data = array_keys(Providercoupon::INSURANCE_POLICY_HOLDER_LIST)[0];
        } elseif ('effectiveDate' === $type) {
            $data = new \DateTime(date('Y-01-01'));
        } elseif ('insuranceExpirationDate' === $type) {
            $data = new \DateTime(date('Y-12-31'));
        } elseif ('countryId' === $type) {
            $data = $this->tokenStorage->getToken()->getUser()->getCountryid() ?? 0;
        } elseif ('stateId' === $type) {
            $countryId = $params['countryId'];
            $lastIp = $this->tokenStorage->getUser()->getLastlogonip();

            try {
                $record = $this->geoReader->city($lastIp);
            } catch (AddressNotFoundException $e) {
            }

            if (!empty($record->subdivisions)) {
                /** @var Subdivision $state */
                $stateCode = $record->subdivisions[0]->isoCode;

                foreach ($params['countriesStates'] as $country) {
                    if ($countryId === $country['CountryID'] && !empty($country['states'])) {
                        foreach ($country['states'] as $state) {
                            if ($state['Code'] === $stateCode) {
                                $data = (int) $state['StateID'];

                                break 2;
                            }
                        }
                    }
                }
            }
        }

        return ['data' => $data];
    }

    public function addIsNewDocument(FormBuilderInterface $builder, Providercoupon $document)
    {
        $builder->add('isNewRecord', HiddenType::class, [
            'mapped' => false,
            'data' => null === $document->getId() ? 1 : 0,
        ]);
    }

    public function addHiddenOwnersList(FormBuilderInterface $builder)
    {
        $ownersList = $this->getOwnersList();
        $ownersList = $this->fetchOwnersPassport($ownersList);

        $builder->add('ownersList', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => json_encode($ownersList),
        ]);
    }

    public static function getDiseaseList(): array
    {
        $list = [
            'Chickenpox (Varicella)',
            'Diphtheria',
            'Flu (Influenza)',
            'Hepatitis A',
            'Hepatitis B',
            'Hib (Haemophilus influenzae type b)',
            'HPV (Human Papillomavirus)',
            'Measles',
            'Meningococcal',
            'Malaria',
            'Mumps',
            'Pneumococcal',
            'Polio (Poliomyelitis)',
            'Rotavirus',
            'Rubella (German Measles)',
            'Shingles (Herpes Zoster)',
            'Tetanus (Lockjaw)',
            'Whooping Cough (Pertussis)',
            'Adenovirus',
            'Anthrax',
            'Cholera',
            'Japanese Encephalitis (JE)',
            'Rabies',
            'Smallpox',
            'Tuberculosis',
            'Typhoid Fever',
            'Yellow Fever',
        ];
        sort($list, SORT_STRING);

        return ['Covid 19'] + $list;
    }

    public function prepareCountries(FormBuilderInterface $builder): array
    {
        $countries = $this->localizer->getLocalizedCountries();
        $countriesStates = $this->stateRepository->getLocalizedCountriesStates($countries);
        $builder->add('countriesStates', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => \json_encode($countriesStates),
        ]);
        $countryId = $builder->getData()->getCustomFields()[Providercoupon::FIELD_KEY_DRIVERS_LICENSE]['country']
            ?? $this->tokenStorage->getToken()->getUser()->getCountryid()
            ?? null;

        if ($countryId) {
            $country = $this->countryRepository->find($countryId);
            $stateChoices = ($country && $country->getHavestates() && \array_key_exists($countryId, $countriesStates)) ?
                $this->stateRepository->getStatesByCountry($countryId) :
                [];
        }

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $countryId = (int) $event->getForm()->get('country')->getData();

            if (!empty($countryId)) {
                $options = $form->get('state')->getConfig()->getOptions();
                $options['choices'] = array_flip($this->stateRepository->getStatesByCountry($countryId));
                $options['required'] = false;
                $options['data'] = $event->getForm()->get('state')->getViewData();
                $form->add('state', ChoiceType::class, $options);

                $data = $event->getData();
                $data->setState($options['data']);
                $event->setData($data);
            }
        });

        return [
            'countries' => $countries,
            'stateChoices' => $stateChoices,
            'statePreFillData' => $this->preFillData('stateId', $builder, [
                'countryId' => $countryId,
                'countriesStates' => $countriesStates,
            ]),
        ];
    }

    public function getCreditCards(array $criteria): array
    {
        $cards = $this->cardRepository->findBy($criteria);

        $result = [];

        /** @var CreditCard $card */
        foreach ($cards as $card) {
            $result[$card->getId()] = $card->getCardFullName() ?? $card->getName();
        }

        return $result;
    }

    public function getTranslatedDocumentTitle(ProviderCoupon $document): string
    {
        switch ($document->getTypeid()) {
            case Providercoupon::TYPE_PASSPORT:
                return $this->translator->trans('document.passport.list.title', [], 'mobile');

            case Providercoupon::TYPE_TRUSTED_TRAVELER:
                return $this->translator->trans('document.traveler.number.list.title', [], 'mobile');

            case Providercoupon::TYPE_VACCINE_CARD:
                return $this->translator->trans('document.vaccine.card.list.title', [], 'mobile');

            case Providercoupon::TYPE_INSURANCE_CARD:
                return $this->translator->trans('document.insurance.card.list.title', [], 'mobile');

            case Providercoupon::TYPE_VISA:
                return $this->translator->trans('document.visa.list.title', [], 'mobile');

            case Providercoupon::TYPE_DRIVERS_LICENSE:
                return $this->translator->trans('document.drivers.license.list.title', [], 'mobile');

            case Providercoupon::TYPE_PRIORITY_PASS:
                return $this->translator->trans('priority-pass');

            default: throw new \InvalidArgumentException('Invalid type');
        }
    }

    private function getOwnersList(): array
    {
        $ownersList = [];
        $user = $this->tokenStorage->getToken()->getUser();
        $owners = $this->ownerRepository->findAvailableOwners(OwnerRepository::FOR_ACCOUNT_ASSIGNMENT, $user, '', 0);

        foreach ($owners as $owner) {
            if ($owner->isBusiness()) {
                continue;
            }

            $agent = $owner->getUseragentForUser($user);
            $name = $owner->getFullName();

            if (null === $agent) {
                $key = $user->getId();
            } elseif (!$owner->isFamilyMemberOfUser($user)) {
                $key = $owner->getUser()->getId();
                $name = $owner->getUser()->getFullName();
            } else {
                $key = $user->getId() . '_' . $agent->getId();
            }

            $ownersList[$key] = [
                'name' => $name,
            ];
        }

        return $ownersList;
    }

    private function fetchOwnersPassport(array $ownersList): array
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $accountList = $this->accountListManager->getAccountList(
            (new \AwardWallet\MainBundle\Globals\AccountList\Options())
                ->set(AccountListOptions::OPTION_USER, $this->tokenStorage->getToken()->getUser())
                ->set(AccountListOptions::OPTION_LOAD_CARD_IMAGES, false)
                ->set(AccountListOptions::OPTION_FILTER, ' AND 0 = 1')
                ->set(AccountListOptions::OPTION_LOAD_SUBACCOUNTS, false)
                ->set(AccountListOptions::OPTION_LOAD_PROPERTIES, false)
                ->set(AccountListOptions::OPTION_COUPON_FILTER, ' AND c.Kind = ' . PROVIDER_KIND_DOCUMENT)
        );

        $passports = it($accountList)
            ->filter(fn ($account) => Providercoupon::TYPE_PASSPORT === (int) $account['TypeID'])
            ->toArray();

        $founds = [];

        foreach ($passports as $passport) {
            $passportData = $passport['CustomFields'][Providercoupon::FIELD_KEY_PASSPORT] ?? null;

            if (empty($passportData)
                || empty($passportData['countryId'])
                || Country::UNITED_STATES !== (int) $passportData['countryId']) {
                continue;
            }

            $key = $user->getId();

            if (!empty($passport['UserAgentID'])) {
                $key .= '_' . $passport['UserAgentID'];
            }

            if (array_key_exists($key, $ownersList)) {
                if (!array_key_exists($key, $founds)) {
                    $founds[$key] = [];
                }
                $founds[$key][] = $passportData['number'];
            }
        }

        foreach ($founds as $key => $found) {
            if (1 === count($found)) {
                $ownersList[$key]['passportNumber'] = $found[0];
            }
        }

        return $ownersList;
    }
}
