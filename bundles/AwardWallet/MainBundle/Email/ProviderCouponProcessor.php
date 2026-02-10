<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\API\Email\V2\Coupon\Coupon;
use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Repository\ProvidercouponRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ProviderCouponProcessor
{
    private AccountRepository $ar;
    private ProvidercouponRepository $pcr;
    private ProviderRepository $pr;
    private LoggerInterface $logger;
    private EntityManagerInterface $em;

    public function __construct(AccountRepository $ar, ProvidercouponRepository $pcr, ProviderRepository $pr, LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->ar = $ar;
        $this->pcr = $pcr;
        $this->pr = $pr;
        $this->logger = $logger;
        $this->em = $em;
    }

    public function process(ParseEmailResponse $data, Owner $owner): string
    {
        try {
            if (($date = $data->metadata->receivedDateTime) && (new \DateTime())->diff(new \DateTime($date))->days > 30) {
                return CallbackProcessor::SAVE_MESSAGE_FAIL;
            }
        } catch (\Exception $e) {
        }
        $provider = $this->pr->findOneBy(['code' => $data->providerCode]);
        $accounts = $this->ar->findBy(['user' => $owner->getUser(), 'providerid' => $provider]);
        $coupons = $this->pcr->findBy(['user' => $owner->getUser()]);
        $success = false;

        /** @var Coupon $cp */
        foreach ($data->coupons as $cp) {
            if ($this->skip($cp, $coupons)) {
                continue;
            }
            $this->save($cp, $owner, $provider, $accounts);
            $success = true;
        }

        return $success ? CallbackProcessor::SAVE_MESSAGE_SUCCESS : CallbackProcessor::SAVE_MESSAGE_MISSED;
    }

    /**
     * @param Providercoupon[] $coupons
     */
    private function skip(Coupon $cp, array $coupons): bool
    {
        if (empty($cp->number) || !array_key_exists($cp->type, Providercoupon::TYPES)) {
            return true;
        }

        foreach ($coupons as $userCoupon) {
            if (strcasecmp($userCoupon->getCardNumber(), $cp->number) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Account[] $accounts
     */
    private function save(Coupon $cp, Owner $owner, Provider $provider, array $accounts): void
    {
        $new = new Providercoupon();

        $new->setProgramname($provider->getDisplayname())
            ->setKind($provider->getKind())
            ->setTypeid($cp->type)
            ->setCardNumber($cp->number)
            ->setValue($cp->value)
            ->setPin($cp->pin);

        if ($cp->expirationDate) {
            try {
                $new->setExpirationdate(new \DateTime($cp->expirationDate));
            } catch (\Exception $e) {
            }
        }

        if ($cp->canExpire === false) {
            $new->setDonttrackexpiration(true);
        }

        if (empty($cp->accountNumber)) {
            if (count($accounts) === 1) {
                $new->setAccount($accounts[0]);
            }
        } else {
            $new->setAccount($this->match($cp, $accounts));
        }

        if ($new->getAccount()) {
            $new->setOwner($new->getAccount()->getOwner());
        } else {
            $new->setOwner($owner);
        }

        $this->em->persist($new);
        $this->em->flush();
        $this->logger->info('saved providercoupon', ['providerCode' => $provider->getCode(), 'number' => $new->getCardNumber()]);
    }

    /**
     * @param Account[] $accounts
     */
    private function match(Coupon $cp, array $accounts): ?Account
    {
        $match = null;

        foreach ($accounts as $account) {
            $numbers = array_filter(array_unique([$account->getLogin(), $account->getAccountNumber()]));

            foreach ($numbers as $number) {
                if ($this->matchNumber($number, $cp->accountNumber, $cp->accountMask)) {
                    if (null !== $match) {
                        return null;
                    } else {
                        $match = $account;

                        continue 2;
                    }
                }
            }
        }

        return $match;
    }

    private function matchNumber($number1, $number2, $mask2): bool
    {
        $number1 = ltrim($number1, '0');
        $number2 = ltrim($number2, '0');

        switch ($mask2) {
            case 'left':
                return preg_match('/' . preg_quote($number2) . '$/i', $number1) > 0;

            case 'right':
                return preg_match('/^' . preg_quote($number2) . '/i', $number1) > 0;

            case 'center':
                [$left, $right] = explode('**', $number2) + ['', ''];

                return strlen($left) > 0 && strlen($right) > 0 && preg_match('/^' . preg_quote($left) . '.+' . preg_quote($right) . '$/i', $number1) > 0;

            default:
                return strcasecmp($number1, $number2) === 0;
        }
    }
}
