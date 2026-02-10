<?php

namespace AwardWallet\MainBundle\Controller\Coupon;

use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/coupon")
 */
class JsonController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private GlobalVariables $globalVariables;

    public function __construct(AwTokenStorageInterface $tokenStorage, GlobalVariables $globalVariables)
    {
        $this->tokenStorage = $tokenStorage;
        $this->globalVariables = $globalVariables;
    }

    /**
     * Custom voucher autocomplete.
     *
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/getprogs", name="aw_coupon_json_progs", methods={"GET"}, options={"expose"=true})
     */
    public function getAllProgsAction(Request $request)
    {
        if ($query = $request->get('query')) {
            $filter = '';
            $requestFields = $request->query->get('requestFields');
            $fields = empty($requestFields) ? null : array_map('trim', explode(',', $requestFields));

            if (!empty($fields)) {
                global $arProviderKind;

                if (in_array('currency', $fields)) {
                    $currency = $this->getDoctrine()->getConnection()->fetchAll('SELECT CurrencyID, Name FROM Currency');
                    $currencyPair = [];

                    foreach ($currency as $item) {
                        $currencyPair[$item['CurrencyID']] = $item['Name'];
                    }
                }

                /**
                 * Hiding providers called Amex so that there is no confusion when choosing from BW: Transfer.
                 */
                $filter .= ' AND ProviderID NOT IN(641, 811, 873, 1032)';
            }

            $providers = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)
                ->findProviderByText($query, 'ASC', 20, $filter);
            $result = [];

            $isRequestFields = !empty($fields);

            foreach ($providers as $provider) {
                $providerId = (int) $provider['ProviderID'];
                $res = [
                    'id' => $providerId,
                    'value' => html_entity_decode($provider['DisplayName']),
                    'label' => html_entity_decode($provider['DisplayName']),
                    'kind' => $provider['Kind'],
                    'name' => $provider['ShortName'],
                ];

                if ($isRequestFields) {
                    if (in_array('currency', $fields) && !empty($provider['Currency'])) {
                        $res['field_currency'] = array_key_exists($provider['Currency'], $currencyPair) ? $currencyPair[$provider['Currency']] : '';
                    }

                    if (in_array('kind_value', $fields)) {
                        $res['field_kind'] = $arProviderKind[$res['kind']] ?? '';
                    }

                    if (in_array('uri_name', $fields)) {
                        $res['uri_name'] = urlencode($provider['Name']);
                    }
                }

                $result[$providerId] = $res;
            }

            if (is_array($fields) && in_array('regions', $fields) && count($result)) {
                $result = $this->getLogin2Options($result, $providers);
            }

            return new JsonResponse(array_values($result));
        }

        return new JsonResponse();
    }

    /**
     * @return array<int ProviderID, array<array field_regions_options, string field_regions_value>>
     * field_regions_options - possible values of Login2 if specified as region or country
     * field_regions_value - value of login2 field
     */
    private function getLogin2Options(array &$result, array $providers): array
    {
        $userId = $this->tokenStorage->getUser()->getId();
        $existAccounts = $this->getDoctrine()->getConnection()->fetchAll('
            SELECT ProviderID, Login2
            FROM Account
            WHERE
                    UserID = ' . $userId . '
                AND ProviderID IN (' . implode(',', array_map('intval', array_keys($result))) . ')
        ');
        $accounts = [];

        foreach ($existAccounts as $existAccount) {
            $providerId = (int) $existAccount['ProviderID'];

            if (!array_key_exists($providerId, $accounts)) {
                $accounts[$providerId] = [];
            }
            $accounts[$providerId][] = htmlspecialchars_decode($existAccount['Login2']);
        }

        foreach ($providers as $provider) {
            $providerId = (int) $provider['ProviderID'];
            $entityProvider = $this->getDoctrine()->getRepository(Provider::class)->find($providerId);

            if (!$entityProvider->isLogin2Regions()) {
                continue;
            }

            if (array_key_exists($providerId, $accounts)) {
                $accounts[$providerId] = array_unique($accounts[$providerId]);

                if (1 === count($accounts[$providerId])) {
                    if (!empty($accounts[$providerId][0])
                        && !in_array($accounts[$providerId][0], BalanceWatch::ACCOUNT_LOGIN2_EXCLUDE_VALUES)) {
                        $result[$providerId]['field_regions_value'] = $accounts[$providerId][0];
                    }
                } else {
                    $login2 = $this->getCheckerLogin2($entityProvider);

                    if (isset($login2['Options']) && array_key_exists('', $login2['Options'])) {
                        $accounts[$providerId] = ['' => $login2['Options']['']] + $accounts[$providerId];
                    }
                    $result[$providerId]['field_regions_options'] = $accounts[$providerId];
                }

                continue;
            }

            $login2 = $this->getCheckerLogin2($entityProvider);

            if (!empty($login2['Options'])) {
                $result[$providerId]['field_regions_options'] = $login2['Options'];
                $result[$providerId]['field_regions_value'] = $login2['Value'] ?? '';
            }
        }

        return $result;
    }

    private function getCheckerLogin2(Provider $entityProvider): ?array
    {
        $checker = $this->globalVariables->getAccountChecker($entityProvider, true);

        $checkerFields = ['Login2'];
        $checker->setUserFields($this->tokenStorage->getUser());
        $checker->TuneFormFields($checkerFields, []);

        if (!empty($checkerFields['Login2']['Options'])) {
            return $checkerFields['Login2'];
        }

        return null;
    }
}
