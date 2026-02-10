<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Repository\MerchantRepository;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\AccountHistory\AnalyserContextFactory;
use AwardWallet\MainBundle\Service\AccountHistory\MerchantMobileRecommendation;
use AwardWallet\MainBundle\Service\AccountHistory\MerchantRecommendationBuilder;
use AwardWallet\MainBundle\Service\AccountHistory\OfferCreditCardItem;
use AwardWallet\MainBundle\Service\BestCreditCards\RecommendationsResponse;
use AwardWallet\MainBundle\Service\BestCreditCards\RecommendedCard;
use Doctrine\DBAL\Connection;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class BestCreditCardsController extends AbstractController
{
    private AntiBruteforceLockerService $bestCreditCardsLocker;
    private LoggerInterface $logger;
    private SerializerInterface $serializer;
    private Connection $connection;
    private MerchantRecommendationBuilder $recommendationBuilder;
    private MerchantRepository $merchantRepository;
    private AnalyserContextFactory $analyserContextFactory;
    private RouterInterface $router;

    public function __construct(
        AntiBruteforceLockerService $bestCreditCardsLocker,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        Connection $connection,
        MerchantRecommendationBuilder $recommendationBuilder,
        MerchantRepository $merchantRepository,
        AnalyserContextFactory $analyserContextFactory,
        RouterInterface $router
    ) {
        $this->bestCreditCardsLocker = $bestCreditCardsLocker;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->recommendationBuilder = $recommendationBuilder;
        $this->merchantRepository = $merchantRepository;
        $this->analyserContextFactory = $analyserContextFactory;
        $this->router = $router;
    }

    /**
     * @Security("is_granted('ROLE_STAFF')")
     * @Route("/api/extension/v1/card-recommendations/{url}", methods={"GET"}, requirements={"url"=".+"})
     */
    public function cardRecommendationsAction(string $url): Response
    {
        $emptyResponse = new Response($this->serializer->serialize(new RecommendationsResponse(RecommendationsResponse::ACTION_NONE), 'json'));

        if ($this->bestCreditCardsLocker->checkForLockout((string) $this->getUser()->getId()) !== null) {
            $this->logger->warning("lockout for cardRecommendationsAction");

            return $emptyResponse;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (preg_match('/^https:\/\/(\w+\.)*(capitalone\.com|intuit\.com)\//ims', $host)) {
            $this->logger->info("ignored host for card recommendations: $host");

            return $emptyResponse;
        }

        $merchantIds = $this->connection->executeQuery(
            "select distinct
                ah.MerchantID
            from
                ReceivedTotal rt
                join AccountHistory ah on rt.AccountHistoryUUID = ah.UUID
            where 
                rt.ReceiveDate >= adddate(now(), interval -3 month) 
                and rt.URL like ':likePattern'", ["likePattern" => "https://{$host}%/"])->fetchFirstColumn();
        $this->logger->info("found merchants: " . implode(", ", $merchantIds));

        if (count($merchantIds) === 0) {
            return $emptyResponse;
        }

        $merchants = $this->merchantRepository->findBy(['id' => $merchantIds]);
        $recommendations = $this->recommendationBuilder->build($merchants, $this->getUser(), $this->analyserContextFactory->makeCacheContext());
        $recommendedCards = it($recommendations)
            ->map(function (MerchantMobileRecommendation $recommendation) {
                return ($recommendation->getTopRecommended() ? $recommendation->getTopRecommended()->cardId : 'none')
                    . '_' . ($recommendation->getTopHasUserCard() ? $recommendation->getTopHasUserCard()->cardId : 'none');
            })
            ->filter(fn (string $cardId) => $cardId !== 'none_none')
            ->unique()
            ->toArray();

        if (count($recommendedCards) !== 1) {
            $this->logger->info("could not find one recommended card, found multiple or zero: " . json_encode($recommendedCards));

            return $emptyResponse;
        }

        /** @var Merchant $merchant */
        $merchant = reset($merchants);

        if ($merchant->getShoppingcategory() === null) {
            $this->logger->info("merchant {$merchant->getId()} {$merchant->getName()} has no group");

            return $emptyResponse;
        }

        if ($merchant->getShoppingcategory()->getGroup() === null) {
            $this->logger->info("merchant {$merchant->getId()} {$merchant->getName()}, shopping category {$merchant->getShoppingcategory()->getId()} {$merchant->getShoppingcategory()->getName()} has no shopping category group");

            return $emptyResponse;
        }

        $response = new RecommendationsResponse(
            RecommendationsResponse::ACTION_SHOW_POPUP,
            $merchant->getName(),
            $merchant->getShoppingcategory()->getGroup()->getName(),
            $this->router->generate('aw_merchant_lookup_preload', ['merchantName' => $merchant->getName() . '_' . $merchant->getId()], RouterInterface::ABSOLUTE_URL),
        );

        /** @var MerchantMobileRecommendation $recommendation */
        $recommendation = reset($recommendations);

        if ($recommendation->getTopHasUserCard() !== null) {
            $response->yourHighestValueCard = $this->convertCard($recommendation->getTopHasUserCard());
        }

        if ($recommendation->getTopRecommended() !== null) {
            $response->overallHighestValueCard = $this->convertCard($recommendation->getTopRecommended());
        }

        $json = $this->serializer->serialize($response, 'json');

        return new Response($json);
    }

    private function convertCard(OfferCreditCardItem $cardItem): RecommendedCard
    {
        return new RecommendedCard($cardItem->picturePath, $cardItem->multiplierPlainText, $cardItem->name, $cardItem->description, $cardItem->cashEquivalent);
    }
}
