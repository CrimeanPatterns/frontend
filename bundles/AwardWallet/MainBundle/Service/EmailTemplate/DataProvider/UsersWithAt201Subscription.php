<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\EmailTemplate\AwPlusReplacementException;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @psalm-import-type ExpirationResult from ExpirationCalculator
 */
class UsersWithAt201Subscription extends AbstractAwPlusRelatedDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->hasSubscription = true;
            $option->hasSubscriptionType = Options::SUBSCRIPTION_TYPE_AT201;
            $option->notBusiness = true;
            $option->ignoreGroupDoNotCommunicate = true;
            $option->ignoreEmailProductUpdates = true;
        });

        return $options;
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        $options[Mailer::OPTION_SKIP_DONOTSEND] = true;

        return $options;
    }

    public function getDescription(): string
    {
        return 'All users with AT201 subscription';
    }

    public function getTitle(): string
    {
        return 'All users with AT201 subscription';
    }

    protected function generateDataReplacements(array $fields): ?string
    {
        $fields = \array_merge(
            ['AwPlusRenewalDate' => 'AW Plus renewal date in short format: 2/20/2025'],
            $fields
        );

        return parent::generateDataReplacements($fields);
    }

    protected function isUserValid(Usr $user, ?Cart $cart): bool
    {
        // if (self::isInManualList($user)) {
        //     return $user->getSubscription();
        // }

        if (!$cart) {
            return false;
        }

        return
            $user->getSubscription()
            && ($user->getSubscriptionType() === Usr::SUBSCRIPTION_TYPE_AT201)
            && $cart->getAT201Item();
    }

    protected function addReplacementsForFields(Usr $user, ?Cart $activeSubscription = null): void
    {
        parent::addReplacementsForFields($user, $activeSubscription);
        /** @var ExpirationResult $expiration */
        $expiration = $this->expirationCalculator->getAccountExpiration($user->getId(), AT201SubscriptionInterface::class);
        $date = $expiration['date'];

        if (null === $date) {
            throw new AwPlusReplacementException('Expiration date is not set');
        }

        if ($activeSubscription) {
            /** @var AwPlusUpgradableInterface[] $acceptedItems */
            $acceptedItems =
                it($activeSubscription->getItems())
                ->filter(fn (object $item) => $item instanceof AwPlusUpgradableInterface)
                ->toArray();

            if (\count($acceptedItems) !== 1) {
                throw new AwPlusReplacementException('Error finding accepted cart items, found: ' . \count($acceptedItems));
            }

            $renewalDate = self::tryAlterRenewalDateWithBonusYear($date, $acceptedItems[0]->getDuration());
        } else {
            $renewalDate = self::tryAlterRenewalDateWithBonusYear($date);
        }

        $this->fields['AwPlusRenewalDate'] = $this->localizeService->formatDate(
            $renewalDate,
            'short',
            $user->getLocale()
        );
    }
}
