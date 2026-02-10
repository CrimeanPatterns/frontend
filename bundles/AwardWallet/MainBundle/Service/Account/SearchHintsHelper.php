<?php

namespace AwardWallet\MainBundle\Service\Account;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Класс для работы с поисковыми фильтрами, использующимися в списке аккаунтов.
 */
class SearchHintsHelper
{
    /**
     * Максимальное количество сохраняемых пользовательских подсказок.
     */
    private const MAX_HINTS = 5;

    private EntityManagerInterface $entityManager;
    private AwTokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;
    private SearchHintsDynamicFilters $dynamicFilters;
    private CacheManager $cacheManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        SearchHintsDynamicFilters $dynamicFilters,
        CacheManager $cacheManager
    ) {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->dynamicFilters = $dynamicFilters;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Сохраняет поисковую подсказку, введённую пользователем.
     *
     * @param string $value значение, пришедшее в запросе
     */
    public function update(string $value): SearchHintsUpdateResult
    {
        $user = $this->tokenStorage->getUser();
        $hints = $user->getSearchHints() ?? [];

        if ($value === '') {
            return new SearchHintsUpdateResult('"Value" cannot be blank.', $hints);
        } elseif (!empty($hints) && in_array($value, $hints)) {
            return new SearchHintsUpdateResult('"Value" must be unique.', $hints);
        }

        array_unshift($hints, $value);

        if (count($hints) > self::MAX_HINTS) {
            array_splice($hints, self::MAX_HINTS);
        }

        $this->logger->info('Search hints helper', ['search_hint' => $value]);
        $user->setSearchHints($hints);
        $this->entityManager->flush();

        return new SearchHintsUpdateResult('Saving was successful', $user->getSearchHints());
    }

    /**
     * Получить список поисковых подсказок текущего пользователя.
     *
     * @param array $accounts список всех аккаунтов пользователя
     * @throws \Exception
     */
    public function getData(array $accounts): array
    {
        $cacheReference = new CacheItemReference(
            'search_hints_dynamic_filters',
            Tags::getAllAccountsCounterTags($this->tokenStorage->getUser()->getId()),
            function () use ($accounts) {
                return $this->dynamicFilters->run($accounts);
            }
        );

        return [
            'custom' => $this->tokenStorage->getUser()->getSearchHints() ?? [],
            'default' => $this->cacheManager->load($cacheReference) ?? [],
        ];
    }
}
