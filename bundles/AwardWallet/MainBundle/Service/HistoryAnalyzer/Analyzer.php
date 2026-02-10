<?php

namespace AwardWallet\MainBundle\Service\HistoryAnalyzer;

use Psr\Log\LoggerInterface;

class Analyzer
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * $rows = [
     *      [
     *          'UserID' => 1,
     *          'UserAgentID' => 2,
     *          'ProviderID' => 3,
     *          all fields from AccountHistory table
     *      ],
     *      ...
     * ].
     */
    public function analyze(array $rows)
    {
        $response = new AnalyzerResponse();
        $response->rows = $this->analyzeRows($rows);

        $flights = [];
        $other = [];

        foreach ($response->rows as $row) {
            if ($row['Excluded']) {
                $response->excluded++;
            } elseif ($row['Miles'] > 0) {
                if ($row['Type'] == 'flight') {
                    $flights[] = $row;
                } else {
                    $other[] = $row;
                }
            }
        }

        $response->volumeOfPoints->flights = array_sum(array_map(function (array $row) { return $row['Miles']; }, $flights));
        $response->volumeOfPoints->other = array_sum(array_map(function (array $row) { return $row['Miles']; }, $other));

        $response->transactionFrequency->flights = count($flights);
        $response->transactionFrequency->other = count($other);

        $response->transactionRecency->flights = $this->transactionRecency($flights);
        $response->transactionRecency->other = $this->transactionRecency($other);

        return $response;
    }

    /**
     * @see analyze for rows format
     * @return array
     */
    private function analyzeRows(array $rows)
    {
        usort($rows, function (array $a, array $b) {
            return strcasecmp($a['PostingDate'], $b['PostingDate']);
        });

        $byValue = [];

        foreach ($rows as &$row) {
            $row['Type'] = call_user_func([$this, "isFlight" . $row['ProviderID']], $row) ? "flight" : "other";

            if (
                $row['Miles'] < 0
                && (!method_exists($this, "isInversed" . $row['ProviderID']) || call_user_func([$this, "isInversed" . $row['ProviderID']], $row))
            ) {
                $byValue[$row['Miles']][] = $row;
            }
        }

        $byValue = array_filter($byValue, function ($rows) {
            return (count($rows) % 2) == 0;
        });

        foreach ($rows as &$row) {
            if (isset($byValue[$row["Miles"] * -1])) {
                $inversed = &$byValue[$row["Miles"] * -1];
            } else {
                $inversed = [];
            }
            $row['Excluded'] = $row['Miles'] > 0 && call_user_func_array([$this, "isExcluded" . $row['ProviderID']], [$row, &$inversed]);
        }

        //        if(count($byValue) > 0)
        //            $this->logger->info("by value matches", ["count" => count($byValue), "AccountID" => $byValue[array_keys($byValue)[0]][0]["AccountID"], "ProviderID" => $byValue[array_keys($byValue)[0]][0]["ProviderID"]]);
        return $rows;
    }

    private function transactionRecency(array $rows)
    {
        if (empty($rows)) {
            return 0;
        }

        $last = new \DateTime(array_pop($rows)['PostingDate']);
        $now = new \DateTime();

        return max(12 - $now->diff($last, true)->m, 1);
    }

    // malaysia
    private function isFlight136(array $row)
    {
        return strpos($row['Description'], '-') !== false && !preg_match('#\b(award|lounge|Redemption)\b#ims', $row['Description']);
    }

    private function isInversed136(array $row)
    {
        return stripos($row['Description'], 'expired') === false;
    }

    private function isExcluded136(array $row, array &$inversed)
    {
        return !empty($inversed) && array_pop($inversed);
    }

    // singapore
    private function isFlight71(array $row)
    {
        return preg_match('#\b[A-Z]{3}\-[A-Z]{3}\b|Tier Bonus Factor#ims', $row['Description']) && $row['Miles'] > 0;
    }

    private function isExcluded71(array $row, array &$inversed)
    {
        return stripos($row['Description'], 'Adjustment') !== false;
    }

    // qantas
    private function isFlight33(array $row)
    {
        return preg_match('#\b[A-Z]{3}/[A-Z]{3}\b#ims', $row['Description'])
            || preg_match('#\b[A-Z]{2}\s+\d{2,5}.+/.+#ims', $row['Description']);
    }

    private function isExcluded33(array $row, array &$inversed)
    {
        return stripos($row['Description'], 'Refund') !== false
            || stripos($row['Description'], 'CANCELLED REDEMPTION POINTS REINSTATED') !== false;
    }

    // emirates
    private function isFlight48(array $row)
    {
        $info = unserialize($row['Info']);

        return
            !empty($info['Partner']) && in_array(strtolower($info['Partner']), ['emirates', 'jetblue', 'qantas', 'alaska', 'easyjet', 'flydubai', 'gol', 'japan', 'jetblue', 'jetstar', 'korean', 'malaysia airlines', 's7', 'southafrican', 'tap', 'virgin'])
            && (strpos($row['Description'], '-') !== false || preg_match('#\sto\s#ims', $row['Description']));
    }

    private function isExcluded48(array $row, array &$inversed)
    {
        return !empty($inversed) && array_pop($inversed);
    }

    // british
    private function isFlight31(array $row)
    {
        return preg_match('#operated by|booking|cabin bonus|tier bonus|\-.+\b[A-Z]{2}\d{2,}\b#ims', $row['Description']) && $row['Miles'] > 0;
    }

    private function isExcluded31(array $row, array &$inversed)
    {
        return stripos($row['Description'], 'Redeposit') !== false;
    }

    // cathay
    private function isFlight35(array $row)
    {
        return preg_match('#Claim Number|Airways|First Class|Economy Class|Business Class|Discount Economy|Domestic Business#ims', $row['Description']);
    }

    private function isExcluded35(array $row, array &$inversed)
    {
        return false;
    }
}
