<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AdvtTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Service\ExpirationDate\ExpirationDate;
use AwardWallet\MainBundle\Service\ExpirationDate\Expire;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtObj;

class BalanceExpiration extends AbstractAccountExpirationTemplate
{
    use AdvtTrait;

    /**
     * @var array
     */
    public $accounts = [];

    public ?\DateTime $now = null;

    public static function getDescription(): string
    {
        return 'Award program point expiration notice';
    }

    public static function getKeywords(): array
    {
        return ['expiration', 'expire'];
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('accounts', TextType::class, [
            'label' => /** @Ignore */ 'Account ids (comma separated)',
            'required' => true,
            'label_attr' => [
                'title' => /** @Ignore */ 'a123 - for account #123, s123 - for subaccount #123, c123 - for coupon #123',
            ],
        ]);
        Tools::addAdvtByAccountIdForm($builder, $container, [
            'label_attr' => [
                'title' => /** @Ignore */ 'Эмуляция ситуации протухания одного аккаунта пользователя. Для выборки. Он не 
                будет показан в письме. В рассылку попадает реклама только по тем провайдерам, аккаунты которых протухают.',
            ],
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);

        $template->now = date_create();

        if (isset($options['AdAccountID'])) {
            $template->advt = Tools::getAdvtByAccountId($container, [$options['AdAccountID']], static::getEmailKind());
        }

        $accounts = [];
        $subaccounts = [];
        $coupons = [];

        foreach (array_map('trim', explode(',', $options['accounts'] ?? '')) as $accountId) {
            if (!preg_match('/^a|s|c\d+$/', $accountId)) {
                continue;
            }
            $kind = substr($accountId, 0, 1);
            $accountId = (int) substr($accountId, 1);

            switch ($kind) {
                case 'a':
                    $accounts[] = $accountId;

                    break;

                case 's':
                    $subaccounts[] = $accountId;

                    break;

                case 'c':
                    $coupons[] = $accountId;

                    break;
            }
        }

        /** @var ExpirationDate $service */
        $service = $container->get(ExpirationDate::class);
        $service->setFilter(function (QueryBuilder $builder, string $pref, string $to, string $kind) use ($accounts, $subaccounts, $coupons) {
            $e = $builder->expr();

            if ($kind === ExpirationDate::KIND_ACCOUNT) {
                if (count($accounts) > 0) {
                    $builder->andWhere(
                        $e->in('a.AccountID', ":{$pref}accountsIds")
                    );
                    $builder->setParameter(":{$pref}accountsIds", $accounts, Connection::PARAM_INT_ARRAY);
                } else {
                    $builder->andWhere('0 = 1');
                }
            }

            if ($kind === ExpirationDate::KIND_SUBACCOUNT) {
                if (count($subaccounts) > 0) {
                    $builder->andWhere(
                        $e->in('sa.SubAccountID', ":{$pref}subaccountsIds")
                    );
                    $builder->setParameter(":{$pref}subaccountsIds", $subaccounts, Connection::PARAM_INT_ARRAY);
                } else {
                    $builder->andWhere('0 = 1');
                }
            }

            if ($kind === ExpirationDate::KIND_COUPON) {
                if (count($coupons) > 0) {
                    $builder->andWhere(
                        $e->in('a.ProviderCouponID', ":{$pref}couponsIds")
                    );
                    $builder->setParameter(":{$pref}couponsIds", $coupons, Connection::PARAM_INT_ARRAY);
                } else {
                    $builder->andWhere('0 = 1');
                }
            }
        });

        $template->accounts = stmtObj($service->getStmt(ExpirationDate::MODE_EMAIL), Expire::class)
            ->filter(function (Expire $expire) {
                return !$expire->isExpiredPassport();
            })
            ->map(function (Expire $expire) use ($service) {
                return $service->prepareExpire($expire);
            })
            ->flatten(1)
            ->toArray();

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
