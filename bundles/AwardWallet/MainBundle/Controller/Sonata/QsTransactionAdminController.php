<?php

namespace AwardWallet\MainBundle\Controller\Sonata;

use AwardWallet\MainBundle\Entity\QsTransaction;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class QsTransactionAdminController extends CRUDController
{
    private Connection $connection;
    private RouterInterface $router;

    public function __construct(Connection $connection, RouterInterface $router)
    {
        $this->connection = $connection;
        $this->router = $router;
    }

    /**
     * @return RedirectResponse
     * @throws \Exception
     */
    public function importAction(Request $request)
    {
        $backUrl = $request->request->has('backUrl') ? $request->request->get('backUrl') : $this->router->generate('qs_transaction_list');

        $file = $request->files->get('transactions');

        if (empty($file) || 'text/plain' !== $file->getMimeType() || $file->getError() > 0) {
            return new RedirectResponse($backUrl);
        }

        $data = $this->parseCsv($file->getPathname());

        if (!empty($data)) {
            $this->updateTransactions($data);
        }

        return new RedirectResponse($backUrl);
    }

    /**
     * @throws \Exception
     */
    private function parseCsv(string $filePath): ?array
    {
        $text = file_get_contents($filePath);
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        $file = explode("\n", $text);

        $csv = array_map('str_getcsv', $file);

        if (1 === count($csv[count($csv) - 1])) {
            unset($csv[count($csv) - 1]);
        }
        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
            unset($a['']);
        });
        array_shift($csv);

        $requiredFields = ['Date', 'Account', 'Var1', 'Card', 'Clicks', 'Earnings', 'CPC', 'Approvals'];

        if ((bool) array_diff_key(array_flip($requiredFields), $csv[0])) {
            throw new \InvalidArgumentException('Incorrect data, check availability: ' . implode(',', $requiredFields));
        }

        $data = [];

        foreach ($csv as $row) {
            $rec = [];

            $rec['RawAccount'] = $row['Account'];

            if (false !== strpos($row['Account'], 'Referral Links to CardRatings')) {
                $rec['Account'] = QsTransaction::ACCOUNT_CARDRATINGS;
            } elseif (false !== strpos($row['Account'], 'Direct')) {
                $rec['Account'] = QsTransaction::ACCOUNT_DIRECT;
            } elseif (false !== strpos($row['Account'], 'Award Travel 101')) {
                $rec['Account'] = QsTransaction::ACCOUNT_AWARDTRAVEL101;
            }

            if (!empty($row['Var1'])) {
                $vars = array_filter(explode('.', $row['Var1']));

                if (false === strpos($row['Var1'], '~') && 1 === count($vars)) {
                    if (false !== strpos($vars[0], 'accountlist')) {
                        $rec['Source'] = 'accountlist';
                    } elseif (false !== strpos($vars[0], '101')) {
                        $rec['Source'] = '101';
                    } elseif (false !== strpos($vars[0], 'bya')) {
                        $rec['Source'] = 'bya';
                    } elseif (false !== strpos($vars[0], 'marketplace')) {
                        $rec['Exit'] = 'marketplace';
                    }
                } else {
                    foreach ($vars as $key => $value) {
                        $values = explode('~', $value);

                        if (2 === \count($values)) {
                            [$k, $v] = $values;
                            $vars[$k] = $v;
                        }
                    }

                    foreach ([
                        'source' => 'Source',
                        'pid' => 'BlogPostID',
                        'mid' => 'MID',
                        'cid' => 'CID',
                        'rkbtyn' => 'RefCode',
                        'exit' => 'Exit',
                    ] as $var => $column) {
                        array_key_exists($var, $vars) ? $rec[$column] = $vars[$var] : null;
                    }
                }
            }
            $rec['RawVar1'] = $row['Var1'];

            $rec['CardName'] = $row['Card'];
            $rec['Clicks'] = $row['Clicks'];
            $rec['Earnings'] = ltrim($row['Earnings'], '$ ');
            $rec['CPC'] = ltrim($row['CPC'], '$ ');
            $rec['Approvals'] = $row['Approvals'];

            $date = explode('/', $row['Date']);
            $rec['ClickDate'] = new \DateTime(implode('-', [$date[2], $date[0], $date[1]]));

            $rec['Hash'] = sha1($row['Date'] . $row['Account'] . $row['Var1'] . $row['Card']);

            $data[] = $rec;
        }

        return $data;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateTransactions(array $data): bool
    {
        $users = [];
        $refCodes = array_unique(array_column($data, 'RefCode'));

        if (!empty($refCodes)) {
            $users = $this->connection->fetchAll(
                'SELECT UserID, RefCode FROM Usr WHERE RefCode IN (?)',
                [$refCodes], [\Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
            );

            if (!empty($users)) {
                $users = array_combine(array_column($users, 'RefCode'), array_column($users, 'UserID'));
            }
        }

        $symbols = ['®', '℠', '™', '(R)', '(SM)', '(TM)'];
        $cards = $this->connection->fetchAll('SELECT QsCreditCardID, CardName FROM QsCreditCard');

        foreach ($cards as $i => $card) {
            $cards[$i]['CardName'] = strtolower(str_replace($symbols, '', $cards[$i]['CardName']));
        }
        $cardsHistory = $this->connection->fetchAll('SELECT DISTINCT CardName, QsCreditCardID FROM QsCreditCardHistory');

        foreach ($cardsHistory as $i => $card) {
            $cardsHistory[$i]['CardName'] = strtolower(str_replace($symbols, '', $cardsHistory[$i]['CardName']));
        }

        $notFound = [];
        $foundCards = [];
        $dataCards = array_column($data, 'CardName');
        $dataCards = array_unique($dataCards);

        foreach ($dataCards as $card) {
            $qsCardId = $this->foundQsCard(strtolower(str_replace($symbols, '', $card)), $cards, $cardsHistory);

            if (empty($qsCardId)) {
                $notFound[] = $card;

                continue;
            }

            $foundCards[$card] = $qsCardId;
        }

        foreach ($data as $row) {
            $upd = [];

            if (array_key_exists($row['CardName'], $foundCards)) {
                $upd[] = ['field' => 'QsCreditCardID', 'value' => $foundCards[$row['CardName']], 'type' => ParameterType::INTEGER];
            }

            $upd[] = ['field' => 'ClickDate', 'value' => $row['ClickDate']->format('c'), 'type' => ParameterType::STRING];
            $upd[] = ['field' => 'Card', 'value' => $row['CardName'], 'type' => ParameterType::STRING];

            if (isset($row['RefCode']) && array_key_exists($row['RefCode'], $users)) {
                $upd[] = ['field' => 'UserID', 'value' => $users[$row['RefCode']], 'type' => ParameterType::INTEGER];
            }

            foreach (['Account', 'BlogPostID', 'Clicks', 'Earnings', 'CPC', 'Approvals'] as $field) {
                if (array_key_exists($field, $row)) {
                    $upd[] = ['field' => $field, 'value' => $row[$field], 'type' => ParameterType::INTEGER];
                }
            }

            foreach (['Source', 'Exit', 'MID', 'CID', 'RefCode', 'RawAccount', 'RawVar1', 'Hash'] as $field) {
                if (array_key_exists($field, $row)) {
                    $upd[] = ['field' => $field, 'value' => $row[$field], 'type' => ParameterType::STRING];
                }
            }

            $upd[] = ['field' => 'CreationDate', 'value' => date('c'), 'type' => ParameterType::STRING];

            $sql = '
                INSERT IGNORE INTO 
                    `QsTransaction` (`' . implode('`,`', array_column($upd, 'field')) . '`)
                VALUES
                    (' . rtrim(str_repeat('?,', \count($upd)), ',') . ')';
            $this->connection->executeQuery($sql, array_column($upd, 'value'), array_column($upd, 'type'));
        }

        return true;
    }

    private function foundQsCard(string $cardName, array $cards = [], array $cardsHistory = []): ?int
    {
        foreach ($cards as $card) {
            if ($cardName === $card['CardName']) {
                return $card['QsCreditCardID'];
            }
        }

        foreach ($cardsHistory as $card) {
            if ($cardName === $card['CardName']) {
                return $card['QsCreditCardID'];
            }
        }

        $cardName = str_replace('credit card', 'card', $cardName);

        foreach ($cards as $card) {
            if ($cardName === str_replace('credit card', 'card', $card['CardName'])) {
                return $card['QsCreditCardID'];
            }
        }

        foreach ($cardsHistory as $card) {
            if ($cardName === str_replace('credit card', 'card', $card['CardName'])) {
                return $card['QsCreditCardID'];
            }
        }

        return null;
    }
}
