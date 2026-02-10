<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendChaseEmailsCommand extends Command
{
    private const ACTION_SEND = 'send';
    private const ACTION_SKIP_DRY_RUN = 'skip, dry run';
    private const ACTION_SKIP_ERROR = 'skip, failed to load params';

    public static $defaultName = 'aw:send-chase-emails';

    private Logger $logger;

    private SqlQuery $sqlQuery;

    private ClickhouseQuery $clickhouseQuery;

    private TemplateParamLoader $templateParamLoader;

    private Sender $sender;

    /**
     * @var bool
     */
    private $dryRun;

    /**
     * @var string
     */
    private $testEmail;

    /**
     * @var string
     */
    private $cacheDir;

    private CardSelector $cardSelector;

    private Connection $connection;

    private AppProcessor $appProcessor;

    public function __construct(
        Logger $logger,
        SqlQuery $sqlQuery,
        ClickhouseQuery $clickhouseQuery,
        TemplateParamLoader $templateParamLoader,
        Sender $sender,
        CardSelector $cardSelector,
        string $cacheDir,
        Connection $connection,
        AppProcessor $appProcessor
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->sqlQuery = $sqlQuery;
        $this->clickhouseQuery = $clickhouseQuery;
        $this->templateParamLoader = $templateParamLoader;
        $this->sender = $sender;
        $this->cacheDir = $cacheDir;
        $this->cardSelector = $cardSelector;
        $this->connection = $connection;
        $this->appProcessor = $appProcessor;
    }

    public function configure()
    {
        $this
            ->setDescription('send email ads about chase cards')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'do not send emails')
            ->addOption('test-email', null, InputOption::VALUE_REQUIRED, 'send emails to this address')
            ->addOption('cache-time', null, InputOption::VALUE_REQUIRED, 'cache data for N minutes')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'process only this user')
            ->addOption('cardId', null, InputOption::VALUE_REQUIRED, 'generate emails only for this card')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'send only N emails')
            ->addOption('limit-each-template', null, InputOption::VALUE_REQUIRED, 'send N emails for each template')
            ->addOption('test-match', null, InputOption::VALUE_REQUIRED, 'test match from log, format: {"Cards":[52],"Criteria":"hyatt","ID":"R.4526453.City"}')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dryRun = $input->getOption('dry-run');
        $this->testEmail = $input->getOption('test-email');

        $cacheFile = $this->cacheDir . "/chase-email-data.json";

        if ($input->getOption('cache-time') && file_exists($cacheFile) && ((time() - filemtime($cacheFile)) / 60) < $input->getOption('cache-time')) {
            [$cardUsers, $users] = json_decode(file_get_contents($cacheFile), true);
        } else {
            $testMatch = $input->getOption('test-match');

            if ($testMatch) {
                $this->logger->info("loading test match: $testMatch");
                $cardUsers = [];
                $users = $this->loadUserFromTestMatch($testMatch);
            } else {
                $cardUsers = $this->clickhouseQuery->getUsersOfCards(Constants::CARD_IDS, $input->getOption('userId'));
                $users = $this->sqlQuery->getUsers($input->getOption('userId'));
            }
            file_put_contents($cacheFile, json_encode([$cardUsers, $users]));
        }

        $users = $this->filterUsersByCard($users, $input->getOption('cardId'));
        $users = $this->filterUsersWithCard($users, $cardUsers);
        $this->showCardStats($users);
        $users = $this->cardSelector->selectCard($users);
        $users = $this->filterUsersByTemplate($users, $input->getOption('limit-each-template'));
        $sent = 0;
        $limit = $input->getOption("limit");

        foreach ($users as $user) {
            if ($this->processUser($user)) {
                $sent++;
            }

            if (!empty($limit) && $sent >= $limit) {
                $this->logger->info("limit hit, stoppping");

                break;
            }
        }
        $this->logger->info("done, sent {$sent} emails");
    }

    private function filterUsersWithCard(array $users, array $cardUsers): array
    {
        $users = array_map(function (array $user) use ($cardUsers) {
            $excludeCards = $cardUsers[$user['UserID']] ?? [];
            $user['UserCards'] = $excludeCards;

            foreach ($excludeCards as $cardId) {
                if (isset(Constants::CARD_EXCLUDES[$cardId])) {
                    $excludeCards = array_merge($excludeCards, Constants::CARD_EXCLUDES[$cardId]);
                }
            }
            $user['Cards'] = array_diff($user['Cards'], $excludeCards);

            return $user;
        }, $users);
        $users = array_filter($users, function (array $user) {
            return count($user['Cards']) > 0;
        });
        $this->logger->info("count of users after filtering out existing cards: " . count($users));

        return $users;
    }

    private function processUser(array $user): bool
    {
        $this->appProcessor->setNewRequestId();
        $this->logger->pushProcessor(function (array $record) use ($user) {
            $record['context']['UserID'] = $user['UserID'];

            return $record;
        });

        try {
            $this->logger->info("preparing email to user {$user['UserID']}, {$user['Email']}, user cards: " . implode(", ", $user['UserCards']) . ", matched cards: " . implode(", ", $user['Cards']) . ", selected card: {$user['CardID']}, " . Constants::CARD_NAMES[$user['CardID']]);
            $cardId = $user['CardID'];
            $match = $this->selectMatch($user['Matches'], $cardId);
            $templateParams = $this->templateParamLoader->load($match['ID']);
            $action = $this->selectAction($templateParams);

            $this->logger->info(" > selected card: {$cardId}, template: {$user['Template']}, template params: " . json_encode($templateParams));

            if ($action === self::ACTION_SEND) {
                $this->sender->sendEmail($user['UserID'], $this->testEmail, $user['Template'], $templateParams, $cardId);
            }

            return $action === self::ACTION_SEND || $action === self::ACTION_SKIP_DRY_RUN;
        } finally {
            $this->logger->popProcessor();
        }
    }

    private function selectMatch(array $matches, int $cardId): array
    {
        foreach ($matches as $n => $match) {
            $this->logger->info(" > match $n: " . json_encode($match));
        }

        foreach ($matches as $n => $match) {
            if (in_array($cardId, $match['Cards'])) {
                $this->logger->info(" > selected match $n for card {$cardId}: " . json_encode($match));

                return $match;
            }
        }

        throw new \Exception("failed to find card $cardId in matches: " . json_encode($match));
    }

    private function selectAction(?array $templateParams): string
    {
        $action = self::ACTION_SEND;

        if ($templateParams === null) {
            $action = self::ACTION_SKIP_ERROR;
        }

        if ($this->dryRun) {
            $action = self::ACTION_SKIP_DRY_RUN;
        }

        $this->logger->info(" > action: $action");

        return $action;
    }

    private function filterUsersByCard(array $users, ?int $cardId): array
    {
        if ($cardId) {
            $users = array_filter($users, function (array $user) use ($cardId) {
                return in_array($cardId, $user['Cards']);
            });
            $users = array_map(function (array $user) use ($cardId) {
                $user['Cards'] = [$cardId];

                return $user;
            }, $users);
        }

        return $users;
    }

    private function showCardStats(array $users)
    {
        if (count($users) === 0) {
            return;
        }

        $cards = [];

        foreach ($users as $user) {
            foreach ($user['Cards'] as $cardId) {
                if (!isset($cards[$cardId])) {
                    $cards[$cardId] = 1;
                } else {
                    $cards[$cardId]++;
                }
            }
        }

        arsort($cards);
        $this->logger->info("we are about to send email to " . count($users) . " users, below are number of users per card");

        foreach ($cards as $cardId => $userCount) {
            $this->logger->info(" > " . Constants::CARD_NAMES[$cardId] . ": " . $userCount . " (" . round($userCount / count($users) * 100) . "%)");
        }
    }

    private function filterUsersByTemplate(array $users, ?int $templateLimit): array
    {
        if ($templateLimit === null) {
            return $users;
        }

        $byTemplate = [];

        return array_filter($users, function (array $user) use (&$byTemplate, $templateLimit): bool {
            if (!isset($byTemplate[$user['Template']])) {
                $byTemplate[$user['Template']] = 0;
            }
            $result = ($byTemplate[$user['Template']] < $templateLimit);
            $byTemplate[$user['Template']]++;

            return $result;
        });
    }

    private function loadUserFromTestMatch(string $testMatch): array
    {
        $match = json_decode($testMatch, true);
        [$type, $rowId, $property] = explode(".", $match['ID']);
        $table = Itinerary::$table[$type];
        $row = $this->connection->executeQuery("select * from $table where {$table}ID = $rowId")->fetch(FetchMode::ASSOCIATIVE);

        return [
            $row['UserID'] => [
                'UserID' => $row['UserID'],
                'Email' => $this->connection->executeQuery("select Email from Usr where UserID = ?", [$row['UserID']])->fetchColumn(),
                'Cards' => $match['Cards'],
                'Matches' => [$match],
            ],
        ];
    }
}
