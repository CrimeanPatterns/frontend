<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\EmailTemplate\AwPlusReplacementException;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class AwPlusUsersWithoutSubscription extends AbstractAwPlusRelatedDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->awPlusUpgraded = true;
            $option->hasSubscription = false;
            $option->notBusiness = true;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'AW Plus users without subscription';
    }

    public function getTitle(): string
    {
        return 'AW Plus users without subscription';
    }

    protected function generateDataReplacements(array $fields): ?string
    {
        $fields = \array_merge(
            ['AwPlusExpirationDate' => 'AW Plus expiration date in short format: 2/20/2025'],
            $fields
        );

        return parent::generateDataReplacements($fields);
    }

    /**
     * @psalm-import-type ExpirationResult from ExpirationCalculator
     */
    protected function addReplacementsForFields(Usr $user, ?Cart $activeSubscription = null): void
    {
        parent::addReplacementsForFields($user, $activeSubscription);
        /** @var ExpirationResult $expiration */
        $expiration = $this->expirationCalculator->getAccountExpiration($user->getId());
        $date = $expiration['date'];

        if (null === $date) {
            throw new AwPlusReplacementException('Expiration date is not set');
        }

        $this->fields['AwPlusExpirationDate'] = $this->localizeService->formatDate(
            $date,
            'short',
            $user->getLocale()
        );
    }

    protected function isUserValid(Usr $user, ?Cart $cart): bool
    {
        return $user->isAwPlus() && !$user->getSubscription() && !$cart;
    }
}
