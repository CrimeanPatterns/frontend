<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\CartItem\Supporters3MonthsUpgrade;
use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Globals\Cart\Manager as CartManager;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\AwPlusReplacementException;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EarlyOrFullSupporterNoSubscription extends AbstractFailTolerantDataProvider
{
    protected UsrRepository $usrRepository;
    protected EntityManager $entityManager;
    protected LoggerInterface $logger;
    protected ExpirationCalculator $expirationCalculator;
    protected LocalizeService $localizeService;
    private ClockInterface $clock;
    private CartManager $cartManager;

    public function __construct(ContainerInterface $container, EmailTemplate $template)
    {
        parent::__construct($container, $template);

        $this->usrRepository = $container->get(UsrRepository::class);
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->logger = $container->get('logger');
        $this->expirationCalculator = $container->get(ExpirationCalculator::class);
        $this->localizeService = $container->get(LocalizeService::class);
        $this->clock = $container->get(ClockInterface::class);
        $this->cartManager = $container->get(CartManager::class);
    }

    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->hasEverSubscription = true;
            $option->hasVIPUpgrade = false;
            $option->hasSupporter3MUpgrade = false;
            $option->notBusiness = true;
            $option->ignoreEmailProductUpdates = true;
            $option->ignoreGroupDoNotCommunicate = true;
        });

        return $options;
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        $options[Mailer::OPTION_SKIP_DONOTSEND] = true;

        return $options;
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

            try {
                $this->addReplacementsForFields($user);

                return true;
            } catch (AwPlusReplacementException $e) {
                $this->logger->info('Error while adding replacements: ' . $e->getMessage(), [
                    'exception' => $e,
                    'UserID' => $user->getId(),
                    'aw_plus_data_provider' => self::getDataProviderName(),
                ]);
            }
        }
    }

    public function preSend(Mailer $mailer, \Swift_Message $message, &$options, bool $dryRun = false)
    {
        parent::preSend($mailer, $message, $options, $dryRun);

        if (!$dryRun) {
            $this->upgrade();
        }
    }

    public function getDescription(): string
    {
        return 'Full supporters and early supporters without active subscription';
    }

    public function getTitle(): string
    {
        return 'Full supporters and early supporters without active subscription';
    }

    protected function generateDataReplacements(array $fields): ?string
    {
        $fields = \array_merge(
            [
                'OldAwPLusExpirationDate' => 'AW Plus expiration date in short format: 2/20/25, date may be in the past',
                'NewAwPLusExpirationDate' => 'New AW Plus expiration date in short format: 2/20/25, date only in the future',
                'ExpireWord' => "'expires' or 'expired' depending on whether {{ OldAwPLusExpirationDate }} the date in the past or not",
            ],
            $fields
        );

        return parent::generateDataReplacements($fields);
    }

    protected static function getDataProviderName()
    {
        return \substr(static::class, \strrpos(static::class, '\\') + 1);
    }

    protected function addReplacementsForFields(Usr $user): void
    {
        $expirationOld = $this->expirationCalculator->getAccountExpiration($user->getId());
        $expirationOld = $expirationOld['date'];

        if (null === $expirationOld) {
            throw new AwPlusReplacementException('Expiration date is not set');
        }

        $expirationOld = new \DateTimeImmutable("@{$expirationOld}");
        $now = $this->clock->current()->getAsDateTimeImmutable();
        $this->fields['OldAwPLusExpirationDate'] = $this->formatDateShort($expirationOld, $user);

        if ($now > $expirationOld) {
            $this->fields['ExpireWord'] = 'expired';
            $this->fields['NewAwPLusExpirationDate'] = $this->formatDateShort($now->modify('+3 months'), $user);
        } else {
            $this->fields['ExpireWord'] = 'expires';
            $this->fields['NewAwPLusExpirationDate'] = $this->formatDateShort($expirationOld->modify('+3 months'), $user);
        }

        $this->logger->info('Replacements added', [
            'UserID' => $user->getId(),
            'OldAwPLusExpirationDate_str' => $this->fields['OldAwPLusExpirationDate'],
            'NewAwPLusExpirationDate_str' => $this->fields['NewAwPLusExpirationDate'],
            'ExpireWord_str' => $this->fields['ExpireWord'],
            'aw_plus_data_provider' => self::getDataProviderName(),
        ]);
    }

    private function upgrade()
    {
        $user = $this->usrRepository->find($this->fields['UserID']);

        if (!$user) {
            return;
        }

        $this->cartManager->setUser($user);
        $cart = $this->cartManager->createNewCart();
        $cart->setUser($user);
        $item = new Supporters3MonthsUpgrade();
        $cart->addItem($item);
        $cart->setPaymenttype(PAYMENTTYPE_CREDITCARD);
        $this->cartManager->markAsPayed(null, null, null, true);
    }

    private function formatDateShort(\DateTimeInterface $dateTime, Usr $user): string
    {
        return $this->localizeService->formatDate(
            $dateTime,
            'short',
            $user->getLocale()
        );
    }

    private function tryClearEm(): void
    {
        if ($this->current % 500 === 0) {
            $this->entityManager->clear();
        }
    }
}
