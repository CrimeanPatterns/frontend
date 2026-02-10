<?php

namespace AwardWallet\MainBundle\Controller\Blog;

use AwardWallet\MainBundle\Command\Update\UpdateQsCreditCardCommand;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\Blog\BlogApi;
use AwardWallet\MainBundle\Service\CreditCards\QsCreditCards;
use AwardWallet\MainBundle\Service\FlightSearch\PlaceQuery;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\TransferTimes;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SyncDataController extends AbstractController
{
    private BlogApi $blogApi;

    public function __construct(
        BlogApi $blogApi
    ) {
        $this->blogApi = $blogApi;
    }

    /**
     * @Route("/api/blog/data", methods={"POST"}, name="aw_blog_api_data")
     */
    public function dataAction(
        Request $request,
        EntityManagerInterface $entityManager,
        PlaceQuery $placeQuery
    ): JsonResponse {
        $this->blogApi->checkAuth($request);

        $query = $request->request->getAlpha('query');

        if (empty($query)) {
            new JsonResponse([]);
        }

        $response = [];

        switch ($request->request->get('type')) {
            case 'provider':
                $response = $entityManager->getConnection()->fetchAllKeyValue(
                    'SELECT ProviderID, DisplayName FROM Provider WHERE (State > 0 OR ProviderID IN (?)) AND DisplayName LIKE ? ORDER BY DisplayName ASC',
                    [[Provider::AA_ID], '%' . $query . '%'],
                    [Connection::PARAM_INT_ARRAY, \PDO::PARAM_STR]
                );

                break;

            case 'placeAll':
                $response = $placeQuery->byAll($query);

                break;
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/api/blog/mileValue/export", name="aw_mileValue_export")
     * @return JsonResponse
     */
    public function mileValueExport(
        Request $request,
        MileValueService $mileValueService,
        Connection $connection
    ) {
        $this->blogApi->checkAuth($request);

        $group = $mileValueService->getFlatDataList();
        $providerIds = [];

        foreach ($group as $data) {
            $providerIds = array_merge($providerIds, array_column($data['data'], 'ProviderID'));
        }

        $codes = $connection->fetchAllKeyValue('
            SELECT ProviderID, Code
            FROM Provider
            WHERE ProviderID IN (' . implode(',', $providerIds) . ')
        ');

        foreach ($group as &$data) {
            foreach ($data['data'] as &$provider) {
                $providerId = $provider['ProviderID'];

                if (!array_key_exists($providerId, $codes)) {
                    throw new \RuntimeException('Unknown provider');
                }

                $provider['Code'] = $codes[$providerId];
            }
        }

        return new JsonResponse($group);
    }

    /**
     * @Route("/api/blog/transferTimes/export", name="aw_blog_transferstat_export")
     * @return JsonResponse
     */
    public function transferStatExport(
        Request $request,
        TransferTimes $transferTimes,
        Connection $connection
    ) {
        $this->blogApi->checkAuth($request);

        $data = $transferTimes->getData(BalanceWatch::POINTS_SOURCE_TRANSFER, null, null, [
            'expectFields' => ['BonusEndDate'],
        ]);

        if (empty($data['data'])) {
            return new JsonResponse(null);
        }

        $providerIds = array_column($data['data'], 'ProviderIDFrom');
        $providerIds = array_merge($providerIds, array_column($data['data'], 'ProviderID'));

        $providers = $connection->fetchAllAssociative(
            'SELECT ProviderID, Kind FROM Provider WHERE ProviderID IN (:ids)',
            ['ids' => array_unique($providerIds)],
            ['ids' => Connection::PARAM_INT_ARRAY],
        );
        $providers = array_column($providers, null, 'ProviderID');

        foreach ($data['data'] as &$item) {
            $item['KindFrom'] = $providers[$item['ProviderIDFrom']]['Kind'];
            $item['KindTo'] = $providers[$item['ProviderID']]['Kind'];

            $item['IsBonusExpired'] = empty($item['BonusEndDate'])
                ? null
                : time() > strtotime($item['BonusEndDate']);

            unset(
                $item['OperationsCount'],
                $item['MinDuration'],
                $item['MaxDuration'],
                $item['ProvOrderPos']
            );
        }

        return new JsonResponse($data['data']);
    }

    /**
     * @Route("/api/blog/aw_quinstreet_cards/sync", name="aw_quinstreet_cards_sync")
     * @Security("is_granted('ROLE_STAFF_BLOGGER') or is_granted('ROLE_MANAGE_MILEVALUE')")
     */
    public function updateQsCreditCards(QsCreditCards $qsCreditCards): JsonResponse
    {
        $cards = $qsCreditCards->getCreditCard([UpdateQsCreditCardCommand::FEED_URLS['aw_1']]);
        $qsCreditCards->update($cards);

        return new JsonResponse(['success' => true]);
    }
}
