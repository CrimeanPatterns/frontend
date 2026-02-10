<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Service\ElasticSearch\Client;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AwPlusIncrease2024ResultsCommand extends Command
{
    private const DATE_FROM = '2024-11-17';
    private const DATE_TO = '2024-12-04';
    private const EMAIL_TEMPLATE_EARLY_SUPPORTER_ID = 929;
    private const EMAIL_TEMPLATE_FULL_SUPPORTER = 930;
    private const EMAIL_TEMPLATE_WITHOUT_SUBSCRIPTION = 932;
    private const EMAIL_TEMPLATE_FREE_MEMBERS = 933;

    /**
     * @ORM\Column(type="string")
     */
    protected static $defaultName = 'aw:plus-increase-2024-results';
    private Client $elasticSearchClient;
    private Connection $connection;

    public function __construct(
        Connection $connection,
        Client $elasticSearchClient
    ) {
        parent::__construct();

        $this->elasticSearchClient = $elasticSearchClient;
        $this->connection = $connection;
    }

    public function configure(): void
    {
        $this
            ->setDescription('Analyzes the results of the 2024 AW+ increase');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->analyzeEarlySupporter($output);
        $this->analyzeFullSupporter($output);
        $this->analyzeWithoutSubscription($output);
        $this->analyzeFreeMembers($output);
    }

    private function getEmailKind(int $emailTemplateId): string
    {
        $code = $this->connection->fetchAssociative('
            select Code, DataProvider
            from EmailTemplate
            where EmailTemplateID = :templateId',
            [':templateId' => $emailTemplateId]
        );

        if (!$code) {
            throw new \RuntimeException("Email template ({$emailTemplateId}) not found");
        }

        return "{$code['Code']}-{$code['DataProvider']}";
    }

    private function analyzeEarlySupporter(OutputInterface $output): void
    {
        $this->baseStats('Early supporters', self::EMAIL_TEMPLATE_EARLY_SUPPORTER_ID, $output);
    }

    private function baseStats(string $groupName, int $emailTemplateId, OutputInterface $output)
    {
        $users = $this->loadUsersFromTemplateES($this->getEmailKind($emailTemplateId), $output);
        $output->writeln($groupName . ' count[ES]: ' . count($users) . ', count[EmailLog]: ' . $this->countUsersFromTemplateEmailLog($emailTemplateId));
        $removedUsersCount = $this->countRemovedUsers($users);
        $output->writeln($groupName . ' removed count: ' . $removedUsersCount);
        $remainedUsers = count($users) - $removedUsersCount;
        $output->writeln($groupName . ' remained count: ' . $remainedUsers);
        $cancelledUsersCount = $this->countCancelledUsers($emailTemplateId);
        $output->writeln($groupName . ' cancelled count: ' . $cancelledUsersCount);
        $prepaidStats = $this->loadPrepaidStats($emailTemplateId);
        $output->writeln($groupName . ' prepaid stats: ' . \json_encode($prepaidStats, \JSON_PRETTY_PRINT));
        $prepaidWithSubCount = $this->countPrepaidWithSub($emailTemplateId);
        $output->writeln($groupName . ' prepaid with sub: ' . $prepaidWithSubCount);
    }

    private function loadPrepaidStats(int $emailTemplateId): array
    {
        return $this->connection->fetchAllKeyValue("
            select 
               ci.Cnt  as years,
               count(*) as count 
            from EmailLog el
            join Usr u on el.UserID = u.UserID
            join Cart c on u.UserID = c.UserID
            join CartItem ci on c.CartID = ci.CartID
            where
                el.MessageKind = :templateId
                AND (c.PayDate between :dateFrom and :dateTo)
                AND ci.TypeID = :prepaidType
            group by ci.Cnt
            order by ci.Cnt
        ", [':templateId' => $emailTemplateId, ':prepaidType' => AwPlusPrepaid::TYPE, ':dateFrom' => self::DATE_FROM, ':dateTo' => self::DATE_TO]);
    }

    private function countPrepaidWithSub(int $emailTemplateId): int
    {
        return (int) $this->connection->fetchOne("
            select 
               count(distinct c.UserID) as count 
            from EmailLog el
            join Usr u on el.UserID = u.UserID
            join Cart c on u.UserID = c.UserID
            where
                el.MessageKind = :templateId
                AND (c.PayDate between :dateFrom and :dateTo)
                AND EXISTS(
                    select 1
                    from CartItem ci
                    where c.CartID = ci.CartID
                    AND ci.TypeID = :prepaidType
                )
                AND EXISTS(
                    select 1
                    from CartItem ci
                    where c.CartID = ci.CartID
                    AND ci.TypeID = :subType
                )
        ", [':templateId' => $emailTemplateId, ':prepaidType' => AwPlusPrepaid::TYPE, ':subType' => AwPlusSubscription::TYPE, ':dateFrom' => self::DATE_FROM, ':dateTo' => self::DATE_TO]);
    }

    private function countCancelledUsers(int $emailTemplateId): int
    {
        return (int) $this->connection->fetchOne('
            select count(*)
            from EmailLog el
            join Usr u on el.UserID = u.UserID
            where
                el.MessageKind = :templateId
                AND u.Subscription IS NULL
                AND u.SubscriptionType IS NULL',
            [':templateId' => $emailTemplateId]
        );
    }

    /**
     * @param list<int> $users
     */
    private function countRemovedUsers(array $users): int
    {
        if (empty($users)) {
            return 0;
        }

        return
            it($users)
            ->chunk(500)
            ->map(fn (array $chunk) => (int) $this->connection->fetchOne(
                'select count(*) from UsrDeleted where UserID in (:users) AND DeletionDate between :dateFrom and :dateTo',
                [':users' => $chunk, ':dateFrom' => self::DATE_FROM, ':dateTo' => self::DATE_TO],
                [':users' => Connection::PARAM_INT_ARRAY, ':dateFrom' => \PDO::PARAM_STR, ':dateTo' => \PDO::PARAM_STR]
            ))
            ->sum();
    }

    private function countUsersFromTemplateEmailLog(int $emailTemplateId): int
    {
        return (int) $this->connection->fetchOne('
            select count(*)
            from EmailLog
            where MessageKind = :templateId',
            [':templateId' => $emailTemplateId]
        );
    }

    /**
     * @return list<int>
     */
    private function loadUsersFromTemplateES(string $emailKind, OutputInterface $output): array
    {
        $query = "context.kind:\"{$emailKind}\" AND message:\"email sent\" AND _exists_:UserID";
        $output->writeln("ES Query: {$query}");
        $users =
            it($this->elasticSearchClient->query(
                $query,
                new \DateTime(self::DATE_FROM),
                new \DateTime(self::DATE_TO),
                5_000,
            ))
            ->map(static fn (array $doc) => (int) $doc['UserID'])
            ->toArray();

        return \array_unique($users);
    }

    private function analyzeFullSupporter(OutputInterface $output): void
    {
        $this->baseStats('Full supporters', self::EMAIL_TEMPLATE_FULL_SUPPORTER, $output);
    }

    private function analyzeWithoutSubscription(OutputInterface $output): void
    {
        $this->baseStats('Without subscription', self::EMAIL_TEMPLATE_WITHOUT_SUBSCRIPTION, $output);
    }

    private function analyzeFreeMembers(OutputInterface $output): void
    {
        $this->baseStats('Free members', self::EMAIL_TEMPLATE_FREE_MEMBERS, $output);
    }
}
