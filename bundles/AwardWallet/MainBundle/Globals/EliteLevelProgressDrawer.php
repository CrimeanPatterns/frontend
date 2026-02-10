<?php

namespace AwardWallet\MainBundle\Globals;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EliteLevelProgressDrawer
{
    /**
     * TODO: add optional condition to disable elite if some(marked as required) properies are missing in account.
     */
    // states
    public const STATE_REACHED = 1;
    public const STATE_PROGRESS = 2;
    public const STATE_HOLLOW = 3;
    public const STATE_UNKNOWN = 4;
    public const STATE_SKIPPED = 5;

    /**
     * Depends only on scrapped Status property and corresponding rank;.
     */
    public const KIND_USER_LEVEL_DEPENDENT = 1;
    /**
     * Mostly lifetime bars. Do not depends on user's elite rank, mark as reached only if progress >= 100.
     */
    public const KIND_PROGRESS_DEPENDENT = 2;

    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var \Symfony\Component\Translation\TranslatorInterface */
    protected $translator;

    /** @var \AwardWallet\MainBundle\Entity\Usr */
    protected $user;

    /** @var \AwardWallet\MainBundle\Globals\Localizer\LocalizeService */
    protected $localizer;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    public function __construct(EntityManager $manager, TranslatorInterface $translator, LocalizeService $localizer, LoggerInterface $logger)
    {
        $this->em = $manager;
        $this->translator = $translator;
        $this->localizer = $localizer;
        $this->logger = $logger;
    }

    /**
     * @return $this
     */
    public function setUser(Usr $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param null $subAccountId why is it?
     * @param int $width
     * @return string|null
     */
    public function draw($accountId, $accountInfo, $subAccountId = null, $width = 850)
    {
        $info = $this->loadInfo($accountId, $subAccountId, $accountInfo);

        if (isset($info) && is_array($info)
            && isset($info['EliteLevels']['Progress']) && is_array($info['EliteLevels']['Progress']) && sizeof($info['EliteLevels']['Progress']) > 0
            && isset($info['EliteLevels']['Levels']) && is_array($info['EliteLevels']['Levels']) && sizeof($info['EliteLevels']['Levels']) > 0
        ) {
            $info['EliteLevels'] = $this->createModel($info['EliteLevels'], $width);

            if (isset($info['EliteLevels'])) {
                $info['EliteLevels'] = $this->mergeReachedBars($info['EliteLevels']);
                $info['EliteLevels'] = $this->mergeDelimiters($info['EliteLevels']);
                $info['EliteLevels'] = $this->group($info['EliteLevels']);

                return $this->drawTab($accountId, $subAccountId, $width, $info);
            } else {
                $this->logger->info('Account EliteLevels error', [
                    'accountId' => $info['ID'],
                    'provider' => $info['ProviderCode'],
                ]);
            }
        }

        return null;
    }

    protected function loadInfo($accountId, $subAccountId, $accountInfo)
    {
        $conn = $this->em->getConnection();
        $elRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class);
        // load account info
        $row = $conn->executeQuery("
                SELECT
                    a.AccountID AS ID,
                    a.*,
                    p.RedirectByHTTPS,
                    p.DisplayName,
                    p.Name AS ProviderName,
                    p.Code AS ProviderCode,
                    p.State AS ProviderState,
					p.AutoLogin,
					p.CalcEliteLevelExpDate,
					p.EliteProgramComment
				FROM Account a
					JOIN Provider p
					    ON a.ProviderID = p.ProviderID
                WHERE
                    a.AccountID = ?
            ",
            [$accountId],
            [\PDO::PARAM_INT]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return;
        }

        if (!isset($accountInfo['MainProperties']['Status']['Status'])) {
            return;
        }

        $accountInfo['CalcEliteLevelExpDate'] = $row['CalcEliteLevelExpDate'];
        $accountInfo['EliteProgramComment'] = $row['EliteProgramComment'];
        $status = $accountInfo['MainProperties']['Status']['Status'];
        $accountInfo['EliteLevels'] = [];
        $levels = &$accountInfo['EliteLevels'];
        $levels['Fields'] = $elRep->getEliteLevelFields($accountInfo["ProviderID"], $status);

        if (empty($levels['Fields']) || !is_array($levels['Fields'])) {
            $levels['Fields'] = [
                "Rank" => 0,
                "Name" => $status,
            ];
        }

        if (isset($subAccountId)) {
            $subAccountSql = " = " . intval($subAccountId);
        } else {
            $subAccountSql = " IS NULL";
        }
        // load elite levels and progress
        $sql = "
			SELECT
			  elp.EliteLevelProgressID AS ID,
			  elp.EndDay,
			  elp.EndMonth,
			  elp.Lifetime,
			  elp.ToNextLevel,
			  elp.GroupIndex,
			  elp.Position,
			  elp.GroupID,
			  elp.Operator,
			  ap.ProviderPropertyID,
			  ap.Val                   AS PropertyValue,
			  app.Val                  AS StartDateValue,
			  pp.Name                  AS PropertyName,
			  elv.Value                AS Goal,
			  el.Name                  AS EliteLevelName,
			  els.Rank                 AS StartLevelRank,
			  el.Rank                  AS EliteLevelRank,
			  eliteS.MaxRankByProgressBar,
			  eliteGroupS.MaxRankByGroup
			FROM Account a
			  INNER JOIN ProviderProperty pp
				ON a.ProviderID = pp.ProviderID
			  INNER JOIN EliteLevelProgress elp
				ON elp.ProviderPropertyID = pp.ProviderPropertyID
			  LEFT JOIN AccountProperty ap
				ON ap.ProviderPropertyID = elp.ProviderPropertyID AND
				   ap.AccountID = a.AccountID AND ap.SubAccountID {$subAccountSql}
			  LEFT JOIN AccountProperty app
				ON app.ProviderPropertyID = elp.StartDatePropertyID AND
				   app.AccountID = a.AccountID AND app.SubAccountID {$subAccountSql}
			  INNER JOIN EliteLevelValue elv
				ON elp.EliteLevelProgressID = elv.EliteLevelProgressID
			  LEFT JOIN (
				  SELECT
				  	elvS.EliteLevelProgressID,
				    max(elS.Rank) AS MaxRankByProgressBar
				  FROM EliteLevelValue elvS
				  JOIN EliteLevel elS
				  	ON elvS.EliteLevelID = elS.EliteLevelID
				  GROUP BY
				  	elvS.EliteLevelProgressID
				  ) eliteS ON eliteS.EliteLevelProgressID = elp.EliteLevelProgressID
			  LEFT JOIN EliteLevel el
				ON elv.EliteLevelID = el.EliteLevelID
			  LEFT JOIN (
				SELECT
					elS.ProviderID,
				 	elp.GroupIndex,
				  	max(elS.Rank) AS MaxRankByGroup
				FROM EliteLevelValue elvS
				JOIN EliteLevel elS
					ON elvS.EliteLevelID = elS.EliteLevelID
				JOIN EliteLevelProgress elp
					ON elvS.EliteLevelProgressID = elp.EliteLevelProgressID
				GROUP BY
					elS.ProviderID, GroupIndex
			  ) eliteGroupS ON
			  	eliteGroupS.ProviderID = el.ProviderID AND
				(eliteGroupS.GroupIndex = elp.GroupIndex OR (elp.GroupIndex IS NULL AND
				 											 eliteGroupS.GroupIndex IS NULL))
			  LEFT JOIN EliteLevel els
				ON elp.StartLevelID = els.EliteLevelID

			WHERE a.AccountID = ?
			ORDER BY
			  elp.Lifetime,
			  eliteGroupS.MaxRankByGroup DESC,
			  elp.GroupIndex,
		      elp.Position,
			  StartLevelRank,
			  eliteS.MaxRankByProgressBar DESC,
			  ID,
			  EliteLevelRank
		";
        $stmt = $conn->executeQuery($sql, [$accountId], [\PDO::PARAM_INT]);
        $levels['LevelsByRank'] = $levels['Progress'] = $levels['Levels'] = [];
        $maxLevel = $levels['Fields']['Rank'];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $pID = $row["ID"];

            if (!isset($levels['Progress'][$pID])) {
                $levels['Progress'][$pID] = array_merge(array_intersect_key($row, [
                    "EndDay" => null,
                    "EndMonth" => null,
                    "PropertyValue" => null,
                    "StartDateValue" => null,
                    "PropertyName" => null,
                    "ToNextLevel" => null,
                    "GroupIndex" => null,
                    "StartLevelRank" => null,
                    "Position" => null,
                    "GroupID" => null,
                    "Operator" => null,
                ]), ["EliteValues" => []]);

                // fileterBalance DO NOT returns zero if null.
                // TODO: remove filterBalance
                $levels['Progress'][$pID]["PropertyValue"] = filterBalance($row['PropertyValue'], false);
                $levels['Progress'][$pID]['Lifetime'] = (bool) $row['Lifetime'];

                if (!is_numeric($row["StartDateValue"])) {
                    $levels['Progress'][$pID]["StartDateValue"] = null;
                }

                if (empty($levels['Progress'][$pID]["EndDay"])) {
                    $levels['Progress'][$pID]["EndDay"] = 0;
                }

                if (empty($levels['Progress'][$pID]["EndMonth"])) {
                    $levels['Progress'][$pID]["EndMonth"] = 0;
                }
            }
            $levels['Progress'][$pID]["EliteValues"][$row["EliteLevelRank"]] = $row["Goal"];

            if ($row["EliteLevelRank"] > $maxLevel) {
                $maxLevel = (int) $row["EliteLevelRank"];
            }
        }

