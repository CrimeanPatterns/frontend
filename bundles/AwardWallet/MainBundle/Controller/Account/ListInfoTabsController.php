<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Globals\AccountInfo\Info;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ListInfoTabsController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_USER') and is_granted('READ_EXTPROP', account)")
     * @Route("/history/{id}/{offset}-{limit}",
     *            name="aw_account_getaccounthistory",
     *            options={"expose"=true},
     *            requirements={"id" = "\d+", "offset" = "\d+", "limit" = "\d+"}
     * )
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "id"})
     * @return JsonResponse
     */
    public function getAccountHistoryAction(Account $account, $offset, $limit, Request $request, Info $accountInfo)
    {
        $offset = intval($offset);
        $limit = intval($limit);

        if ($offset < 0 || $limit < 1 || $limit > 200) {
            return new JsonResponse("Bad request", 400);
        }
        $data = $accountInfo->getAccountHistory($account, $limit, $offset, true, $request->query->get('subAccountId', null));

        return (new JsonResponse($data))->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    /**
     * @Route("/balanceChart", name="aw_account_balancechart", options={"expose"=true})
     */
    public function balanceChartAction(Request $request, Info $accountInfo)
    {
        $data = $request->query->get('d');
        $labels = $request->query->get('l');
        $retina = $request->query->get('retina', 0);

        if (
            !is_string($data)
            || !is_string($labels)
            || empty($data)
            || empty($labels)
            || (!is_null($retina) && !is_numeric($retina))
        ) {
            return new Response('Bad request', 400);
        }

        if (false === strpos($data, '|') && false !== strpos($data, '%7P')) {
            $data = str_replace('%7P', '|', $data);
            $labels = str_replace(['%7P', '%2S'], ['|', '/'], $labels);
        }
        $data = explode('|', rawurldecode($data));
        $labels = explode('|', rawurldecode($labels));

        if (sizeof($data) != sizeof($labels)) {
            return new Response('Bad request', 400);
        }

        $retina = is_numeric($retina) && $retina;

        return new Response(
            $accountInfo->getAccountBalanceChart(
                $labels,
                $data,
                $retina ? 2 : 1
            ),
            200,
            [
                'Content-Type' => 'image/png',
            ]
        );
    }
}
