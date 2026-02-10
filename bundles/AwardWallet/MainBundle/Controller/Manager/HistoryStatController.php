<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExpirationTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\HistoryTypesTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\SqlTask;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route ("/manager/history/stat")
 */
class HistoryStatController extends AbstractController
{
    use JsonTrait;

    /**
     * @Security("is_granted('ROLE_MANAGE_REPORTTOTALS')")
     * @Route("/{code}", name="aw_manager_history_stat", methods={"GET"}, requirements={"code"="[a-z\d]+"}, defaults={"code" = "marriott"})
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider")
     * @Template("@AwardWalletMain/Manager/HistoryStat/index.html.twig")
     */
    public function indexAction(Provider $provider)
    {
        return [
            'providerName' => $provider->getShortname(),
            'providerCode' => $provider->getCode(),
            'requestId' => StringHandler::getRandomCode(20),
            'supportedProviders' => ['marriott', 'spg'],
        ];
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REPORTTOTALS')")
     * @Route("/{code}/redemption-by-status/{requestId}", name="aw_manager_history_redemptions_by_status", methods={"GET"}, requirements={"code"="[a-z\d]+", "requestId"="[a-z\d]{1,20}"})
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider")
     */
    public function redemptionsByStatusAction(Provider $provider, $requestId, Process $asyncProcess)
    {
        return $this->jsonResponse($asyncProcess->execute(new SqlTask("
		select
			status.Val as Status,
			sum(abs(h.Miles)) as Points,
			count(*) as Transactions,
			count(distinct h.AccountID) as Accounts
		from
			AccountHistory h
			join Account a on h.AccountID = a.AccountID
			left outer join ( /* there can be doubles with SubAccountID = null */
				select
					ap.AccountID,
					max(ap.Val) as Val
				from
					AccountProperty ap
					join ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID
				where
					pp.ProviderID = :providerId and pp.Kind = 3 and ap.SubAccountID is null
				group by ap.AccountID
			) as status on h.AccountID = status.AccountID
		where
			a.ProviderID = :providerId
			and h.Miles < 0
			and h.PostingDate >= '" . (date("Y") - 1) . "-01-01' and h.PostingDate < '" . date("Y") . "-01-01'
		group by
			status.Val
		with rollup
		", ["providerId" => $provider->getProviderid()], $requestId)));
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REPORTTOTALS')")
     * @Route("/{code}/redemption-by-type/{requestId}", name="aw_manager_history_redemptions_by_type", methods={"GET"}, requirements={"code"="[a-z\d]+", "requestId"="[a-z\d]{1,20}"})
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider")
     */
    public function redemptionsByTypeAction(Provider $provider, $requestId, \Symfony\Component\HttpFoundation\Request $request, Process $asyncProcess)
    {
        return $this->jsonResponse($asyncProcess->execute(new HistoryTypesTask(
            $provider->getProviderid(),
            true,
            ["\\AwardWallet\\Engine\\" . $provider->getCode() . "\\History\\RedemptionMapper", "map"],
            $requestId,
            $request->query->has("rawData"),
            $request->query->get("filter"),
            "Type"
        )));
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REPORTTOTALS')")
     * @Route("/{code}/stays-by-nights/{requestId}", name="aw_manager_history_stays_by_night", methods={"GET"}, requirements={"code"="[a-z\d]+", "requestId"="[a-z\d]{1,20}"})
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider")
     */
    public function staysByNightAction(Provider $provider, $requestId, \Symfony\Component\HttpFoundation\Request $request, Process $asyncProcess)
    {
        return $this->jsonResponse($asyncProcess->execute(new HistoryTypesTask(
            $provider->getProviderid(),
            true,
            ["\\AwardWallet\\Engine\\" . $provider->getCode() . "\\History\\NightMapper", "map"],
            $requestId,
            $request->query->has("rawData"),
            $request->query->get("filter"),
            "# of Nights"
        )));
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REPORTTOTALS')")
     * @Route("/{code}/stays-by-category/{requestId}", name="aw_manager_history_stays_by_category", methods={"GET"}, requirements={"code"="[a-z\d]+", "requestId"="[a-z\d]{1,20}"})
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider")
     */
    public function staysByCategoryAction(Provider $provider, $requestId, \Symfony\Component\HttpFoundation\Request $request, Process $asyncProcess)
    {
        return $this->jsonResponse($asyncProcess->execute(new HistoryTypesTask(
            $provider->getProviderid(),
            true,
            ["\\AwardWallet\\Engine\\" . $provider->getCode() . "\\History\\HotelCategoryMapper", "map"],
            $requestId,
            $request->query->has("rawData"),
            $request->query->get("filter"),
            "Category"
        )));
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REPORTTOTALS')")
     * @Route("/{code}/earnings-by-type/{requestId}", name="aw_manager_history_earnings_by_type", methods={"GET"}, requirements={"code"="[a-z\d]+", "requestId"="[a-z\d]{1,20}"})
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider")
     */
    public function earningsByTypeAction(Provider $provider, $requestId, \Symfony\Component\HttpFoundation\Request $request, Process $asyncProcess)
    {
        return $this->jsonResponse($asyncProcess->execute(new HistoryTypesTask(
            $provider->getProviderid(),
            false,
            ["\\AwardWallet\\Engine\\" . $provider->getCode() . "\\History\\EarningMapper", "map"],
            $requestId,
            $request->query->has("rawData"),
            $request->query->get("filter"),
            "Type"
        )));
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REPORTTOTALS')")
     * @Route("/{code}/expired/{requestId}", name="aw_manager_history_expired", methods={"GET"}, requirements={"code"="[a-z\d]+", "requestId"="[a-z\d]{1,20}"})
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider")
     */
    public function expiredAction(Provider $provider, $requestId, \Symfony\Component\HttpFoundation\Request $request, Process $asyncProcess)
    {
        return $this->jsonResponse($asyncProcess->execute(new ExpirationTask(
            $provider->getProviderid(),
            $requestId,
            $request->query->has("rawData")
        )));
    }
}
