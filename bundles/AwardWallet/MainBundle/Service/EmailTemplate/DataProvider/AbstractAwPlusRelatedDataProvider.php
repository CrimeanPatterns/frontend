<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\UpgradeCodeGenerator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\AwPlusReplacementException;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractAwPlusRelatedDataProvider extends AbstractFailTolerantDataProvider
{
    private const MANUAL_USERS_LIST = [
        874453 => null,
        54534 => null,
        626368 => null,
        733340 => null,
        734144 => null,
        784123 => null,
        785477 => null,
        786875 => null,
        874996 => null,
        875670 => null,
        875822 => null,
        877162 => null,
        877260 => null,
        877491 => null,
        877557 => null,
        877803 => null,
        877887 => null,
        878197 => null,
        878808 => null,
        880432 => null,
        880551 => null,
        880792 => null,
        880962 => null,
        881266 => null,
        882514 => null,
        882945 => null,
        884817 => null,
        886682 => null,
        886880 => null,
        886945 => null,
        140025 => null,
        549784 => null,
        603735 => null,
        753718 => null,
        795197 => null,
        883811 => null,
        884032 => null,
        884076 => null,
        67475 => null,
        872661 => null,
        872750 => null,
        283532 => null,
        537837 => null,
        884221 => null,
        916348 => null,
        945481 => null,
        27500 => null,
        53343 => null,
        69617 => null,
        166774 => null,
        373285 => null,
        378380 => null,
        433169 => null,
        456300 => null,
        509624 => null,
        566745 => null,
        675847 => null,
        775019 => null,
        692804 => null,
        339362 => null,
        889112 => null,
    ];
    protected CartRepository $cartRepository;
    protected UsrRepository $usrRepository;
    protected EntityManager $entityManager;
    protected LoggerInterface $logger;
    protected ExpirationCalculator $expirationCalculator;
    protected LocalizeService $localizeService;
    protected static \DateTimeImmutable $AW_PLUS_PRICE_INCREASE_DATE_2024;
    protected UpgradeCodeGenerator $upgradeCodeGenerator;

    public function __construct(ContainerInterface $container, EmailTemplate $template)
    {
        if (!isset(self::$AW_PLUS_PRICE_INCREASE_DATE_2024)) {
            self::$AW_PLUS_PRICE_INCREASE_DATE_2024 = new \DateTimeImmutable('2024-12-18 00:00:00');
            // self::$AW_PLUS_BONUS_YEAR_BEFORE_PRICE_INCREASE_START_DATE = new \DateTimeImmutable('2024-12-16 00:00:00');
        }

        parent::__construct($container, $template);

        $this->cartRepository = $container->get(CartRepository::class);
        $this->usrRepository = $container->get(UsrRepository::class);
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->logger = $container->get('logger');
        $this->expirationCalculator = $container->get(ExpirationCalculator::class);
        $this->localizeService = $container->get(LocalizeService::class);
        $this->upgradeCodeGenerator = $container->get(UpgradeCodeGenerator::class);
    }

    public function next(/** bool $handleCount = true */)
    {
        $handleCount = true;

        if (\func_num_args() > 0) {
            $handleCount = \func_get_arg(0);
        }

        while (true) {
            $hasNext = parent::next($handleCount);

            if (!$hasNext) {
                // stop MailerCollection loop
                $this->tryClearEm();

                return false;
            }

            $this->tryClearEm();
            $user = $this->usrRepository->find($this->fields['UserID']);

            if (!$user) {
                // skip invalid user, grab next one
                continue;
            }

            $activeSubscription = $this->loadCart($user);
            $isUserValid = $this->isUserValid($user, $activeSubscription);

            if ($isUserValid) {
                try {
                    $this->addReplacementsForFields($user, $activeSubscription);

                    return true;
                } catch (AwPlusReplacementException $e) {
                    $this->logger->info('Error while adding replacements: ' . $e->getMessage(), [
                        'exception' => $e,
                        'UserID' => $user->getId(),
                        'aw_plus_data_provider' => self::getDataProviderName(),
                    ]);
                }
            }

            $this->logger->info('UserID was skipped on last mile check', [
                'UserID' => $user->getId(),
                'aw_plus_data_provider' => self::getDataProviderName(),
                'reason' => isset($e) ? 'exception' : 'last_mile_check',
            ]);
        }
    }

    protected function loadCart(Usr $user): ?Cart
    {
        return $this->cartRepository->getActiveAwSubscription($user, true, true);
    }

    protected function generateDataReplacements(array $fields): ?string
    {
        $fields = \array_merge(
            ['PrePaymentHash' => 'unique hash for user inside pre-payment page link'],
            $fields
        );

        return parent::generateDataReplacements($fields);
    }

    protected static function getDataProviderName()
    {
        return \substr(static::class, \strrpos(static::class, '\\') + 1);
    }

    protected function addReplacementsForFields(Usr $user, ?Cart $activeSubscription = null): void
    {
        $this->fields['PrePaymentHash'] = $this->upgradeCodeGenerator->generateCode($user);
    }

    abstract protected function isUserValid(Usr $user, ?Cart $cart): bool;

    protected static function tryAlterRenewalDateWithBonusYear(int $expirationDateTs, string $modify = '+1 year'): \DateTimeImmutable
    {
        $renewalDate = new \DateTimeImmutable("@{$expirationDateTs}");

        if ($renewalDate < self::$AW_PLUS_PRICE_INCREASE_DATE_2024) {
            $renewalDate = $renewalDate->modify($modify);
        }

        return $renewalDate;
    }

    protected static function isInManualList(Usr $user): bool
    {
        return \array_key_exists($user->getId(), self::MANUAL_USERS_LIST);
    }

    private function tryClearEm(): void
    {
        if ($this->current % 500 === 0) {
            $this->entityManager->clear();
        }
    }
}
