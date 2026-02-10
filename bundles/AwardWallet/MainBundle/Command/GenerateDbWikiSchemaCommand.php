<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class GenerateDbWikiSchemaCommand extends Command
{
    protected static $defaultName = 'aw:generate-db-wiki-schema';

    /**
     * @var DialogHelper
     */
    private $dialog;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();
        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->setDescription('Generate awardwallet database schema in wiki format')
            ->addOption('wiki-file', 'f', InputOption::VALUE_OPTIONAL, "generate migration from specified wiki-file and")
            ->addOption('db-dump', 'd', InputOption::VALUE_NONE, 'dump existing comments')
            ->addOption('interactive-merge', 'i', InputOption::VALUE_NONE, 'merge databse and wiki data interactively')
            ->addOption('replace-empty', 'r', InputOption::VALUE_NONE, 'auro-replace empty comments in interactive mode')
            ->addOption('force-yes', 'y', InputOption::VALUE_NONE, 'answer yes for all "replace" questions')
            ->addOption('skip-removed', null, InputOption::VALUE_NONE, 'skip removed columns\tables')
            ->addArgument('output', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $consoleInput, OutputInterface $consoleOutput): int
    {
        $this->dialog = $this->getHelperSet()->get('dialog');

        // parse options
        $inputFile = $consoleInput->getOption('wiki-file');

        $outputFile = $consoleInput->getArgument('output');

        if (isset($outputFile)) {
            if (false !== $outputHandler = fopen($outputFile, 'w')) {
                $outStream = new StreamOutput($outputHandler);
            } else {
                throw new \Exception("Can not open file '{$outputFile}' in write mode");
            }
        } else {
            $outStream = $consoleOutput;
        }

        $dbDump = $consoleInput->getOption('db-dump');
        $interactiveMerge = $consoleInput->getOption('interactive-merge');

        $replaceEmpty = $consoleInput->getOption('replace-empty');

        if ($replaceEmpty && !$interactiveMerge) {
            throw new \Exception("Replace-empty mode must be set with interactive merge option");
        }

        $skipRemoved = $consoleInput->getOption('skip-removed');

        $forceYes = $consoleInput->getOption('force-yes');

        if ($replaceEmpty && !$interactiveMerge) {
            throw new \Exception("force-yes mode must be set with interactive merge option");
        }

        if ($interactiveMerge && !isset($inputFile)) {
            throw new \Exception("Output file must be specified with interactive merge option");
        }

        /**
         * collect data(table, name, type, nullable etc.) about all columns in awardwallet database
         * as MySQL does not simply allow to flawlessly change column comment.
         */
        $stmt = $this->connection->executeQuery("
        SELECT
            ic.TABLE_NAME,
            it.TABLE_COMMENT,
            ic.COLUMN_NAME,
            ic.COLUMN_COMMENT,
            CONCAT('ALTER TABLE `',
                ic.table_name,
                '` MODIFY `',
                ic.column_name,
                '` ',
                ic.column_type,
                ' ',
                IF(ic.is_nullable = 'YES', '' , 'NOT NULL '),
                IF(ic.column_default IS NOT NULL, concat('DEFAULT ', IF(ic.column_default = 'CURRENT_TIMESTAMP', ic.column_default, CONCAT('\'',column_default,'\'') ), ' '), ''),
                IF(ic.column_default IS NULL AND ic.is_nullable = 'YES' AND ic.column_key = '' AND ic.column_type = 'timestamp','NULL ', ''),
                IF(ic.column_default IS NULL AND ic.is_nullable = 'YES' AND ic.column_key = '','DEFAULT NULL ', ''),
                ic.extra,
                ' COMMENT ') as alterSQL
        FROM INFORMATION_SCHEMA.COLUMNS ic
        JOIN INFORMATION_SCHEMA.TABLES it ON
            ic.TABLE_NAME = it.TABLE_NAME
        WHERE
            ic.TABLE_SCHEMA = 'awardwallet' AND
            it.TABLE_SCHEMA = 'awardwallet'

        ORDER BY
            ic.TABLE_NAME,
            ic.ORDINAL_POSITION");
        $rows = $stmt->fetchAll(Query::HYDRATE_ARRAY);

        if (empty($rows)) {
            throw new \Exception("Can't find tables");
        }
        $dbData = [];

        foreach ($rows as $column) {
            if (!isset($dbData[$column['TABLE_NAME']])) {
                // new table in list
                $dbData[$column['TABLE_NAME']]['staleColumns'] = true;
                $dbData[$column['TABLE_NAME']]['comment'] = $column['TABLE_COMMENT'];
            }

            if (isset($column['COLUMN_COMMENT'])) {
                $dbData[$column['TABLE_NAME']]['staleColumns'] = false;
            }
            $column['comment'] = $column['COLUMN_COMMENT'];
            $dbData[$column['TABLE_NAME']]['columns'][$column['COLUMN_NAME']] = $column;
        }

        if (isset($inputFile) && !$dbDump) {
            /**
             * parse textile file exported from redmine wiki.
             */
            if (!file_exists($inputFile)) {
                throw new \Exception("Can not find specified wiki-file '{$inputFile}'");
            }
            $fileContent = trim(file_get_contents($inputFile));
            $lines = explode("\n", $fileContent);

            if (empty($lines)) {
                throw new \Exception("Wiki file is empty");
            }
            $multiline = false;
            $multilineComment = [];
            $parsedData = [];
            $currentTable = null;

            foreach ($lines as $lineCount => $line) {
                $lineCount++;
                $line = trim($line, "\n\r\0\x0B");

                // skip empty lines
                if (0 === strlen(trim($line))) {
                    continue;
                }

                if (!$multiline && preg_match('/^h([12])\. (.+)$/ims', $line, $matches)) {
                    switch ($matches[1]) {
                        case '1':
                            if (!isset($parsedData[$matches[2]])) {
                                $currentTable = $matches[2];
                            } else {
                                throw new \Exception("Wiki file format error: expected \"|Column1|Column commnet|\" table, line {$lineCount}, \"{$line}\"");
                            }

                            break;

                        case '2':
                            if (!isset($currentTable)) {
                                throw new \Exception("Wiki file format error: missing \"h1. TableName\" definition, line {$lineCount}, \"{$line}\"");
                            } else {
                                if (isset($currentTable) && isset($parsedData[$currentTable])) {
                                    throw new \Exception("Wiki file format error: unexpected table description, line {$lineCount}, \"{$line}\"");
                                } else {
                                    $parsedData[$currentTable]['comment'] = $matches[2];
                                }
                            }

                            break;
                    }

                    continue;
                }

                if (isset($currentTable)) {
                    if ('|' === $line[0]) {
                        // check for existing h1 and h2
                        if (preg_match('/^\|([^\|]+)\|(.*)$/', trim($line), $matches) && !$multiline) {
                            if (('' !== $matches[2]) && preg_match('/([^\|]*)\|\s*$/', $matches[2], $subMatches)) {
                                // skip empty comments
                                if ('' !== $subMatches[1]) {
                                    // simple |Table|Column|Comment|
                                    $parsedData[$currentTable]['columns'][$matches[1]] = $subMatches[1];
                                    $multiline = false;
                                    $multilineComment = [];
                                }
                            } else {
                                $multilineComment = [
                                    'column' => $matches[1],
                                    'comment' => $matches[2],
                                ];
                                $multiline = true;
                            }
                        } else {
                            throw new \Exception("Wiki file format error: line {$lineCount}, \"{$line}\"");
                        }
                    } else {
                        if ($multiline) {
                            if (preg_match('/([^\|]*)\|$\s*/', $line, $matches)) {
                                // |Table1|Column|Some conmment line1
                                // some comment line2|
                                $parsedData[$currentTable]['columns'][$multilineComment['column']] = $multilineComment['comment'] . "\n" . $matches[1];
                                $multiline = false;
                                $multilineComment = [];
                            } else {
                                // |Table1|Column|Some conmment line1
                                // some comment line2
                                // some comment line3|
                                $multilineComment['comment'] .= "\n" . $line;
                            }
                        } else {
                            throw new \Exception("Wiki file format error: line {$lineCount}, \"{$line}\"");
                        }
                    }
                } else {
                    throw new \Exception("Wiki file format error: missing h1\\\\h2 description, line {$lineCount}, \"{$line}\"");
                }
            }
            unset($line);

            /**
             * generate migration's up() method content.
             */
            if (!empty($parsedData)) {
                if ($interactiveMerge) {
                    foreach ($dbData as $tableName => $tableData) {
                        // merge table comment
                        if (
                            isset($parsedData[$tableName]['comment'])
                            && strcmp(trim($parsedData[$tableName]['comment']), trim($tableData['comment'])) !== 0
                            && 0 !== strlen(trim($parsedData[$tableName]['comment']))
                            && false === strpos($parsedData[$tableName]['comment'], '[CHANGE_ME]')
                            && (mb_strlen($tableData['comment']) !== 0 || !$replaceEmpty)
                        ) {
                            $default = 'y';

                            if (0 !== strlen(trim($parsedData[$tableName]['comment'])) && 0 !== strlen(trim($tableData['comment']))) {
                                $default = 'n';
                            }

                            $replaced = $forceYes ? 'y' : $this->askForReplace(
                                $consoleOutput,
                                "Would you like to replace existing table comment for '{$tableName}' ?",
                                $default,
                                $tableData['comment'],
                                $parsedData[$tableName]['comment']
                            );

                            if (!$replaced) {
                                $parsedData[$tableName]['comment'] = $dbData[$tableName]['comment'];
                            }
                        }

                        // merge column comments
                        foreach ($tableData['columns'] as $columnName => $columnData) {
                            if (
                                isset($parsedData[$tableName]['columns'][$columnName])
                                && strcmp(trim($parsedData[$tableName]['columns'][$columnName]), trim($columnData['comment'])) !== 0
                                && 0 !== strlen(trim($parsedData[$tableName]['columns'][$columnName]))
                                && (mb_strlen($columnData['comment']) !== 0 || !$replaceEmpty)
                            ) {
                                $default = 'y';

                                if (0 !== strlen(trim($parsedData[$tableName]['columns'][$columnName])) && 0 !== strlen(trim($columnData['comment']))) {
                                    $default = 'n';
                                }
                                $replaced = $forceYes ? 'y' : $this->askForReplace(
                                    $consoleOutput,
                                    "Would you like to replace existing column comment for '{$tableName}.{$columnName}' ?",
                                    $default,
                                    $columnData['comment'],
                                    $parsedData[$tableName]['columns'][$columnName]
                                );

                                if (!$replaced) {
                                    $parsedData[$tableName]['columns'][$columnName] = $dbData[$tableName]['columns'][$columnName]['comment'];
                                }
                            }
                        }
                    }
                }

                foreach ($parsedData as $tableName => $tableData) {
                    if (
                        isset($tableData['comment'])
                        && (0 !== strlen(trim($tableData['comment'])))
                        && (false === strpos($tableData['comment'], '[CHANGE_ME]')) // skip default description
                    ) {
                        $this->writeSQLComment($outStream, 'ALTER TABLE ' . $this->connection->quoteIdentifier($tableName) . ' COMMENT ', $tableData['comment']);
                    }

                    if (!empty($tableData['columns'])) {
                        foreach ($tableData['columns'] as $column => $comment) {
                            if (!isset($dbData[$tableName]['columns'][$column])) {
                                if ($skipRemoved) {
                                    continue;
                                }
                                // try to find appropriate column name candidates
                                $columnFound = false;

                                if ($interactiveMerge) {
                                    $columnSelect = $this->dialog->select(
                                        $consoleOutput,
                                        "<question>Column '{$tableName}.{$column}' is absent in DB structure, please select existing column name or skip</question>",
                                        $choices = array_merge(
                                            array_column($dbData[$tableName]['columns'], 'COLUMN_NAME'),
                                            [
                                                's' => 'Skip column',
                                                'a' => 'Abort merge',
                                            ]),
                                        null,
                                        3
                                    );

                                    if ('s' === $columnSelect) {
                                        continue;
                                    } elseif ('a' === $columnSelect) {
                                        throw new \Exception("Merge aborted by user");
                                    } else {
                                        $columnFound = true;
                                        $column = $choices[$columnSelect];
                                    }
                                }
                            } else {
                                $columnFound = true;
                            }

                            if (!$columnFound) {
                                throw new \Exception("Can not find {$tableName}.{$column} in database, use -i (--interactive-merge) option to resolve");
                            }

                            if ('' !== $comment) {
                                $this->writeSQLComment($outStream, $dbData[$tableName]['columns'][$column]['alterSQL'], $comment);
                            }
                        }
                    }
                }
            }
        } else {
            /**
             * dump table\column comments in textile format.
             *
             * h1. TableName
             *
             * h2. one-lien table description
             *
             * |ColumnName1|[empty comment]|
             * |ColumnName2||
             */
            foreach ($dbData as $table => $tableData) {
                if ($dbDump) {
                    $outStream->writeln("\nh1. " . $table . "\n");

                    if (isset($tableData['comment']) && (0 !== strlen(trim($tableData['comment'])))) {
                        $tableComment = $tableData['comment'];
                    } else {
                        $tableComment = "Table Description [CHANGE_ME]";
                    }
                    $outStream->writeln("h2. " . $tableComment . "\n");

                    foreach ($tableData['columns'] as $columnName => $columnData) {
                        $outStream->writeln('|' . implode('|', [
                            /* $table, */
                            $columnName,
                            $columnData['comment'],
                        ]) . '|');
                    }
                } else {
                    $outStream->writeln("\nh1. " . $table . "\n\nh2. Table Description [CHANGE_ME]\n");

                    foreach ($tableData['columns'] as $columnName => $columnData) {
                        $outStream->writeln('|' . implode('|', [
                            /* $table, */
                            $columnName,
                            '',
                        ]) . '|');
                    }
                }
            }
        }

        return 0;
    }

    private function writeSQLComment(OutputInterface $output, $sqlTemplate, $comment)
    {
        $output->writeln('$this->addSql("' . $sqlTemplate . ' ?;", [\'' . addcslashes($comment, "'\\\\") . '\'], [\PDO::PARAM_STR]);');
    }

    private function askForReplace(OutputInterface $output, $question, $default, $oldData, $newData)
    {
        $choices = [
            'y' => 'Yes',
            'n' => 'No',
        ];
        $formattedQuestion = "\n<question>{$question}</question>\n";
        $formattedQuestion .= "OLD: <info>\"{$oldData}\"</info> (mb_strlen=" . mb_strlen($oldData) . ")\n";
        $formattedQuestion .= "NEW: <info>\"{$newData}\"</info> (mb_strlen=" . mb_strlen($newData) . ")\n";
        $formattedQuestion .= "default = " . $choices[$default];

        $answer = $this->dialog->select(
            $output,
            $formattedQuestion,
            $choices,
            $default,
            3
        );

        return 'y' === strtolower($answer);
    }
}
