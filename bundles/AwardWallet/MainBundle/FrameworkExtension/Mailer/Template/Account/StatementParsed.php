<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AdvtTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\MailboxOfferTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class StatementParsed extends AbstractTemplate
{
    use AdvtTrait;
    use MailboxOfferTrait;

    public $account;

    public static function getDescription(): string
    {
        return "Statement was successfully processed and account updated";
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        Tools::addAdvtByAccountIdForm($builder, $container, [
            'label' => /** @Ignore */ 'Advert for AccountID',
            'label_attr' => [
                'title' => /** @Ignore */ 'Эмуляция ситуации изменения баланса одного аккаунта пользователя. Для выборки. Он не будет показан в письме. В рассылку попадает реклама только по тем провайдерам, в аккаунтах которых были изменения баланса',
            ],
        ]);
        $builder->add('hasMailbox', CheckboxType::class, [
            'label' => /** @Ignore */ 'Has mailbox',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($user = Tools::createUser(ACCOUNT_LEVEL_AWPLUS));

        if (isset($options['AdAccountID'])) {
            $template->advt = Tools::getAdvtByAccountId($container, [$options['AdAccountID']], RewardsActivity::getEmailKind());
        }
        $template->account = [
            'ID' => 12345,
            'ProviderName' => 'Test Provider',
            'DisplayName' => 'Test Provider (Test)',
            'Kind' => 1,
            'UserName' => $user->getFullName(),
            'Balance' => 560,
            'LastBalance' => 300,
            'LastChange' => '+260',
            'ChangedOverPeriodPositive' => true,
            'MainProperties' => [
                'Login' => 'abcd@mail.com',
            ],
            'ExpirationDate' => '11/13/16',
            'ExpirationKnown' => true,
            'Properties' => [
                'Name' => [
                    "Name" => "Name",
                    "Val" => "Jessica",
                    "Visible" => "1",
                    "SortIndex" => "10",
                ],
                'UntilNextFreeNight' => [
                    "Name" => "Nights until the next Free Night",
                    "Val" => "5",
                    "Visible" => "1",
                    "SortIndex" => "20",
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

        if (isset($options['hasMailbox'])) {
            $template->hasMailbox = $options['hasMailbox'];
        }

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
