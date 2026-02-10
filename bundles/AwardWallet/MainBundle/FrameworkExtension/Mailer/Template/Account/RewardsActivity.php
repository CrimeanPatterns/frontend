<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProvider\RewardsActivityProvider;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AdvtTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilder;

class RewardsActivity extends AbstractTemplate
{
    use AdvtTrait;

    public const PERIOD_WEEK = 1;
    public const PERIOD_MONTH = 2;
    public const PERIOD_DAY = 3;

    public const TO_PERSONAL = 1;
    public const TO_FM_WITH_CODE = 2;
    public const TO_FM_WITHOUT_CODE = 3;
    public const TO_BUSINESS_ADMIN = 4;
    public const TO_PERSONAL_FREE = 5;

    /**
     * @var int "PERIOD_*" constant
     */
    public $period;

    /**
     * @var \DateTime
     */
    public $fromDate;

    /**
     * @var \DateTime
     */
    public $toDate;

    /**
     * @var Usr if account owner - business, but the recipient of the email - admin
     */
    public $businessRecipient;

    public $accounts = [];

    public static function getDescription(): string
    {
        return 'Rewards Activity';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder->add('period', ChoiceType::class, [
            'label' => /** @Ignore */ 'Period',
            'choices' => [
                /** @Ignore */
                'Day' => self::PERIOD_DAY,
                /** @Ignore */
                'Week' => self::PERIOD_WEEK,
                /** @Ignore */
                'Month' => self::PERIOD_MONTH,
            ],
        ]);

        $builder->add('to', ChoiceType::class, [
            'label' => /** @Ignore */ 'To',
            'choices' => [
                /** @Ignore */
                'Personal (AW Plus)' => self::TO_PERSONAL,
                /** @Ignore */
                'Personal (without AW Plus)' => self::TO_PERSONAL_FREE,
                /** @Ignore */
                'Family member (with share code and without AW Plus)' => self::TO_FM_WITH_CODE,
                /** @Ignore */
                'Family member (without share code)' => self::TO_FM_WITHOUT_CODE,
                /** @Ignore */
                'Business admin' => self::TO_BUSINESS_ADMIN,
            ],
        ]);

        /** @var Provider[] $providers */
        $providers = Tools::getProviders($container);
        $choices = [];

        foreach ($providers as $provider) {
            $choices[$provider->getDisplayname()] = $provider->getProviderid();
        }

        $builder->add('providers', ChoiceType::class, [
            'label' => /** @Ignore */ 'Providers',
            'multiple' => true,
            'choices' => $choices,
            'attr' => ['size' => 30],
        ]);

        $builder->add('numberAccounts', NumberType::class, [
            'label' => /** @Ignore */ 'Number accounts per provider',
            'data' => 1,
        ]);

        Tools::addAdvtByAccountIdForm($builder, $container, [
            'label_attr' => [
                'title' => /** @Ignore */ 'Эмуляция ситуации изменения баланса одного аккаунта пользователя. Для выборки. 
                Он не будет показан в письме. В рассылку попадает реклама только по тем провайдерам, в аккаунтах которых были изменения баланса',
            ],
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        // personal account
        $user = Tools::createUser(ACCOUNT_LEVEL_AWPLUS);

        // family member
        $familyMember = Tools::createFamilyMember($user);

        $template = new static();
        $template->period = $options['period'] ?? self::PERIOD_DAY;
        $periodName = RewardsActivityProvider::PERIOD[$template->period];
        $template->fromDate = new \DateTime("-1 hour");
        $toDate = clone $template->fromDate;
        $template->toDate = $toDate->modify("+1 " . $periodName);

        $options['to'] = !isset($options['to']) ? self::TO_PERSONAL : $options['to'];

        if ($options['to'] == self::TO_PERSONAL) {
            $template->toUser($user, false);
        } elseif ($options['to'] == self::TO_PERSONAL_FREE) {
            $user->setAccountlevel(ACCOUNT_LEVEL_FREE);
            $template->toUser($user, false);
        } elseif (in_array($options['to'], [self::TO_FM_WITH_CODE, self::TO_FM_WITHOUT_CODE])) {
            $template->toFamilyMember($familyMember);

            if ($options['to'] == self::TO_FM_WITHOUT_CODE) {
                $familyMember->setSharecode(null);
            } else {
                $familyMember->getAgentid()->setAccountlevel(ACCOUNT_LEVEL_FREE);
            }
        } else {
            $business = clone $user;
            $business->setAccountlevel(ACCOUNT_LEVEL_BUSINESS);
            $template->toUser($user, true);
            $template->businessRecipient = $business;
        }

        if (isset($options['AdAccountID'])) {
            $template->advt = Tools::getAdvtByAccountId($container, [$options['AdAccountID']], static::getEmailKind());
        }

        $providerRep = $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $localizer = $container->get(LocalizeService::class);
        $providers = isset($options['providers']) && is_array($options['providers']) && count($options['providers']) > 0
            ? $options['providers'] : [636];
        $perProvider = isset($options['numberAccounts']) && is_numeric($options['numberAccounts']) && $options['numberAccounts'] > 0
            ? $options['numberAccounts'] : 1;

        foreach ($providers as $providerId) {
            /** @var Provider $provider */
            $provider = $providerRep->find($providerId);

            if (!$provider) {
                continue;
            }

            for ($i = 0; $i < $perProvider; $i++) {
                $template->accounts[] = [
                    'ID' => rand(1000, 99999),
                    'DisplayName' => $provider->getDisplayname(),
                    'ProviderCode' => $provider->getCode(),
                    'ProviderName' => $provider->getShortname(),
                    'Kind' => $provider->getKind(),
                    'UserName' => $user->getFullName(),
                    'Balance' => $localizer->formatNumber($balance = rand(1, 99999)),
                    'LastBalance' => $localizer->formatNumber($lastBalance = rand(1, 9999)),
                    'ChangedOverPeriodPositive' => $positive = $balance >= $lastBalance,
                    'LastChange' => ($positive ? "+" : "-") . $localizer->formatNumber(abs($balance - $lastBalance)),
                    'MainProperties' => [
                        'Login' => StringHandler::getRandomCode(6) . '@mail.com',
                    ],
                    'ExpirationDate' => '11/13/16',
                    'ExpirationKnown' => true,
                    'Properties' => [
                        'Name' => [
                            "Name" => "Name",
                            "Val" => $user->getFirstname(),
                            "Visible" => "1",
                            "SortIndex" => "10",
                        ],
                        'LastActivity' => [
                            "Name" => "Last activity",
                            "Val" => "Mar 15, 2016",
                            "Visible" => "1",
                            "SortIndex" => "30",
                        ],
                        'NightsNeededToNextLevel' => [
                            "Name" => "Nights needed to next level",
                            "Val" => "5",
                            "Visible" => "1",
                            "SortIndex" => "40",
                        ],
                        'NightsDuringCurrentMembershipYear' => [
                            "Name" => "Nights during current membership year",
                            "Val" => "5",
                            "Visible" => "1",
                            "SortIndex" => "50",
                        ],
                        'NextEliteLevel' => [
                            "Name" => "Next Elite Level",
                            "Val" => "Silver",
                            "Visible" => "1",
                            "SortIndex" => "60",
                        ],
                    ],
                    'balanceChart' => 'w=1000&h=285&email=1&l=7%2F9%2F14%7C7%2F16%2F14%7C8%2F17%2F14%7C11%2F17%2F14%7C12%2F17%2F14%7C3%2F3%2F15%7C3%2F30%2F15&d=202680%7C202776%7C203032%7C215491%7C215614%7C217114%7C275761',
                ];
            }
        }

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
