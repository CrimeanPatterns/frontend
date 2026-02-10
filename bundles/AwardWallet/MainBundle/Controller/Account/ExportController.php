<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\Account\Export\ExportAccountList;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\MileValue\ProviderMileValueItem;
use Cocur\Slugify\Slugify;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Template("@AwardWalletMain/Account/List/export.html.twig")
 */
class ExportController extends AbstractController
{
    public const EXPORT_TYPE_EXCEL = 'xls';
    public const EXPORT_TYPE_PDF = 'pdf';

    private Slugify $slugify;
    private ExportAccountList $exportAccountList;
    private TranslatorInterface $translator;
    private MileValueService $mileValueService;
    private LocalizeService $localizeService;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;

    public function __construct(
        ExportAccountList $exportAccountList,
        TranslatorInterface $translator,
        MileValueService $mileValueService,
        LocalizeService $localizeService,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory
    ) {
        $this->slugify = new Slugify();
        $this->exportAccountList = $exportAccountList;
        $this->translator = $translator;
        $this->mileValueService = $mileValueService;
        $this->localizeService = $localizeService;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    /**
     * @Route("/export/{type}", name="aw_account_list_export", requirements={"type"="(?:pdf|excel)"}, options={"expose"=true})
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA') and is_granted('ROLE_USER')")
     * @return Response|array
     */
    public function export(string $type, AwTokenStorageInterface $tokenStorage)
    {
        if (ACCOUNT_LEVEL_AWPLUS !== $tokenStorage->getBusinessUser()->getAccountlevel()) {
            return $this->redirect($this->generateUrl('aw_account_list', ['upgrade_popup' => true]));
        }
        /** @var Usr $user */
        $user = $this->getUser();
        $userName = $this->slugify->slugify($user->getFullName(), ' ');

        $accountsData = $this->getAccountsData($user->getFullName());
        $types = [
            'pdf' => self::EXPORT_TYPE_PDF,
            'excel' => self::EXPORT_TYPE_EXCEL,
        ];

        if (php_sapi_name() !== 'cli') { // exclude test environment
            ini_set('memory_limit', '256M');
        }
        $result = $this->exportAccountList->export($accountsData, $userName, $types[$type]);

        if (null !== $result) {
            return $result;
        }

        return [
            'title' => $this->translator->trans('error.award.account.other.title'),
            'message' => $this->translator->trans('account.export.no-data'),
        ];
    }

    /**
     * @return array|array[]
     */
    private function getAccountsData(): array
    {
        global $arProviderKind;
        $accountsInfo = $this->accountListManager->getAccountList(
            $this->optionsFactory->createDesktopListViewOptions(
                (new Options())
                    ->set(Options::OPTION_USER, $this->getUser())
                    ->set(Options::OPTION_LOAD_MILE_VALUE, true)
            )
        )->getAccounts();
        $mileValue = $this->mileValueService->getFlatDataListById();

        foreach ($accountsInfo as &$account) {
            $account['comment'] = $account['comment'] ?? $account['Description'];
            $account['Type'] = $arProviderKind[$account['Kind']] ?? 'Custom';

            if ('Coupon' === $account['TableName']) {
                $account['AccountNumber'] = $account['CardNumber'];
            } elseif (
                'Account' === $account['TableName'] && null === $account['CardNumber']
                && !empty($account['MainProperties']['Number']['Number'])
                && $account['MainProperties']['Number']['Number'] !== $account['Login']
            ) {
                $account['AccountNumber'] = $account['MainProperties']['Number']['Number'];
            } else {
                $account['AccountNumber'] = '';
            }
            $account['Owner'] = ($account['FamilyMemberName'] !== null) ? $account['FamilyMemberName'] : $account['UserName'];
            $account['LastUpdate'] = !empty($account['SuccessCheckDateTs']) ? date('d M Y, H:i:s', $account['SuccessCheckDateTs']) : null;

            if (!empty($account['ConnectedAccount']) && array_key_exists('a' . $account['ConnectedAccount'], $accountsInfo)) {
                if (!array_key_exists('SubAccountsArray', $accountsInfo['a' . $account['ConnectedAccount']])) {
                    $accountsInfo['a' . $account['ConnectedAccount']]['SubAccountsArray'] = [];
                }
                $accountsInfo['a' . $account['ConnectedAccount']]['SubAccountsArray'][] = $account;
                unset($accountsInfo['c' . $account['ID']]);
            }

            $account['CashEquivalent'] = $this->cashEquivalentField($account, $mileValue);

            if (!empty($account['SubAccountsArray'])) {
                foreach ($account['SubAccountsArray'] as &$subAccount) {
                    $subAccount['CashEquivalent'] = $this->cashEquivalentField($subAccount, $mileValue);
                }
            }
        }

        return $accountsInfo;
    }

    private function cashEquivalentField(array $account, array $mileValue): string
    {
        $isEmpty = fn ($value) => empty(trim($value, '$0,.'));

        if (array_key_exists('USDCash', $account)) {
            return $isEmpty($account['USDCash']) ? '' : $account['USDCash'];
        }

        if (0 === strpos($account['Balance'], '$')) {
            return $isEmpty($account['Balance']) ? '' : $account['Balance'];
        }

        $providerId = (int) ($account['ProviderID'] ?? 0);
        $balance = (float) ($account['BalanceRaw'] ?? 0);

        if ($providerId && array_key_exists($providerId, $mileValue) && $balance > 0) {
            /** @var ProviderMileValueItem $mileValueItem */
            $mileValueItem = $mileValue[$providerId];

            return $this->localizeService->formatCurrency(
                round(($mileValueItem->getPrimaryValue(MileValueService::PRIMARY_CALC_FIELD) * $balance) / 100),
                'USD'
            );
        }

        return '';
    }
}
