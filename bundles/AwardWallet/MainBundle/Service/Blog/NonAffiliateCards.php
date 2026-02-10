<?php

namespace AwardWallet\MainBundle\Service\Blog;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class NonAffiliateCards
{
    private const URI_CARDS_GET = 'affiliate-cards/get';

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private \CurlDriver $curlDriver;
    private BlogApi $blogApi;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        \HttpDriverInterface $curlDriver,
        BlogApi $blogApi
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->curlDriver = $curlDriver;
        $this->blogApi = $blogApi;
    }

    public function syncNonAffiliateDisclosure(): array
    {
        $fullUrl = $this->blogApi->getApiJsonUrl(self::URI_CARDS_GET);
        $response = $this->curlDriver->request(
            new \HttpDriverRequest(
                $fullUrl,
                Request::METHOD_GET,
                null,
                $this->blogApi->getAuthData(),
                Constants::REQUEST_TIMEOUT
            )
        );

        $data = json_decode($response->body, true);

        if (!is_array($data)) {
            throw new \Exception('Error in data response');
        }

        $naffCardsId = array_column($data, 'CreditCardID');

        if (empty($naffCardsId)) {
            throw new \Exception('Error in data structure');
        }

        $this->entityManager->getConnection()->update(
            'CreditCard',
            ['IsNonAffiliateDisclosure' => 0],
            ['IsNonAffiliateDisclosure' => 1]
        );

        $this->entityManager->getConnection()->executeQuery('
            UPDATE CreditCard cc
            JOIN QsCreditCard qcc ON (qcc.QsCreditCardID = cc.QsCreditCardID)
            SET cc.IsNonAffiliateDisclosure = 1
            WHERE qcc.QsCardInternalKey IN (?)
        ',
            [$naffCardsId],
            [Connection::PARAM_INT_ARRAY]
        );

        $nonAffCards = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT qcc.QsCardInternalKey, cc.CardFullName, cc.Name
            FROM CreditCard cc
            JOIN QsCreditCard qcc ON (qcc.QsCreditCardID = cc.QsCreditCardID)
            WHERE cc.IsNonAffiliateDisclosure = 1
        ');

        $nonAffCards = array_column($nonAffCards, null, 'QsCardInternalKey');
        $data = array_column($data, null, 'CreditCardID');

        foreach ($nonAffCards as $id => $card) {
            unset($data[(int) $id]);
        }

        return [
            'notFound' => $data,
            'actives' => $nonAffCards,
        ];
    }
}
