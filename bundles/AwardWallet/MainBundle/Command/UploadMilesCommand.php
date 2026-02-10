<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UploadMilesCommand extends Command
{
    public static $defaultName = 'aw:upload:miles';
    private string $channel;
    private string $host;
    private string $remoteFile;
    private UsrRepository $usrRepository;
    private AccountRepository $accountRepository;
    private SymfonyStyle $io;

    public function __construct(string $channel, string $host, string $remoteFile, UsrRepository $usrRepository, AccountRepository $accountRepository)
    {
        parent::__construct();
        $this->channel = $channel;
        $this->host = $host;
        $this->remoteFile = $remoteFile;
        $this->usrRepository = $usrRepository;
        $this->accountRepository = $accountRepository;
    }

    public function configure()
    {
        $this
            ->setDescription("Upload AA miles to points.com")
            ->addOption('test-upload', null, InputOption::VALUE_NONE, 'test upload')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'host, from where to download data, with protocol, like https://awardwallet.com')
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, 'data day', date("Y-m-d", strtotime("-1 month")))
            ->addOption('from-file', null, InputOption::VALUE_OPTIONAL, 'json data file, use it instead of downloading from blog')
            ->addOption('skip-upload', null, InputOption::VALUE_NONE, 'do not upload, only mark as processed, usually used with --from-file')
            ->addOption('skip-mark', null, InputOption::VALUE_NONE, 'do not mark as processed, usually used with --from-file')
            ->addOption('auth', null, InputOption::VALUE_OPTIONAL, 'http auth for downloading data, in user:password format')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'run, otherwise check')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getOption('test-upload')) {
            $output->writeln("testing upload");
            $this->testUpload();
            $output->writeln("upload successful");

            return 0;
        }

        $host = $input->getOption("host");

        if (empty($host)) {
            $host = $this->channel . '://' . $this->host;
        }

        $rows = $this->downloadData($host, $input);

        if (empty($rows)) {
            $this->io->warning("empty data, there are nothing to upload");

            return 0;
        }

        [$fileName, $csv, $processed] = $this->prepareCsv($rows);

        if (!$input->getOption('skip-upload') && $input->getOption("force")) {
            $this->io->section("uploading " . $fileName);
            $remoteFile = str_replace("aaUpload.csv", $fileName, $this->remoteFile);
            $this->upload($remoteFile, $csv);
        }

        if ($input->getOption("force") && !$input->getOption('skip-mark')) {
            $urlMark = $host . '/blog/wp-json/blog/api/commenters-mark-processed/';
            $this->markProcessed($urlMark, $processed);
        }

        $output->writeln("done");

        return 0;
    }

    private function fputcsv($f, array $values)
    {
        $data = [];

        foreach ($values as $value) {
            $value = str_replace(",", "", $value);
            $data[] = $value;
        }

        return fputs($f, implode(",", $data) . "\r\n");
    }

    private function markProcessed(string $urlMark, array $processed)
    {
        $this->io->writeln("marking as processed to $urlMark");

        $info = [];
        $result = curlRequest(
            $urlMark,
            120,
            [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => json_encode($processed),
            ],
            $info,
            $errorCode
        );

        if ('true' === $result) {
            $this->io->success('done, mark as processed');

            return;
        }

        $this->io->writeln("curl error {$errorCode}: " . json_encode($info));

        throw new \Exception('Error when marking as processed: ' . $result);
    }

    // from https://stackoverflow.com/a/56786581
    // got segfault on plain file_put_contents
    private function upload(string $remoteUrl, string $content): void
    {
        $params = parse_url($remoteUrl);

        if (!$connection = ssh2_connect($params['host'], $params['port'])) {
            throw new \Exception("Failed to connect");
        }

        if (!ssh2_auth_password($connection, $params["user"], $params["pass"])) {
            throw new \Exception("Failed to login");
        }

        if (!$sftp_connection = ssh2_sftp($connection)) {
            throw new \Exception("Failed to open SFTP session");
        }

        if (!$fh = fopen($params['scheme'] . "://" . (int) $sftp_connection . $params['path'], 'wb')) {
            throw new \Exception("Failed to open file");
        }

        if (fwrite($fh, $content) === false) {
            throw new \Exception("Failed to write file");
        }

        fclose($fh);
        unset($sftp_connection);

        if (!ssh2_disconnect($connection)) {
            throw new \Exception("Failed to disconnect");
        }
    }

    private function testUpload(): void
    {
        $remoteFile = $this->remoteFile;
        $remoteFile = str_replace("aaUpload.csv", "test.csv", $remoteFile);
        $this->upload($remoteFile, "test-upload");
    }

    private function downloadData(string $host, InputInterface $input): array
    {
        if ($fromFile = $input->getOption('from-file')) {
            $data = file_get_contents($fromFile);
        } else {
            $data = $this->downloadFromUrl($host, $input);
        }

        if (strpos($data, '"') === 0) { // Irina's bug
            $data = json_decode($data);
        }

        $this->io->block($data);
        $rows = json_decode($data, true);

        if (!is_array($rows)) {
            throw new \Exception("Invalid data");
        }

        return $rows;
    }

    private function downloadFromUrl(string $host, InputInterface $input): string
    {
        $url = $host . "/blog/wp-json/blog/api/commenters/?from=" . $input->getOption("from");
        $this->io->section("downloading data from $url");

        if (!empty($input->getOption("auth"))) {
            $context = stream_context_create([
                'http' => [
                    'header' => [
                        'Authorization: Basic ' . base64_encode($input->getOption("auth")),
                    ],
                ],
            ]);
        } else {
            $context = null;
        }

        return file_get_contents($url, null, $context);
    }

    private function prepareCsv(array $rows): array
    {
        $localCsvFile = sys_get_temp_dir() . "/aw_aa_" . date("Y_m_d_H_i_s") . ".csv";
        $f = fopen($localCsvFile, "wb");

        if (empty($f)) {
            throw new \Exception("Failed to open file $localCsvFile");
        }

        if ($this->fputcsv($f, ["Campaign Code", "First Name", "Last Name", "AAdvantage No.", "Email", "Miles", "Activity Date", "Customer Transaction ID", "Extra Info"]) === false) {
            throw new \Exception("Failed to write file $localCsvFile");
        }

        $errors = 0;
        $count = 0;
        $processed = [];

        foreach ($rows as $row) {
            $this->io->text(json_encode($row));
            /** @var Usr $user */
            $user = $this->usrRepository->find($row['user_id']);

            if ($user === null) {
                $this->io->warning("could not find user {$row['user_id']}");
                $errors++;

                continue;
            }

            /** @var Account $account */
            $account = $this->accountRepository->find($row['account_id']);

            if ($account === null) {
                $this->io->warning("could not find account {$row['account_id']}");
                $processed[] = $row;
                $errors++;

                continue;
            }

            $email = $account->getUserid()->getEmail();

            if ($account->getUseragentid() !== null) {
                $firstName = $account->getUseragentid()->getFirstname();
                $lastName = $account->getUseragentid()->getLastname();

                if (!empty($account->getUseragentid()->getEmail())) {
                    $email = $account->getUseragentid()->getEmail();
                }
            } else {
                $firstName = $account->getUserid()->getFirstname();
                $lastName = $account->getUserid()->getLastname();
            }

            if (!empty($account->getLogin2())) {
                $lastName = $account->getLogin2();
            }

            if ($this->fputcsv($f, ["1", $firstName, $lastName, $account->getLogin(), $email, 5, date("m/d/Y"), time(), "AwardWallet Account " . $account->getAccountid() . " User " . $user->getUserid()]) === false) {
                throw new \Exception("Failed to write file $localCsvFile");
            }
            $count++;
            $processed[] = $row;
        }

        if (!fclose($f)) {
            throw new \Exception("Failed to close file");
        }

        $name = basename($localCsvFile);
        $contents = file_get_contents($localCsvFile);
        unlink($localCsvFile);

        if ($errors === 0) {
            $this->io->success("prepared $count accounts");
        } else {
            $this->io->warning("prepared $count accounts, with $errors errors");
        }

        return [$name, $contents, $processed];
    }
}
