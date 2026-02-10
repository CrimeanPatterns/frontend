<?php

namespace AwardWallet\MainBundle\Controller\Blog;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BlogCreditCardController
{
    private string $blogApiSecret;

    public function __construct(
        string $blogApiSecret
    ) {
        $this->blogApiSecret = $blogApiSecret;
    }

    /**
     * @Route("/api/blog/credit-cards", methods={"POST"}, name="aw_blog_api_credit_cards")
     * @return JsonResponse
     * @throws Exception
     */
    public function creditCardsAction(Request $request, Connection $connection)
    {
        if ($request->getPassword() !== $this->blogApiSecret) {
            throw new AccessDeniedHttpException('Invalid Authentication');
        }

        $cards = $connection->fetchAllAssociative('
            SELECT
                    cc.CreditCardID, cc.Name, cc.DisplayNameFormat, cc.IsBusiness, cc.CardFullName, cc.IsCashBackOnly, cc.IsVisibleInAll, cc.IsVisibleInBest, cc.RankIndex, cc.QsAffiliate,
                    p.ProviderID AS CobrandProviderID, p.DisplayName AS CobrandDisplayName, p.Kind AS CobrandKind,
                    qcc.QsCardInternalKey,
                    p2.ProviderID AS ProviderID, p2.DisplayName AS ProviderDisplayName, p2.Kind AS ProviderKind
            FROM CreditCard cc
            JOIN QsCreditCard qcc ON (cc.QsCreditCardID = qcc.QsCreditCardID)
            LEFT JOIN Provider p ON (p.ProviderID = cc.CobrandProviderID)
            LEFT JOIN Provider p2 ON (p2.ProviderID = cc.ProviderID)
        ');

        $offers = $connection->fetchAllAssociative('
            SELECT o.CreditCardID, o.SubjectiveValue, MAX(o.StartDate) AS lastStartDate 
            FROM CreditCardOffer o
            WHERE
                   o.EndDate IS NULL
                OR UNIX_TIMESTAMP(o.EndDate) > CURRENT_TIMESTAMP()
            GROUP BY o.CreditCardID, o.SubjectiveValue
        ');
        $offers = array_column($offers, null, 'CreditCardID');

        foreach ($cards as &$card) {
            $cardId = (int) $card['CreditCardID'];

            foreach (
                [
                    'IsBusiness',
                    'IsVisibleInAll',
                    'IsVisibleInBest',
                    'IsCashBackOnly',
                    'QsCardInternalKey',

                    'CobrandProviderID',
                    'CobrandKind',

                    'ProviderID',
                    'ProviderKind',

                    'QsAffiliate',
                ] as $key
            ) {
                $card[$key] = (int) $card[$key];
            }

            $card['SubjectiveValue'] = (int) array_key_exists($cardId, $offers)
                ? $offers[$cardId]['SubjectiveValue']
                : 0;
        }

        return new JsonResponse($cards);
    }
}