        // get all available elite levels, because property with correspondig progress-by-level data might be missing
        $stmt = $conn->executeQuery("
                SELECT
                    el.Rank,
                    el.Name,
                    el.Description
                FROM Account a
                JOIN EliteLevel el
                    ON el.ProviderID = a.ProviderID
                WHERE
                    a.AccountID = ? AND el.Rank <= ? AND el.ByDefault = 1
                ORDER BY el.Rank
			",
            [$accountId, $maxLevel],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($levels['Fields']['Rank'] == $row['Rank']) {
                $row['Name'] = $levels['Fields']['Name'];

                if (isset($levels['Fields']['Description'])) {
                    $row['Description'] = $levels['Fields']['Description'];
                }
            }
            $levelData = [
                'Rank' => $row['Rank'],
                'Name' => $row['Name'],
                'Description' => $row['Description'],
                'Progress' => false,
            ];

            if (!isset($levels['LevelsByRank'][$row['Rank']])) {
                $levels['LevelsByRank'][$row['Rank']] = &$levelData;
                $levels['Levels'][] = &$levelData;
            }
            unset($levelData);
        }

        return $accountInfo;
    }

    protected function createModel($fields, $width)
    {
        $fields['Model'] = [];
        $levelsStart = $fields['Levels'][0]['Rank'];
        $levelsEnd = $fields['Levels'][count($fields['Levels']) - 1]['Rank'];

        $chunks = [];
        $row = 0;

        foreach ($fields['Progress'] as $progressChunk) {
            if ($progressChunk['Lifetime']) {
                continue;
            }

            if ($fields['Fields']['Rank'] < $progressChunk['StartLevelRank'] // hide progress bar if it starts after user current level
                // || $fields['Fields']['Rank'] < $levelsEnd && !isset($progressChunk['PropertyValue']) // get rid off missing properties
            ) {
                continue;
            }
            $chunk = [];
            $chunk['Name'] = $progressChunk['PropertyName'];

            // set up kind
            if ($progressChunk['Lifetime']) {
                $chunk['Kind'] = self::KIND_PROGRESS_DEPENDENT;
            } else {
                $chunk['Kind'] = self::KIND_USER_LEVEL_DEPENDENT;
            }

            $chunk['Lifetime'] = $progressChunk['Lifetime'];
            $chunk['GroupIndex'] = $progressChunk['GroupIndex'];
            $chunk['GroupID'] = $progressChunk['GroupID'];
            $chunk['Operator'] = $progressChunk['Operator'];

            // get chunk boundaries
            $chunk['LeftBound'] = isset($progressChunk['StartLevelRank']) ? (int) $progressChunk['StartLevelRank'] : $fields['Levels'][0]['Rank'];
            end($progressChunk['EliteValues']);
            $chunk['RightBound'] = key($progressChunk['EliteValues']);
            reset($progressChunk['EliteValues']);

            $chunk['Segments'] = [];

            // add skipped segments
            for ($i = $levelsStart; $i < $chunk['LeftBound']; $i++) {
                $skippedSegment = [
                    'State' => self::STATE_SKIPPED,
                    'StartX' => 0,
                    'LengthX' => $chunk['LeftBound'],

                    'StartY' => $row,
                    'LengthY' => 1,

                    'Level' => $i + 1,
                    'SkippedBefore' => true,
                ];
                $chunk['Segments'][$i + 1] = $skippedSegment;
            }
            $segmentStart = $chunk['LeftBound'];
            $progressExists = false;

            foreach ($progressChunk['EliteValues'] as $segmentGoalLevel => $segmentGoalLevelValue) {
                $progress = null;
                $lengthX = null;

                if ($segmentGoalLevel >= $segmentStart) {// TODO: redundant check?
                    $progress = null;
                    $state = self::STATE_UNKNOWN;
                    $lengthX = $segmentGoalLevel - $segmentStart;

                    if (!isset($progressChunk['PropertyValue'])) {
                        $progressChunk['PropertyValue'] = 0;
                    }

                    if ($progressChunk['ToNextLevel']) {
                        if ($segmentGoalLevel > $fields['Fields']['Rank'] && !$progressExists) {
                            $progressChunk['PropertyValue'] = $segmentGoalLevelValue - $progressChunk["PropertyValue"];

                            if ($progressChunk['PropertyValue'] < 0) {
                                $progressChunk['PropertyValue'] = 0;
                            }
                        }
                    }
                    $progress = intval(100 * $progressChunk["PropertyValue"] / $segmentGoalLevelValue);

                    if ($progress > 100) {
                        $progress = 100;
                    }

                    switch ($chunk['Kind']) {
                        case self::KIND_PROGRESS_DEPENDENT:
                            if ($progress > 100) {
                                $state = self::STATE_REACHED;
                            } elseif (!$progressExists) {
                                $state = self::STATE_PROGRESS;
                                $progressExists = true;
                            } else {
                                $state = self::STATE_HOLLOW;
                            }

                            break;

                        case self::KIND_USER_LEVEL_DEPENDENT:
                            // determine segment state and progress
                            if ($segmentGoalLevel <= $fields['Fields']['Rank']) {
                                $state = self::STATE_REACHED;
                            } elseif (!$progressExists && $segmentStart <= $fields['Fields']['Rank']) {
                                $state = self::STATE_PROGRESS;
                                $progressExists = true;
                            } else {
                                $state = self::STATE_HOLLOW;
                            }

                            break;
                    }

                    for ($i = $segmentStart + 1; $i <= $segmentGoalLevel; $i++) {
                        $chunkSegment = [
                            'StartX' => $segmentStart,
                            'LengthX' => $lengthX,

                            'StartY' => $row,
                            'LengthY' => 1,

                            'Progress' => $progress,
                            'State' => $state,

                            "PropertyName" => $progressChunk["PropertyName"],
                            "Goal" => $progressChunk["EliteValues"][$segmentGoalLevel],
                            "PropertyValue" => $progressChunk["PropertyValue"],

                            'Level' => $i,
                        ];
                        $chunk['Segments'][$i] = $chunkSegment;
                    }
                    $fields['LevelsByRank'][$segmentGoalLevel]['Progress'] = true;

                    $segmentStart = $segmentGoalLevel;
                    // $prevChunkSegment = $chunkSegment;
                }
            }

            if (isset($segmentGoalLevel)) {
                for ($i = $segmentGoalLevel; $i < $levelsEnd; $i++) {
                    $skippedSegment = [
                        'State' => self::STATE_SKIPPED,

                        'StartX' => $i,
                        'LengthX' => 1,

                        'StartY' => $row,
                        'LengthY' => 1,

                        'Level' => $i + 1,
                        'SkippedAfter' => true,
                    ];
                    $chunk['Segments'][$i + 1] = $skippedSegment;
                }
            }

            if (count($chunk['Segments']) > 0) {
                $chunks[] = $chunk;
                $row++;
            }
        }

        if (count($chunks) > 0) {
            $fields['Model'] = $chunks;
        } else {
            return;
        }

        return $fields;
    }

    protected function mergeReachedBars($fields)
    {
        // three-pass merge
        // iterate over chunk and merge reached segments, by increasing LengthX
        $rows = count($fields['Model']);

        // mark segments with level <= userLevel as reached
        for ($row = 0; $row < $rows; $row++) {
            $chunk = &$fields['Model'][$row];

            if ($chunk['Kind'] != self::KIND_PROGRESS_DEPENDENT) {
                $chunkSegments = &$chunk['Segments'];

                $maxChunkSegmentsCol = (count($chunkSegmentsCols = array_keys($chunkSegments)) > 1) ?
                    max(...$chunkSegmentsCols) :
                    $chunkSegmentsCols[0];

                $col = min(
                    $fields['Fields']['Rank'],
                    $maxChunkSegmentsCol
                );

                while ($col > $fields['Levels'][0]['Rank']) {
                    if ($chunkSegments[$col]['State'] != self::STATE_REACHED) {
                        // mark as merged before user
                        $segment = $chunkSegments[$col];

                        for ($j = $segment['Level']; $j > $segment['StartX']; $j--) {
                            $chunkSegments[$j]['State'] = self::STATE_REACHED;
                            $chunkSegments[$j]['LengthX'] = $segment['Level'] - $segment['StartX'];

                            if ($j <= $chunk['LeftBound']) {
                                $chunk['LeftBound']--;
                            }

                            if ($j > $chunk['RightBound']) {
                                $chunk['RightBound']++;
                            }
                        }

                        for ($j = $segment['Level'] + 1; $j <= $segment['StartX'] + $segment['LengthX']; $j++) {
                            $chunkSegments[$j]['StartX'] = $segment['Level'];
                            $chunkSegments[$j]['LengthX'] = $segment['StartX'] + $segment['LengthX'] - $segment['Level'];
                        }
                    }
                    // walk back
                    $col = $chunkSegments[$col]['StartX'];
                }
            }
        }

        // merge reached by X
        for ($row = 0; $row < $rows; $row++) {
            $chunk = &$fields['Model'][$row];
            $chunkSegments = &$chunk['Segments'];
            $cols = count($chunkSegments);
            $col = $chunkSegments[$fields['Levels'][0]['Rank'] + 1]['StartX'] + $chunkSegments[$fields['Levels'][0]['Rank'] + 1]['LengthX'];

            while ($col <= $fields['Fields']['Rank']) {
                if ($chunkSegments[$col]['State'] == self::STATE_REACHED) {
                    for ($j = $col; $j > $chunkSegments[$col]['StartX']; $j--) {
                        $chunkSegments[$j]['MergedStartX'] = $chunkSegments[$col]['StartX'];
                        $chunkSegments[$j]['MergedLengthX'] = $chunkSegments[$col]['LengthX'];
                    }

                    $segment = $chunkSegments[$col];

                    // merge reached by X
                    if (isset($chunkSegments[$col - $segment['LengthX']]) // if there is prev segment
                        && ($chunkSegments[$col - $segment['LengthX']]['State'] == self::STATE_REACHED) // and the prev is reached
                    ) {
                        $prevSegment = $chunkSegments[$col - $segment['LengthX']];

                        for ($j = $col; $j > $prevSegment['MergedStartX']; $j--) {
                            $chunkSegments[$j]['MergedStartX'] = $prevSegment['MergedStartX'];
                            $chunkSegments[$j]['MergedLengthX'] = $segment['LengthX'] + $prevSegment['MergedLengthX'];
                        }
                    }
                }

                if (isset($chunkSegments[$col + 1])) {
                    $col = $chunkSegments[$col + 1]['StartX'] + $chunkSegments[$col + 1]['LengthX'];
                } else {
                    break;
                }
            }
        }

        // delete row if it fully reached and it is not last row, do not remove lifetime bar
        for ($row = 0; $row < $rows; $row++) {
            $chunk = &$fields['Model'][$row];
            $chunkSegments = &$chunk['Segments'];

            if (isset($chunkSegments[1]['MergedLengthX'])
                && $chunkSegments[1]['MergedLengthX'] == $chunk['RightBound']
                && $chunkSegments[1]['State'] == self::STATE_REACHED
                && $chunk['Kind'] != self::KIND_PROGRESS_DEPENDENT
                && count(array_filter($fields['Model'])) > 1
            ) {
                $chunk = null;

                // shift next rows
                for ($i = $row + 1; $i < $rows; $i++) {
                    for ($j = 1; $j <= count($fields['Model'][$i]['Segments']); $j++) {
                        if (isset($fields['Model'][$i]['Segments'][$j]['MergedStartY'])) {
                            $fields['Model'][$i]['Segments'][$j]['MergedStartY']--;
                        }
                        $fields['Model'][$i]['Segments'][$j]['StartY']--;
                    }
                }
            }
        }
        // clear from null-rows
        $fields['Model'] = array_values(array_filter($fields['Model']));
        $rows = count($fields['Model']);

        $levelsByRank = $fields['LevelsByRank'];
        unset($levelsByRank[$fields['Levels'][0]['Rank']]);

        // merge reached by Y
        foreach ($levelsByRank as $levelRank => $levelData) {
            for ($row = 0; $row < $rows; $row++) {
                if ($fields['Model'][$row]['Segments'][$levelRank]['State'] == self::STATE_REACHED) {
                    if (!isset($reachedSeqBegin)) {
                        $reachedSeqBegin = $row;
                    }
                } else {
                    $reachedSeqBegin = null;
                }

                if (isset($reachedSeqBegin) && $row >= $reachedSeqBegin) {
                    if (($row == $rows - 1) // is it last ?
                        || ($fields['Model'][$row + 1]['Kind'] !== $fields['Model'][$row]['Kind'])) { // or kind switched...
                        for ($j = $reachedSeqBegin; $j <= $row; $j++) {
                            $fields['Model'][$j]['Segments'][$levelRank]['MergedLengthY'] = $row - $reachedSeqBegin + 1;
                            $fields['Model'][$j]['Segments'][$levelRank]['MergedStartY'] = $reachedSeqBegin;
                        }
                        $reachedSeqBegin = null;
                    }
                }
            }
        }

        return $fields;
    }

    protected function mergeDelimiters($fields)
    {
        $rows = count($fields['Model']);
        $levels = $fields['LevelsByRank'];
        unset($levels[$fields['Levels'][0]['Rank']]);

        foreach ($levels as $levelRank => $levelData) {
            $delimSeqBegin = null;
            $lastNonSkipped = null;

            for ($row = 0; $row < $rows; $row++) {
                $chunk = &$fields['Model'][$row];

                $stretchOver = ($levelRank == $chunk['Segments'][$levelRank]['StartX'] + $chunk['Segments'][$levelRank]['LengthX']
                    && $chunk['Segments'][$levelRank]['State'] != self::STATE_REACHED && $chunk['Kind'] != self::KIND_PROGRESS_DEPENDENT); // stretch over non-reached bars

                //  should not be "skipped" until it has next not-skipped segment
                $nonSkipped = ($chunk['Segments'][$levelRank]['State'] != self::STATE_SKIPPED || (isset($chunk['Segments'][$levelRank + 1])
                        && ($chunk['Segments'][$levelRank + 1]['State'] != self::STATE_SKIPPED)));

                if ($nonSkipped && $stretchOver) {
                    $lastNonSkipped = $row;

                    if (!isset($delimSeqBegin)) {
                        $delimSeqBegin = $row;
                    }
                }

                if (isset($delimSeqBegin) && $row >= $delimSeqBegin) {
                    if (($row == $rows - 1)
                        || ($fields['Model'][$row + 1]['Kind'] !== $fields['Model'][$row]['Kind'])
                        || ($levelRank != $fields['Model'][$row + 1]['Segments'][$levelRank]['StartX'] + $fields['Model'][$row + 1]['Segments'][$levelRank]['LengthX'])
                    ) {
                        for ($j = $delimSeqBegin; $j <= $lastNonSkipped; $j++) {
                            $fields['Model'][$j]['Segments'][$levelRank]['DelimiterLengthY'] = $lastNonSkipped - $delimSeqBegin + 1;
                            $fields['Model'][$j]['Segments'][$levelRank]['DelimiterStartY'] = $delimSeqBegin;
                        }
                        $fields['Model'][--$j]['Segments'][$levelRank]['DelimiterEnd'] = true;

                        $fields['LevelsByRank'][$levelRank]['Delimiter'] = $delimSeqBegin;
                        $delimSeqBegin = null;
                    }
                }
            }
        }

        return $fields;
    }

    protected function group($fields)
    {
        $rows = count($fields['Model']);
        $levels = $fields['LevelsByRank'];
        unset($levels[$fields['Levels'][0]['Rank']]);
        $levelRank = (int) $fields['Fields']['Rank'] + 1;

        if ($levelRank > count($levels)) {
            return $fields;
        }
        $firstGroupedRow = null;
        $groupIndex = null;
        $groupWidth = null;
        $lastGroupedRow = null;
        $isGroupEnd = false;

        for ($row = 0; $row < $rows; $row++) {
            $chunk = &$fields['Model'][$row];
            $segments = &$chunk['Segments'];
            $segment = &$segments[$levelRank];

            $stretchOver = (isset($chunk['GroupIndex']) || (isset($chunk['GroupID']))) // grouped chunks
                && $segment['State'] == self::STATE_PROGRESS // group only progress segments
                && $levelRank == $segment['StartX'] + $segment['LengthX']; // group at the segment's right end

            if ($stretchOver) {
                $lastGroupedRow = $row;
                $groupId = $chunk['GroupIndex'] ?? $chunk['GroupID'];

                if (!isset($firstGroupedRow)) {
                    // start point
                    $firstGroupedRow = $row;
                    $groupIndex = $groupId;
                    $groupWidth = $segment['LengthX'];
                    $isGroupEnd = false;
                } elseif ($groupIndex !== $groupId // not the same group index
                    || $groupWidth != $segment['LengthX']) { // not the same width
                    $groupIndex = $groupId;
                    $groupWidth = $segment['LengthX'];

                    if ($lastGroupedRow == $firstGroupedRow) {
                        $firstGroupedRow = $row;
                        $isGroupEnd = false;
                    } else {
                        $isGroupEnd = true;
                        $lastGroupedRow = $row - 1;
                    }
                }

                if ($row == $rows - 1) {
                    $isGroupEnd = true;
                }
            } else {
                $groupIndex = null;
                $groupWidth = null;
                $isGroupEnd = true;
            }

            if (isset($firstGroupedRow) && $lastGroupedRow > $firstGroupedRow && $isGroupEnd) {
                for ($j = $firstGroupedRow; $j <= $lastGroupedRow; $j++) {
                    $fields['Model'][$j]['Segments'][$levelRank]['GroupLengthY'] = $lastGroupedRow - $firstGroupedRow + 1;
                    $fields['Model'][$j]['Segments'][$levelRank]['GroupStartY'] = $firstGroupedRow;
                }
                $fields['Model'][--$j]['Segments'][$levelRank]['GroupEnd'] = true;

                $fields['Levels'][$levelRank]['Group'][] = $firstGroupedRow;

                if ($stretchOver) {
                    $firstGroupedRow = $row;
                    $lastGroupedRow = $row;
                    $groupIndex = $chunk['GroupIndex'] ?? $chunk['GroupID'];
                }
            }
        }

        return $fields;
    }

    protected function drawTab($accountId, $subAccountId, $width, $info)
    {
        $fields = &$info['EliteLevels'];
        $sep = "
			<tr>
				<td style=\"border-bottom: #cccccc solid 1px;\">
				</td>
			</tr>
		";
        $html = "<table class='elite_stats'>";

        if (isset($fields['Fields']["Name"])) {
            $currentElTitle = (isset($fields['Fields']["Rank"]) && $fields['Fields']["Rank"] > 0)
                ? $this->translator->trans('account.details.elitelevels.current-level')
                : $this->translator->trans('account.details.elitelevels.current-elite-level');
            $html .= "
				<tr>
					<td class=\"elite_stats\">
						<span>$currentElTitle: <strong> {$fields['Fields']["Name"]}</strong></span>
						</td>
				</tr>
			" . $sep;
        }

        if (isset($fields['Fields']["Rank"]) && $fields['Fields']["Rank"] > 0) {
            $expDate = false;

            if (isset($info['Properties'])) {
                foreach ($info['Properties'] as $property) {
                    if (isset($property['Kind']) && $property['Kind'] == PROPERTY_KIND_STATUS_EXPIRATION) {
                        $expDate = $property['Val'];
                        $expDate = (is_numeric($expDate) ? $expDate : null);
                    }
                }
            }

            if (!$expDate) {
                $dates = $this->getLevelDates($fields);
                $expDate = reset($dates);
            }

            if ($info['CalcEliteLevelExpDate'] && $expDate) {
                $expDate = $this->localizer->formatDateTime(new \DateTime('@' . $expDate), 'full');
                $html .= "
					<tr>
						<td class=\"elite_stats\">
							<span>" . $this->translator->trans('account.details.elitelevels.expiration') . ": <strong> {$expDate} </strong></span>
						</td>
					</tr>
				" . $sep;
            }
        }

        if (isset($fields["Fields"]["AllianceEliteLevelID"])) {
            $ael = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Allianceelitelevel::class)->find($fields["Fields"]["AllianceEliteLevelID"]);

            if ($ael) {
                $aName = $ael->getAllianceid()->getName();
            }

            if (isset($aName)) {
                $html .= "
					<tr>
						<td class=\"elite_stats\">
							<span>
							" . $this->translator->trans('account.details.elitelevels.elite-level', ['%name%' => $aName]) . ":
							<strong> {$fields["Fields"]["AllianceName"]} </strong></span>
						</td>
					</tr>
				" . $sep;
            }
        }
        $nextLevel = null;

        foreach ($fields["Levels"] as $level) {
            if ($level["Rank"] > $fields["Fields"]["Rank"]) {
                $nextLevel = $level;

                break;
            }
        }

        if (isset($nextLevel)) {
            $html .= "
				<tr>
					<td class=\"elite_stats\">
						<span>" . $this->translator->trans('account.details.elitelevels.next-elite-level') . ": <strong> {$nextLevel["Name"]} </strong></span>
					</td>
				</tr>
			" . $sep;
        }
        $html .= "</table>";
        $html .= $this->drawChart($accountId, $subAccountId, $width, $info);

        if (isset($info['EliteProgramComment']) && !empty($info['EliteProgramComment'])) {
            $html .= "<div class='eliteCommentText'>" . nl2br($info['EliteProgramComment']) . "</div>";
        }

        return $html;
    }

    protected function drawChart($accountId, $subAccountId, $width, $info)
    {
        $fields = &$info['EliteLevels'];
        $dates = $this->getLevelDates($fields);
        $nID = $accountId . '-' . intval($subAccountId);

        // available levels
        $levels = $fields['Levels'];
        unset($levels[0]);
        $levelsCount = count($levels);
        // precalculate html attribs
        $this->calcHtmlArrtibutes($accountId, $subAccountId, $width, $info);
        $result = "<table style='border-collapse: separate; width: 95%;' class='{$nID}_elite_chart elite_chart' data-levels-count='{$levelsCount}' data-account-id='{$accountId}-" . intval($subAccountId) . "'>";

        $result .= "<tr>";

        foreach ($levels as $key => $levelData) {
            $levelRank = $levelData['Rank'];
            $colspan = $this->getCellColspan($fields, $levelRank, 1);

            if (!isset($fields['Model'][0]['Segments'][$levelRank])) {
                DieTrace('EliteLevelProgress: broken level, accountID:' . $nID, false);
            }

            if ($fields['Model'][0]['Segments'][$levelRank]['State'] != self::STATE_REACHED && $levelData['Progress']) {
                $levelName = $levelData['Name'] ?? '';

                if (isset($levelData['Description']) && !empty($levelData['Description'])) {
                    $levelName = "<img id='{$nID}_{$levelData['Rank']}' data-role='tooltip' title='" . htmlspecialchars($levelData['Description'], ENT_QUOTES) . "' src='/images/levels/info.png' class='eliteLevelInfo'>" . $levelName;
                }
            } else {
                $levelName = "";
            }
            $result .= "<td colspan='{$colspan}'><div class='elite_level_name'>{$levelName}</div></td>";
        }

        $result .= "</tr>";

        foreach ($fields['Model'] as $row => $chunk) {
            $result .= "
	<tr>";
            // spacer cells after each row, for additional info, and\or
            $spacers = [];
            $mergedSpacers = [];

            foreach ($chunk['Segments'] as $segmentLevel => $segment) {
                if (isset($segment['MergedStartY']) && $row == $segment['MergedStartY']
                    || !isset($segment['MergedStartY']) && $segmentLevel == $segment['StartX'] + $segment['LengthX']
                ) {
                    $spacer = ['Class' => null,
                        'Inner' => null,
                        'State' => $segment['State'], ];

                    switch ($segment['State']) {
                        case self::STATE_REACHED:
                            // count in order
                            $levelCounter = 1;

                            foreach ($levels as $key => $levelData) {
                                if ($levelData['Rank'] == $segmentLevel) {
                                    break;
                                }
                                $levelCounter++;
                            }
                            $level = intval($levelCounter / count($levels) * 5); // TODO: move this to js
                            $result .= "
								<td colspan='{$segment['Colspan']}' rowspan='{$segment['Rowspan']}' class='{$nID}_reached_{$level} reached {$segment['Classes']}' data-initial-length='{$segment['InitialLength']}'>
									<div style='position: absolute;'><span> {$fields['LevelsByRank'][$segmentLevel]["Name"]} </span></div>
								</td>";

                            break;

                        case self::STATE_PROGRESS:
                            // grouping borders
                            $class = "";

                            if (isset($segment['GroupStartY'])) {
                                $class .= " hasGroup";
                                $spacer['Class'] .= $class;

                                if ($segment['GroupStartY'] == $row) {
                                    $class .= " firstGrouped";
                                } elseif ($segment['GroupStartY'] + $segment['GroupLengthY'] - 1 == $row) {
                                    $class .= " lastGrouped";
                                    $spacer['Class'] = '';
                                }
                            }

                            $result .= "
								<td colspan='{$segment['Colspan']}' class='{$class} {$segment['Classes']}' data-initial-length='{$segment['InitialLength']}'>
									<div class='barContainter'>";

                            // todo localizer!
                            $val = number_format_localized($segment["PropertyValue"], 0);
                            $progress = $segment["Progress"];

                            if ($progress > 0) {
                                $width = $progress;

                                $result .= "
										<div class='{$nID}_progress internal progress' style='width: {$width}%;'>
											<div class='progressValue'>{$val}</div>
										</div>";
                            }

                            if ($progress < 100) {
                                $width = 100 - $progress;

                                // todo localizer!
                                $needed = number_format_localized($segment["Goal"] - $segment["PropertyValue"], 0);
                                $result .= "
										<div class='{$nID}_empty internal empty' style='width: {$width}%;'>
											<div class='needed'>
												{$needed} needed
											</div>
										</div>";
                            }

                            // hide goal in case of "at least" property
                            // todo localizer!
                            if ($segment['Goal'] < $segment['PropertyValue']) {
                                $segment['Goal'] = '';
                            } else {
                                $segment['Goal'] = number_format_localized($segment["Goal"], 0);
                            }
                            $result .= "
									</div>

							        <div class='eliteComment'>
										<div class='propertyName'>{$chunk['Name']}</div>
							            <div class='levelGoal'>{$segment["Goal"]}</div>
									</div>";

                            if (isset($fields['Model'][$row + 1]) && $fields['Model'][$row]['Kind'] == $fields['Model'][$row + 1]['Kind']) {
                                if (isset($fields['Model'][$row]['GroupID'])) {
                                    $text = $fields['Model'][$row + 1]['Operator'] == 1 ? 'OR' : 'AND';
                                } else {
                                    if (
                                        isset($segment['GroupStartY'])
                                        && isset($fields['Model'][$row + 1]['Segments'][$segmentLevel]['GroupStartY'])
                                        && $fields['Model'][$row + 1]['GroupIndex'] === $fields['Model'][$row]['GroupIndex']
                                    ) {
                                        $text = "AND";
                                    } else {
                                        $text = "OR";
                                    }
                                }

                                $spacer['Inner'] = "<div class='andorContainer'><span class='andor'>{$text}</span></div>";
                            }

                            $result .= "</td>";

                            break;

                        case self::STATE_HOLLOW:
                            // todo localizer!
                            $segment['Goal'] = number_format_localized($segment["Goal"], 0);
                            $result .= "
								<td colspan='{$segment['Colspan']}' data-initial-length='{$segment['InitialLength']}' class='{$segment['Classes']}'>
									<div class='barContainter'>
										<div class='internal empty' style='width: 100%;'>&nbsp;</div>
									</div>

							        <div class='eliteComment'>
							            <div class='levelGoal'>{$segment["Goal"]}</div>
									</div>
								</td>";

                            break;

                        case self::STATE_SKIPPED:
                            $result .= "
							<td colspan='{$segment['Colspan']}' class='{$nID}_skipped_{$segmentLevel} skipped {$segment['Classes']}' data-initial-length='{$segment['InitialLength']}'>
								<div><span></span></div>
							</td>
							";

                            break;
                    }

                    if (isset($segment['GroupStartY']) && $segment['GroupStartY'] == $row) {
                        $rowspan = $segment['GroupLengthY'] * 2 - 1;
                        $result .= "
							<td rowspan='{$rowspan}' class='{$nID}_group group' colspan='1'>
							</td>";
                    }

                    if (isset($segment['DelimiterStartY']) && $segment['DelimiterStartY'] == $row) {
                        $rowspan = $segment['DelimiterLengthY'] * 2 - 1;
                        $result .= "
							<td rowspan='{$rowspan}' class='{$nID}_delimiter delimiter' colspan='1'>
							</td>";
                    }
                }

                // add segment spacer
                if ((isset($segment['MergedStartY']) && $row == $segment['MergedStartY'] + $segment['MergedLengthY'] - 1 // spacer for reached bars
                        || !isset($segment['MergedStartY']) && $segmentLevel == $segment['StartX'] + $segment['LengthX']) // spacer for others
                    && !in_array($segmentLevel, $mergedSpacers)) {
                    $spacer['Colspan'] = $segment['Colspan'];

                    // carry on delimiters and groups colspans
                    if (isset($segment['GroupEnd']) && !isset($fields['Model'][$row + 1]['Segments'][$segmentLevel]['GroupEnd'])) {
                        $spacer['Colspan']++;
                    }

                    if (isset($segment['DelimiterEnd']) && !isset($fields['Model'][$row + 1]['Segments'][$segmentLevel]['DelimiterEnd'])) {
                        $spacer['Colspan']++;
                    }

                    // stretch spacer
                    if (isset($fields['Model'][$row + 1]['Segments'][$segmentLevel])) {
                        $nextRowSegment = $fields['Model'][$row + 1]['Segments'][$segmentLevel];

                        if ($segmentLevel != $nextRowSegment['StartX'] + $nextRowSegment['LengthX']) {
                            if ($segmentLevel == $nextRowSegment['StartX'] + 1) {
                                $spacer['Colspan'] = $fields['Model'][$row + 1]['Segments'][$nextRowSegment['StartX'] + $nextRowSegment['LengthX']]['Colspan'];

                                for ($i = $segmentLevel; $i <= $nextRowSegment['StartX'] + $nextRowSegment['LengthX']; $i++) {
                                    $mergedSpacers[] = $i;
                                }
                            }
                        }
                    }

                    if ($segment['State'] == self::STATE_REACHED) {
                        $spacer['Inner'] = null;
                        $spacer['Class'] = '';
                    }
                    $spacer['Inner'] = "<div class='tableSpacer {$spacer['Class']}'>" . ArrayVal($spacer, 'Inner', '&nbsp;') . "</div>";
                    $spacer['Row'] = $row;

                    $spacers[$segmentLevel] = $spacer;
                }
            }

            $result .= "</tr>";

            // print spacers
            $result .= "<tr>";

            foreach ($spacers as $spacer) {
                $result .= "<td colspan='{$spacer['Colspan']}'>{$spacer['Inner']}</td>\n";
            }
            $result .= "</tr>";
        }
        $result .= "</table>";

        return $result;
    }

    protected function getCellColspan($fields, $cellLevel, $initialColspan = 1)
    {
        if (isset($fields['LevelsByRank'][$cellLevel]['Delimiter'])) {
            $initialColspan++;
        }

        if (isset($fields['LevelsByRank'][$cellLevel]['Group'])) {
            $initialColspan++;
        }

        return $initialColspan;
    }

    protected function calcHtmlArrtibutes($accountId, $subAccountId, $width, $info)
    {
        $fields = &$info['EliteLevels'];

        foreach ($fields['Model'] as $row => &$chunk) {
            foreach ($chunk['Segments'] as $segmentLevel => &$segment) {
                if (isset($segment['MergedStartY']) && $row == $segment['MergedStartY']
                    || !isset($segment['MergedStartY']) && $segmentLevel == $segment['StartX'] + $segment['LengthX']
                ) {
                    // initial values
                    $rowspan = isset($segment['MergedLengthY']) ? $segment['MergedLengthY'] * 2 - 1 : 1;
                    $colspan = $segment['LengthX'];
                    $cellClasses = "{$accountId}-" . intval($subAccountId) . "_eliteCell";

                    // do not stretch reached bars
                    if ($segment['State'] == self::STATE_REACHED) {
                        $colspan = 1;
                    }
                    // save for cell width calculation in js
                    $initialLength = $colspan;

                    // calc colspans
                    for ($i = $segment['StartX'] + 1; $i <= $segment['StartX'] + $segment['LengthX']; $i++) {
                        $colspan = $this->getCellColspan($fields, $i, $colspan);
                    }

                    if (isset($segment['DelimiterStartY'])) {
                        $colspan--;
                        $cellClasses .= ' hasDelimiter';
                    }

                    if (isset($segment['GroupStartY'])) {
                        $colspan--;
                        $cellClasses .= ' hasGroup';
                    }

                    $segment['Colspan'] = $colspan;
                    $segment['Rowspan'] = $rowspan;
                    $segment['Classes'] = $cellClasses;
                    $segment['InitialLength'] = $initialLength;

                    if (isset($segment['MergedStartY'])) {
                        for ($j = $segment['MergedStartY']; $j < $segment['MergedStartY'] + $segment['MergedLengthY']; $j++) {
                            $fields['Model'][$j]['Segments'][$segmentLevel]['Colspan'] = $colspan;
                        }
                    }
                }
            }
        }
        // do not forget to unset "&" vars for further usage
    }

    protected function getLevelDates($fields)
    {
        $dates = [];

        foreach ($fields['Progress'] as $progress) {
            $rank = null;

            foreach ($progress["EliteValues"] as $r => $value) {
                if ($r >= $fields['Fields']["Rank"]) {
                    $rank = $r;

                    break;
                }
            }

            if (isset($rank) && !empty($progress["StartDateValue"]) && (!empty($progress["EndDay"]) || !empty($progress["EndMonth"]))) {
                $date = strtotime(" + {$progress["EndMonth"]} months + {$progress["EndDay"]} days", $progress["StartDateValue"]);

                if (!isset($dates[$rank]) || $date < $dates[$rank]) {
                    $dates[$rank] = $date;
                }
            }
        }

        return $dates;
    }

    protected function getLevelName($accountInfo, $rank)
    {
        foreach ($accountInfo['EliteLevels']['Levels'] as $level) {
            if ($level["Rank"] == $rank) {
                return $level["Name"];
            }
        }

        return null;
    }
}
