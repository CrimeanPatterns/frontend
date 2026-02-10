<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\TransferTimes;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TransferTimesController extends AbstractController
{
    private Connection $db;
    private LocalizeService $localizer;
    private TransferTimes $transferTimes;

    public function __construct(
        Connection $db,
        LocalizeService $localizer,
        TransferTimes $transferTimes
    ) {
        $this->db = $db;
        $this->localizer = $localizer;
        $this->transferTimes = $transferTimes;
    }

    /**
     * @Route(
     *     "/mile-transfer-times",
     *     name="aw_mile_transfer_times_index",
     *     defaults={
     *          "_canonical"="aw_mile_transfer_times_index_locale",
     *          "_alternate"="aw_mile_transfer_times_index_locale"
     *     },
     *     options={"expose"=true}
     * )
     * @Route(
     *     "/{_locale}/mile-transfer-times",
     *     name="aw_mile_transfer_times_index_locale",
     *     defaults={
     *          "_locale"="en",
     *          "_canonical"="aw_mile_transfer_times_index_locale",
     *          "_alternate"="aw_mile_transfer_times_index_locale"
     *     },
     *     requirements={"_locale" = "%route_locales%"},
     *     options={"expose"=true}
     * )
     * @Template("@AwardWalletMain/TransferTimes/transferTimes.html.twig")
     * @return mixed|RedirectResponse
     */
    public function transferTimesAction()
    {
        return [];
    }

    /**
     * @Route(
     *     "/mile-purchase-times",
     *     name="aw_mile_purchase_times_index",
     *     options={"expose"=true},
     *     defaults={
     *          "_canonical"="aw_mile_purchase_times_index_locale",
     *          "_alternate"="aw_mile_purchase_times_index_locale"
     * })
     * @Route(
     *     "/{_locale}/mile-purchase-times",
     *     name="aw_mile_purchase_times_index_locale",
     *     defaults={
     *          "_locale"="en",
     *          "_canonical"="aw_mile_purchase_times_index_locale",
     *          "_alternate"="aw_mile_purchase_times_index_locale"
     *     },
     *     requirements={"_locale" = "%route_locales%"}, options={"expose"=true}
     * )
     * @return mixed|RedirectResponse
     */
    public function purchaseTimesAction()
    {
        return $this->render(
            '@AwardWalletMain/TransferTimes/transferTimes.html.twig',
            []
        );
    }

    /**
     * @Route("/mile-transfer-times-table", name="aw_mile_transfer_index", options={"expose"=true})
     * @Template("@AwardWalletMain/TransferTimes/transfer.html.twig")
     * @return mixed|RedirectResponse
     */
    public function transferAction(Request $request)
    {
        return $this->render("@AwardWalletMain/TransferTimes/transfer.html.twig");
    }

    /**
     * @Route("/mile-purchase-times-table", name="aw_mile_purchase_index", options={"expose"=true})
     * @Template("@AwardWalletMain/TransferTimes/purchase.html.twig")
     * @return mixed|RedirectResponse
     */
    public function purchaseAction(Request $request)
    {
        return $this->render("@AwardWalletMain/TransferTimes/purchase.html.twig");
    }

    /**
     * @Route("/mile-transfers", name="aw_mile_transfers", methods={"POST"}, options={"expose"=true})
     * @return JsonResponse
     */
    public function getTransfersAction(Request $request)
    {
        $source = (int) $request->get('pointsSource');

        if ($this->transferTimes->checkPointSource($source) !== true) {
            return new JsonResponse([]);
        }

        return new JsonResponse($this->transferTimes->getData($source)['data']);
    }

    /**
     * @Route("/update-mile-transfer-times", name="aw_update_mile_transfer_times_index", options={"expose"=true})
     * @Template("@AwardWalletMain/TransferTimes/updateTimes.html.twig")
     * @return mixed|RedirectResponse
     */
    public function updateTimesAction(Request $request)
    {
        return [
            'data' => $this->transferTimes->updateTransferTimes(),
        ];
    }
}
