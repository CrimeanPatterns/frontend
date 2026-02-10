<?php

namespace AwardWallet\MainBundle\Service\ProgramStatus;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

abstract class AbstractDescriptor
{
    protected TranslatorInterface $translator;

    protected LocalizeService $localizeService;

    protected RouterInterface $router;

    protected ProviderRepository $providerRepository;

    protected OptionsFactory $optionsFactory;

    protected AccountListManager $accountListManager;

    public function __construct(
        TranslatorInterface $translator,
        LocalizeService $localizeService,
        RouterInterface $router,
        ProviderRepository $providerRepository,
        OptionsFactory $optionsFactory,
        AccountListManager $accountListManager
    ) {
        $this->translator = $translator;
        $this->localizeService = $localizeService;
        $this->router = $router;
        $this->providerRepository = $providerRepository;
        $this->optionsFactory = $optionsFactory;
        $this->accountListManager = $accountListManager;
    }

    public function searchProviders(
        string $msg,
        ?Usr $user,
        int $limit = 10,
        ?callable $matchingCallback = null,
        array $excludeProviders = [],
        array $allowedStates = ProviderRepository::PROVIDER_SEARCH_ALLOWED_STATES
    ): array {
        return it(
            $this->providerRepository->searchProviderByText(
                $msg,
                $user ? $user->getId() : null,
                null,
                $limit,
                $allowedStates,
                ['p.CollectingRequests'],
                $matchingCallback
            )
        )
            ->map(function (array $providerFields) use ($user, $excludeProviders) {
                if (in_array($providerFields['ProviderID'], $excludeProviders)) {
                    return null;
                }

                return [
                    'id' => $providerFields['Code'] === 'extraa' ? 1 : $providerFields['ProviderID'],
                    'data' => $this->mapProvider($providerFields, $user),
                ];
            })
            ->filter(fn (?array $data) => is_array($data) && !is_null($data['data']))
            ->uniqByColumn('id')
            ->column('data')
            ->toArray();
    }

    abstract protected function mapProvider(array $providerFields, ?Usr $user);
}
