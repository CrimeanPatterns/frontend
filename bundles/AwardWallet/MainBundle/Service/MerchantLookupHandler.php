<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Entity\ShoppingCategoryGroup;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\AccountHistory\OfferQuery;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MerchantLookupHandler
{
    private MerchantLookup $merchantLookup;

    private TokenStorageInterface $tokenStorage;

    private AntiBruteforceLockerService $ipLocker;

    private AntiBruteforceLockerService $userLocker;

    private AuthorizationCheckerInterface $authorizationChecker;

    private SpentAnalysisService $analysisService;

    private EntityManagerInterface $entityManager;

    public function __construct(
        MerchantLookup $merchantLookup,
        SpentAnalysisService $analysisService,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        AntiBruteforceLockerService $securityAntibruteforceMerchantLookupIp,
        AntiBruteforceLockerService $securityAntibruteforceMerchantLookupUser,
        EntityManagerInterface $entityManager
    ) {
        $this->merchantLookup = $merchantLookup;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->ipLocker = $securityAntibruteforceMerchantLookupIp;
        $this->userLocker = $securityAntibruteforceMerchantLookupUser;
        $this->analysisService = $analysisService;
        $this->entityManager = $entityManager;
    }

    public function handleMerchantDataRequest(Request $request): JsonResponse
    {
        $error = $this->ipLocker->checkForLockout($request->getClientIp());

        if (!empty($error)) {
            throw new TooManyRequestsHttpException();
        }

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $userId = $this->tokenStorage->getToken()->getUser()->getUserid();
            $error = $this->userLocker->checkForLockout((string) $userId);
        }

        if (!empty($error)) {
            throw new TooManyRequestsHttpException();
        }

        $queryData = $request->request->get('query');

        if (!$queryData) {
            return new JsonResponse([]);
        }

        $queryData = trim($queryData);

        if (\mb_strlen($queryData) < 3) {
            return new JsonResponse([]);
        }

        $merchantList = $this->merchantLookup->getMerchantLookupList($queryData, 10);

        return new JsonResponse($merchantList);
    }

    public function handleMerchantOfferRequest(Request $request, Merchant $merchant, string $source): array
    {
        $user = null;
        $error = $this->ipLocker->checkForLockout($request->getClientIp());

        if (empty($error) && $this->authorizationChecker->isGranted('ROLE_USER')) {
            $user = $this->tokenStorage->getToken()->getUser();
            $userId = $user->getUserid();
            $error = $this->userLocker->checkForLockout((string) $userId);
        }

        if (!empty($error)) {
            throw new TooManyRequestsHttpException();
        }

        /** @var ShoppingCategoryGroup $offerCategory */
        [$cards, $grouped, $offerCategory] = $this->analysisService->buildCardsListToOffer(
            $merchant,
            $source,
            $user
        );
        $category = $offerCategory instanceof ShoppingCategoryGroup ? $offerCategory->getName() : "";

        if (empty($category) && $merchant->getShoppingcategory() instanceof ShoppingCategory) {
            $category = $merchant->getShoppingcategory()->getName();
        }

        // filter to show message: Chase dismiss categories without bonus
        $knownCategories = $this->merchantLookup->buildMerchantKnownCategories($merchant);
        $chaseUnknownFlag = false;

        foreach ($knownCategories as $providerName => $providerCategories) {
            foreach ($providerCategories as $categoryIndex => $providerCategory) {
                if ($providerCategory['providerId'] !== Provider::CHASE_ID) {
                    continue;
                }

                if (empty($providerCategory['name'])) {
                    if (count($providerCategories) === 1) {
                        $chaseUnknownFlag = true;
                        unset($knownCategories[$providerName]);
                    } else {
                        unset($knownCategories[$providerName][$categoryIndex]);
                    }

                    break 2;
                }
            }
        }

        // choosing bottom link href
        $addSourceLink = sprintf("?%s=%s", OfferQuery::SOURCE_PARAM_NAME, $source);
        $merchantPattern = $merchant->getMerchantPattern();
        $blogUrl = $merchantPattern ? $merchantPattern->getClickurl() : null;
        $isMerchantBlogLink = true;

        if (empty($blogUrl)) {
            $blogUrl = $offerCategory instanceof ShoppingCategoryGroup ? $offerCategory->getClickURL() . $addSourceLink : $cards[0]->link;
            $isMerchantBlogLink = false;
        } else {
            $blogUrl .= $addSourceLink;
        }

        $merchantName = $merchant->getDisplayName() ?? str_replace('#', '', $merchant->getName());
        $data = [
            'merchant' => $merchantName,
            'cards' => $cards,
            'grouped' => $grouped,
            'category' => html_entity_decode($category),
            'onlyCardsList' => true,
            'potentialData' => [
                'isEveryPurchaseCategory' => empty($offerCategory) ? true : false,
                'blogUrl' => $blogUrl,
                'potential' => $cards[0]->multiplier,
            ],
            'bottomLinkTitle' => $isMerchantBlogLink ? $merchantName : html_entity_decode($category),
            'knownCategories' => $knownCategories,
            'chaseUnknownFlag' => $chaseUnknownFlag,
        ];

        return $data;
    }

    public function handleExactMatchRequest(Request $request, $merchantName, string $source)
    {
        if (!\preg_match('/^(?P<name>.+)_(?P<merchantId>\d+)$/', $merchantName, $matches)) {
            return null;
        }

        $merchant = $this->entityManager->getRepository(Merchant::class)->find((int) $matches['merchantId']);

        if (!$merchant) {
            return null;
        }

        return $this->handleMerchantOfferRequest($request, $merchant, $source);
    }
}
