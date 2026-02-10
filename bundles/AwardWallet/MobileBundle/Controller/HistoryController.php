<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryQuery;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryService;
use AwardWallet\MainBundle\Service\AccountHistory\NextPageToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/account")
 */
class HistoryController
{
    /**
     * @var HistoryService
     */
    private $historyService;
    /**
     * @var MobileHistoryFormatter
     */
    private $formatter;

    public function __construct(HistoryService $historyService, MobileHistoryFormatter $formatter)
    {
        $this->historyService = $historyService;
        $this->formatter = $formatter;
        LocalizeService::defineDateTimeFormat();
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('READ_EXTPROP', account) and is_granted('CSRF') and is_granted('USER_AWPLUS')")
     * @Route("/history/{accountId}/{subAccountId}",
     *     name="awm_subaccount_history_data",
     *     methods={"POST"},
     *     requirements={"accountId"="\d+", "subAccountId"="\d+"}
     * )
     * @Route("/history/{accountId}",
     *     name="awm_account_history_data",
     *     methods={"POST"},
     *     requirements={"accountId"="\d+"}
     * )
     * @JsonDecode()
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @ParamConverter("subAccount", class="AwardWalletMainBundle:Subaccount", options={"id" = "subAccountId"})
     */
    public function dataAction(Request $request, Account $account, ?Subaccount $subAccount = null): JsonResponse
    {
        $params = array_merge($request->request->all(), $request->query->all());

        $nextPageToken = !empty($params['nextPage']) ? NextPageToken::createFromString($params['nextPage']) : null;
        $descriptionFilter = !empty($params['descriptionFilter']) ? $params['descriptionFilter'] : null;
        $query = (new HistoryQuery($account, $descriptionFilter, $nextPageToken))
                 ->setFormatter($this->formatter);

        if ($subAccount instanceof Subaccount) {
            $query->setSubAccount($subAccount)
                  ->setOfferCards(isset($params['offerFilterIds']) && is_array($params['offerFilterIds']) ? $params['offerFilterIds'] : null);
        }

        if (
            isset($params['limit'])
            && \is_numeric($params['limit'])
            && ($params['limit'] > 0)
            && ($params['limit'] <= 500)
        ) {
            $limit = (int) $params['limit'];
        } else {
            $limit = 100;
        }

        $query->setLimit($limit);
        $history = $this->historyService->getHistory($query);

        return new JsonResponse([
            'rows' => $history['historyRows'] ?? [],
            'nextPageToken' => $history['nextPageToken'] ?? null,
            'descriptionFilter' => $params['descriptionFilter'] ?? null,
            'offerFilterIds' => null,
        ]);
    }
}
